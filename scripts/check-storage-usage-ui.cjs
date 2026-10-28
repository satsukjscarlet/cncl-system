const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.CNCL_PLAYWRIGHT_MODULE || 'playwright');

// All requests are fulfilled locally; this review never scans or writes application data.
const directory = process.env.CNCL_STORAGE_UI_DIR;
const root = path.resolve(__dirname, '..', 'public');
const current = {at: '2026-10-27T12:00:00+07:00', bytes: 20480, pdf_files: 4, pdf_bytes: 10240,
    database_bytes: 2048, types: {signed: {files: 2, bytes: 8192}, unsigned: {files: 1, bytes: 1024}, other: {files: 1, bytes: 1024}}};
const previous = {...current, at: '2026-10-26T12:00:00+07:00', pdf_bytes: 8192, pdf_files: 3};
const fixture = {files: 8, bytes: 20480, pdf_files: 4, pdf_bytes: 10240,
    complete: true, groups: {'app/private/quality-certificates': 10240},
    pdf_directories: {a: {directory: 'app/private/quality-certificates/smartca', type: 'signed', files: 2, bytes: 8192},
        b: {directory: 'app/private/quality-certificates/smartca', type: 'unsigned', files: 1, bytes: 1024},
        c: {directory: 'app/private/imports', type: 'other', files: 1, bytes: 1024}},
    disk: {total: 100000, free: 50000, used_percent: 50},
    database: {available: true, data: 1024, indexes: 1024, tables: [{name: 'quality_certificates', data_bytes: 1024, index_bytes: 1024}]},
    history: {month: '10/2026', saved: true, first: previous, previous, current, daily: [previous, current]},
    warnings: [], scanned_at: '27/10/2026 12:00:00', duration: 0.2};

(async () => {
    const browser = await chromium.launch({headless: true, executablePath: process.env.CNCL_BROWSER_BIN});
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({viewport: {width, height: 900}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            let scans = 0;
            await page.route('**/*', async route => {
                const request = route.request();
                const url = new URL(request.url());
                if (url.hostname !== 'storage-ui.test') return route.abort();
                if (url.pathname.endsWith('/storage-usage/scan')) {
                    assert.equal(request.method(), 'POST'); scans++;
                    await new Promise(resolve => setTimeout(resolve, 150));
                    if (scans === 3) return route.fulfill({status: 409, json: {message: 'Đang có lượt quét khác.'}});
                    return route.fulfill({json: scans === 2 ? {...fixture, complete: false, history: {...fixture.history, saved: false, current: null}} : fixture});
                }
                if (request.method() !== 'GET') return route.abort();
                if (url.pathname === '/review') return route.fulfill({contentType: 'text/html; charset=utf-8', body: fs.readFileSync(path.join(directory, 'storage.html'), 'utf8').replaceAll('http://localhost', 'http://storage-ui.test').replaceAll('http:\\/\\/localhost', 'http://storage-ui.test')});
                if (url.pathname.endsWith('/feed')) return route.fulfill({json: {label: 0, dropdown: ''}});
                const asset = path.resolve(root, '.' + decodeURIComponent(url.pathname));
                if (!asset.startsWith(root + path.sep) || !fs.existsSync(asset) || !fs.statSync(asset).isFile()) return route.fulfill({status: 404});
                return route.fulfill({path: asset});
            });
            await page.goto('http://storage-ui.test/review');
            await page.locator('#storage-results:visible').waitFor();
            assert.equal(await page.locator('#pdf-directory-rows tr').count(), 3);
            assert.equal(await page.locator('#history-rows tr').count(), 2);
            assert.equal(await page.locator('#growth-rows tr').count(), 8);
            assert(await page.locator('#history-chart .bg-info').first().evaluate(el => el.getBoundingClientRect().width > 0));
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Page overflows viewport');
            await page.screenshot({path: path.join(directory, `storage-${width}.png`), fullPage: true});
            await page.locator('#scan-storage').click();
            assert(await page.locator('#scan-storage').isDisabled());
            await page.waitForFunction(() => !document.getElementById('scan-storage').disabled);
            assert((await page.locator('#scan-errors').innerText()).includes('chưa đầy đủ'));
            assert.equal(await page.locator('#growth-rows tr').count(), 0);
            await page.locator('#scan-storage').click();
            await page.waitForFunction(() => !document.getElementById('scan-storage').disabled);
            assert((await page.locator('#scan-status').innerText()).includes('chưa được cập nhật'));
            assert.deepEqual(errors, []);
            process.stdout.write(`PASS ${width}px: table, chart, partial scan, error recovery\n`);
            await page.close();
        }
    } finally { await browser.close(); }
})().catch(error => { process.stderr.write(error.stack + '\n'); process.exitCode = 1; });
