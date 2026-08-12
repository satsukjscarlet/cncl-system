(function () {
    'use strict';

    var showTimer = null;
    var active = false;

    function ensureOverlay() {
        var overlay = document.querySelector('.cncl-loading-overlay');

        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.className = 'cncl-loading-overlay';
        overlay.setAttribute('aria-live', 'polite');
        overlay.setAttribute('aria-busy', 'true');
        overlay.innerHTML = [
            '<div class="cncl-loading-box" role="status">',
            '<span class="cncl-loading-spinner" aria-hidden="true"></span>',
            '<span class="cncl-loading-text">Đang xử lý, vui lòng chờ...</span>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);

        return overlay;
    }

    function showLoading(delay) {
        if (active) {
            return;
        }

        active = true;
        clearTimeout(showTimer);

        showTimer = setTimeout(function () {
            ensureOverlay();
            document.body.classList.add('cncl-loading');
        }, typeof delay === 'number' ? delay : 120);
    }

    function hideLoading() {
        active = false;
        clearTimeout(showTimer);
        document.body.classList.remove('cncl-loading');
    }

    function isModifiedClick(event) {
        return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
    }

    function shouldIgnoreLink(link) {
        var href = link.getAttribute('href') || '';

        return link.hasAttribute('data-no-loading')
            || link.classList.contains('no-loading')
            || link.hasAttribute('download')
            || link.getAttribute('target') === '_blank'
            || link.getAttribute('data-toggle')
            || link.getAttribute('data-widget')
            || href === ''
            || href.charAt(0) === '#'
            || href.indexOf('javascript:') === 0
            || href.indexOf('mailto:') === 0
            || href.indexOf('tel:') === 0;
    }

    function shouldIgnoreForm(form) {
        return form.hasAttribute('data-no-loading')
            || form.classList.contains('no-loading')
            || form.getAttribute('target') === '_blank';
    }

    function rememberSubmitter(event) {
        var button = event.target.closest('button[type="submit"], input[type="submit"]');

        if (button && button.form) {
            button.form.__cnclSubmitter = button;
        }
    }

    function preserveSubmitterValue(form, submitter) {
        if (!submitter || !submitter.name || submitter.disabled) {
            return;
        }

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = submitter.name;
        hidden.value = submitter.value;
        hidden.setAttribute('data-cncl-submit-shadow', '1');
        form.appendChild(hidden);
    }

    function disableSubmitButtons(form, submitter) {
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');

        buttons.forEach(function (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        });

        if (submitter && submitter.tagName === 'BUTTON') {
            submitter.setAttribute('data-cncl-original-html', submitter.innerHTML);
            submitter.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        }
    }

    document.addEventListener('click', function (event) {
        rememberSubmitter(event);

        var link = event.target.closest('a[href]');

        if (!link || isModifiedClick(event) || shouldIgnoreLink(link)) {
            return;
        }

        showLoading(140);
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || shouldIgnoreForm(form)) {
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        if (form.__cnclSubmitting) {
            event.preventDefault();
            return;
        }

        form.__cnclSubmitting = true;

        var submitter = event.submitter || form.__cnclSubmitter;
        preserveSubmitterValue(form, submitter);
        disableSubmitButtons(form, submitter);
        showLoading(80);
    }, true);

    window.addEventListener('pageshow', hideLoading);
    window.addEventListener('pagehide', hideLoading);
})();
