(function () {
    'use strict';

    var showTimer = null;
    var active = false;
    var defaultMessage = 'Đang xử lý, vui lòng chờ...';
    var submitterSelector = 'button:not([type]), button[type="submit"], input[type="submit"], input[type="image"]';

    function ensureOverlay(message) {
        var overlay = document.querySelector('.cncl-loading-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'cncl-loading-overlay';
            overlay.setAttribute('aria-live', 'polite');
            overlay.setAttribute('aria-busy', 'true');
            overlay.innerHTML = [
                '<div class="cncl-loading-box" role="status">',
                '<span class="cncl-loading-spinner" aria-hidden="true"></span>',
                '<span class="cncl-loading-text"></span>',
                '</div>'
            ].join('');

            document.body.appendChild(overlay);
        }

        setMessage(message || defaultMessage);

        return overlay;
    }

    function setMessage(message) {
        var text = document.querySelector('.cncl-loading-text');

        if (text) {
            text.textContent = message || defaultMessage;
        }
    }

    function showLoading(delay, message) {
        if (active) {
            ensureOverlay(message);
            document.body.classList.add('cncl-loading');
            return;
        }

        active = true;
        clearTimeout(showTimer);

        if (typeof delay === 'number' && delay <= 0) {
            ensureOverlay(message);
            document.body.classList.add('cncl-loading');
            return;
        }

        showTimer = setTimeout(function () {
            ensureOverlay(message);
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

        var lowerHref = href.toLowerCase();

        return link.hasAttribute('data-no-loading')
            || link.classList.contains('no-loading')
            || link.hasAttribute('data-download')
            || link.hasAttribute('download')
            || link.getAttribute('target') === '_blank'
            || link.getAttribute('data-toggle')
            || link.getAttribute('data-widget')
            || lowerHref.indexOf('template') !== -1
            || lowerHref.indexOf('export') !== -1
            || /\.(xlsx|xls|csv|pdf|zip)(\?|#|$)/i.test(href)
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
        var target = event.target;

        if (!target || typeof target.closest !== 'function') {
            return;
        }

        var button = target.closest(submitterSelector);

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
        var buttons = form.querySelectorAll(submitterSelector);

        buttons.forEach(function (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        });

        if (submitter && submitter.tagName === 'BUTTON') {
            submitter.setAttribute('data-cncl-original-html', submitter.innerHTML);
            submitter.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        }
    }

    function loadingMessageFor(form, submitter) {
        return (submitter && submitter.getAttribute('data-loading-message'))
            || form.getAttribute('data-loading-message')
            || defaultMessage;
    }

    function loadingDelayFor(form, submitter) {
        return form.hasAttribute('data-loading-lock')
            || (submitter && submitter.hasAttribute('data-loading-lock'))
            ? 0
            : 80;
    }

    document.addEventListener('click', function (event) {
        rememberSubmitter(event);

        var link = event.target.closest('a[href]');

        if (!link || isModifiedClick(event) || shouldIgnoreLink(link)) {
            return;
        }

        showLoading(140, link.getAttribute('data-loading-message'));
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (event.defaultPrevented) {
            return;
        }

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
        showLoading(loadingDelayFor(form, submitter), loadingMessageFor(form, submitter));
    }, false);

    window.CnclLoading = {
        show: function (message) {
            showLoading(0, message);
        },
        hide: hideLoading
    };

    window.addEventListener('pageshow', hideLoading);
    window.addEventListener('pagehide', hideLoading);
})();
