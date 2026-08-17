(function () {
    'use strict';

    var storageKey = 'cncl:last-browser-notification-id';
    var initializedKey = 'cncl:browser-notification-initialized';
    var pollMs = 30000;
    var feedUrl = new URL('/notifications/feed', window.location.origin).toString();

    function supported() {
        return 'Notification' in window && window.isSecureContext;
    }

    function readLastId() {
        return parseInt(window.localStorage.getItem(storageKey) || '0', 10) || 0;
    }

    function writeLastId(id) {
        if (id) {
            window.localStorage.setItem(storageKey, String(id));
        }
    }

    function requestPermission() {
        if (!supported() || Notification.permission !== 'default') {
            return;
        }

        Notification.requestPermission().catch(function () {});
    }

    function notificationOptions(item) {
        return {
            body: item.message || 'Anh có thông báo mới trên hệ thống CNCL.',
            tag: 'cncl-notification-' + item.id,
            renotify: false,
            icon: '/images/logo.png',
        };
    }

    function showBrowserNotification(item) {
        if (!supported() || Notification.permission !== 'granted') {
            return;
        }

        var browserNotification = new Notification(item.title || 'Thông báo mới', notificationOptions(item));

        browserNotification.onclick = function () {
            window.focus();

            if (item.url) {
                window.location.href = item.url;
            }

            browserNotification.close();
        };
    }

    function handleFeed(data) {
        var item = data && data.browser_notification;

        if (!item || !item.id) {
            return;
        }

        var id = parseInt(item.id, 10);
        var lastId = readLastId();
        var initialized = window.localStorage.getItem(initializedKey) === '1';

        if (!initialized) {
            writeLastId(id);
            window.localStorage.setItem(initializedKey, '1');
            return;
        }

        if (id <= lastId) {
            return;
        }

        writeLastId(id);
        showBrowserNotification(item);
    }

    function poll() {
        if (!supported()) {
            return;
        }

        fetch(feedUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(handleFeed)
            .catch(function () {});
    }

    function bindPermissionPrompt() {
        document.addEventListener('click', function (event) {
            var notificationTrigger = event.target.closest('#cncl-notification-bell, [href$="/notifications"], [href*="/notifications"]');

            if (notificationTrigger) {
                requestPermission();
            }
        }, true);
    }

    if (!supported()) {
        return;
    }

    bindPermissionPrompt();
    window.setTimeout(poll, 5000);
    window.setInterval(poll, pollMs);
})();
