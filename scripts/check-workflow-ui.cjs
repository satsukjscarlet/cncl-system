const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.CNCL_PLAYWRIGHT_MODULE || 'playwright');

// Serve only snapshots and public assets inside Playwright; never contact the application.
const root = path.resolve(__dirname, '..', 'public');
const snapshots = process.env.CNCL_UI_SNAPSHOT_DIR;
assert(snapshots && fs.existsSync(snapshots), 'Generate test HTML with CNCL_UI_SNAPSHOT_DIR first.');
const output = path.join(snapshots, 'screenshots');
fs.mkdirSync(output, { recursive: true });
const mime = { '.css': 'text/css', '.js': 'application/javascript', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf' };
const pages = fs.readdirSync(snapshots).filter(name => name.endsWith('.html')).map(name => name.slice(0, -5))
    .filter(name => !process.env.CNCL_UI_PAGES || process.env.CNCL_UI_PAGES.split(',').includes(name));

(async () => {
    const browser = await chromium.launch({
        headless: true,
        ...(process.env.CNCL_BROWSER_BIN ? { executablePath: process.env.CNCL_BROWSER_BIN } : {}),
    });
    const results = [];
    try {
        for (const viewport of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
            for (const name of pages) {
                const page = await browser.newPage({ viewport });
                const errors = [];
                const writes = [];
                page.on('pageerror', error => errors.push(error.message));
                await page.route('**/*', async route => {
                    const request = route.request();
                    if (request.method() !== 'GET') {
                        writes.push(request.method() + ' ' + request.url());
                        return route.fulfill({ status: 405, body: 'UI review is read-only.' });
                    }
                    const url = new URL(request.url());
                    if (url.hostname !== 'cncl-ui.test') return route.abort();
                    if (url.pathname.startsWith('/__review/')) {
                        const fixture = url.pathname.split('/').pop();
                        if (!pages.includes(fixture)) return route.fulfill({ status: 404 });
                        return route.fulfill({
                            contentType: 'text/html; charset=utf-8',
                            body: fs.readFileSync(path.join(snapshots, fixture + '.html'), 'utf8').replaceAll('http://localhost', 'http://cncl-ui.test'),
                        });
                    }
                    if (url.pathname.endsWith('/feed')) return route.fulfill({ contentType: 'application/json', body: '{"label":0,"dropdown":""}' });
                    const file = path.resolve(root, '.' + decodeURIComponent(url.pathname));
                    if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {
                        return route.fulfill({ status: 404 });
                    }
                    return route.fulfill({ path: file, contentType: mime[path.extname(file)] || 'application/octet-stream' });
                });
                try {
                    await page.goto('http://cncl-ui.test/__review/' + name, { waitUntil: 'networkidle' });
                    if (await page.locator('#monthlyChart').count()) {
                        await page.waitForFunction(() => window.Chart && Chart.getChart(document.getElementById('monthlyChart')));
                        assert(await page.locator('#monthlyChart').evaluate(canvas => {
                            const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
                            return pixels.some((value, index) => index % 4 === 3 && value > 0);
                        }), 'Dashboard chart is blank');
                    }
                    const bar = page.locator('.workflow-action-bar');
                    const detail = await bar.count() > 0;
                    const deliveryDate = page.locator('#delivery_date');
                    if (name.startsWith('group-default-')) {
                        const select = page.locator('#default_quality_standard_id');
                        const box = page.locator('#default_quality_standard_id + .select2 .select2-selection--single');
                        assert.equal(await box.evaluate(el => Math.round(el.getBoundingClientRect().height)), 40);
                        assert.equal(await box.evaluate(el => getComputedStyle(el).display), 'flex');
                        await box.click();
                        const search = page.locator('.select2-container--open .select2-search__field');
                        await search.waitFor({ state: 'visible' });
                        await page.waitForFunction(() => document.activeElement?.classList.contains('select2-search__field'));
                        await search.fill('STD-A');
                        const result = page.locator('.select2-results__option--selectable').filter({ hasText: 'STD-A' });
                        await result.first().click();
                        assert.notEqual(await select.inputValue(), '');
                        assert.equal(await page.locator('.select2-container--open').count(), 0);
                    }
                    if (name === 'product-default-create' || name === 'product-default-edit') {
                        const result = await page.evaluate(() => {
                            const group = $('#product_group_id');
                            const standard = $('#quality_standard_id');
                            const groups = Object.fromEntries(group.find('option').toArray().filter(o => o.value).map(o => [o.textContent.trim().split(' - ')[0], o.value]));
                            const standards = Object.fromEntries(standard.find('option').toArray().filter(o => o.value).map(o => [o.textContent.trim().split(' - ')[0], o.value]));
                            const initial = standard.val();
                            const values = [];
                            group.val(groups.GROUP).trigger('change');
                            values.push(standard.val());
                            group.val(groups['GROUP-B']).trigger('change');
                            values.push(standard.val());
                            standard.val(standards['STD-C']).trigger('change');
                            group.val(groups.GROUP).trigger('change');
                            values.push(standard.val());
                            standard.val('').trigger('change');
                            group.val(groups['GROUP-B']).trigger('change');
                            values.push(standard.val());
                            return { initial, values, standards, select2: group.hasClass('select2-hidden-accessible') };
                        });
                        assert(result.select2, 'Select2 did not initialize');
                        assert.equal(result.initial, name.endsWith('-edit') ? result.standards['STD-B'] : '');
                        assert.deepEqual(result.values, [
                            name.endsWith('-edit') ? result.standards['STD-B'] : result.standards['STD-A'],
                            result.standards['STD-B'], result.standards['STD-C'], '',
                        ]);
                    }
                    if (await deliveryDate.count()) {
                        const original = await deliveryDate.inputValue();
                        const today = await deliveryDate.getAttribute('data-today');
                        const nextDate = days => {
                            const date = new Date(today + 'T12:00:00Z');
                            date.setUTCDate(date.getUTCDate() + days);
                            return date.toISOString().slice(0, 10);
                        };
                        assert.equal(await deliveryDate.getAttribute('max'), null);
                        assert.equal(await page.locator('[name="confirmed_delivery_date"]').count(), 0);
                        const submit = page.locator('#request-submit-button');
                        const commitment = page.locator('#customer_commitment_confirmed');
                        if (!await commitment.isChecked()) await page.locator('label[for="customer_commitment_confirmed"]').click();
                        assert(await commitment.isChecked());
                        await deliveryDate.fill(nextDate(1));
                        assert(await page.locator('#future-delivery-warning').isVisible());
                        assert(await submit.isEnabled());
                        await deliveryDate.fill(nextDate(2));
                        assert(await submit.isEnabled());
                        const saveDraft = page.locator('button[name="request_action"][value="draft"]');
                        if (await saveDraft.count()) assert(await saveDraft.isEnabled());
                        await deliveryDate.fill(today);
                        assert.equal(await page.locator('#future-delivery-warning').isVisible(), false);
                        assert(await submit.isEnabled());
                        await deliveryDate.fill(original);
                    }
                    async function checkBounds() {
                        const bounds = await page.evaluate(() => {
                            const bar = document.querySelector('.workflow-action-bar');
                            const rect = bar?.getBoundingClientRect();
                            const content = document.querySelector('.content-wrapper').getBoundingClientRect();
                            return { width: innerWidth, height: innerHeight, scrollWidth: document.documentElement.scrollWidth,
                                bar: rect ? { left: rect.left, right: rect.right, bottom: rect.bottom, height: rect.height } : null,
                                contentLeft: content.left };
                        });
                        assert(bounds.scrollWidth <= bounds.width + 2, `Horizontal overflow: ${JSON.stringify(bounds)}`);
                        if (bounds.bar) {
                            assert(Math.abs(bounds.bar.bottom - bounds.height) < 2, 'Action bar is not at viewport bottom');
                            assert(Math.abs(bounds.bar.left - Math.max(0, bounds.contentLeft)) < 2, 'Sidebar/action bar alignment');
                            assert(bounds.bar.right <= bounds.width + 2 && bounds.bar.height < bounds.height * 0.55, 'Action bar overflow');
                        }
                    }
                    await checkBounds();
                    await page.screenshot({ path: path.join(output, `${name}-${viewport.width}-top.png`) });
                    if (detail) {
                        const search = page.locator('[data-product-search]');
                        if (await search.count()) {
                            const tableId = await search.getAttribute('data-product-search');
                            const rows = page.locator(`#${tableId} tbody tr[data-product-text]`);
                            const total = await rows.count();
                            await search.fill('__no_matching_product__');
                            assert.equal(await rows.evaluateAll(items => items.filter(item => !item.hidden).length), 0);
                            assert(await page.getByText('Không tìm thấy sản phẩm phù hợp.', { exact: true }).isVisible());
                            const firstCode = (await rows.first().getAttribute('data-product-text')).trim().split(/\s+/)[0];
                            await search.fill(firstCode);
                            assert(await rows.evaluateAll(items => items.filter(item => !item.hidden).length) > 0);
                            await search.fill('');
                            assert.equal(await rows.evaluateAll(items => items.filter(item => !item.hidden).length), total);
                        }
                        if (viewport.width > 1000) {
                            await page.locator('[data-widget="pushmenu"]').first().click();
                            await page.waitForTimeout(400);
                            await checkBounds();
                        }
                        const secondary = page.locator('.workflow-secondary-actions > summary');
                        if (await secondary.count()) {
                            await secondary.click();
                            await page.waitForTimeout(100);
                            await checkBounds();
                            await secondary.click();
                        }
                        await page.evaluate(() => {
                            document.querySelectorAll('.workflow-detail-table').forEach(table => { table.scrollTop = table.scrollHeight; });
                            scrollTo(0, document.body.scrollHeight);
                        });
                        await checkBounds();
                        await page.screenshot({ path: path.join(output, `${name}-${viewport.width}-bottom.png`) });
                        const modalButton = bar.locator('[data-toggle="modal"]').first();
                        if (await modalButton.count()) {
                            await modalButton.click();
                            const modal = page.locator('.modal.show');
                            await modal.waitFor({ state: 'visible' });
                            assert(await modal.evaluate(element => Number(getComputedStyle(element).zIndex)) > 1030);
                            await page.screenshot({ path: path.join(output, `${name}-${viewport.width}-modal.png`) });
                            await modal.locator('[data-dismiss="modal"]').first().click();
                            await modal.waitFor({ state: 'hidden' });
                            assert.equal(await page.locator('.cncl-loading').count(), 0, 'Cancel left loading active');
                        }
                        const confirmFormButton = bar.locator('form[onsubmit*="confirm"] button[type="submit"]').first();
                        if (await confirmFormButton.count() && await confirmFormButton.isVisible()) {
                            let dialogs = 0;
                            page.once('dialog', dialog => { dialogs++; return dialog.dismiss(); });
                            await confirmFormButton.click();
                            assert.equal(dialogs, 1, 'Confirmation dialog did not open');
                            assert.equal(await page.locator('.cncl-loading').count(), 0, 'Cancel confirmation left loading active');
                        }
                    }
                    if (!detail) {
                        const confirmButton = page.locator('form[onsubmit*="confirm"] button').first();
                        if (await confirmButton.count() && await confirmButton.isVisible()) {
                            let dialogs = 0;
                            page.once('dialog', dialog => { dialogs++; return dialog.dismiss(); });
                            await confirmButton.click();
                            assert.equal(dialogs, 1, 'List confirmation dialog did not open');
                            assert.equal(await page.locator('.cncl-loading').count(), 0);
                        }
                    }
                    assert.deepEqual(writes, [], 'Browser attempted to submit data');
                    assert.deepEqual(errors, [], 'Browser JavaScript errors');
                    results.push({ name, viewport: viewport.width, passed: true });
                    console.log(`PASS ${name} ${viewport.width}`);
                } catch (error) {
                    results.push({ name, viewport: viewport.width, passed: false, error: error.message });
                    await page.screenshot({ path: path.join(output, `${name}-${viewport.width}-failure.png`) });
                    console.log(`FAIL ${name} ${viewport.width}: ${error.message}`);
                } finally {
                    await page.close();
                }
            }
        }
    } finally {
        await browser.close();
    }
    fs.writeFileSync(path.join(snapshots, 'browser-results.json'), JSON.stringify(results, null, 2));
    console.log(`Screenshots: ${output}`);
    if (results.some(result => !result.passed)) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
