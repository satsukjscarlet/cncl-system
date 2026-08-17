(function () {
    function initSelect2(root) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return false;
        }

        var $root = root ? jQuery(root) : jQuery(document);

        $root.find('select.select2').each(function () {
            var $select = jQuery(this);

            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            var placeholder = $select.find('option:first').text() || '';
            var options = {
                width: '100%',
                allowClear: false,
                placeholder: placeholder,
                language: {
                    noResults: function () {
                        return '\u004b\u0068\u00f4\u006e\u0067 \u0074\u00ec\u006d \u0074\u0068\u1ea5\u0079 \u0064\u1eef \u006c\u0069\u1ec7\u0075';
                    },
                    searching: function () {
                        return '\u0110\u0061\u006e\u0067 \u0074\u00ec\u006d...';
                    },
                    inputTooShort: function () {
                        return '\u0056\u0075\u0069 \u006c\u00f2\u006e\u0067 \u006e\u0068\u1ead\u0070 \u0074\u0068\u00ea\u006d \u006b\u00fd \u0074\u1ef1';
                    }
                }
            };

            var $modal = $select.closest('.modal');
            if ($modal.length) {
                options.dropdownParent = $modal;
            }

            var ajaxUrl = $select.data('ajax-url');
            if (ajaxUrl) {
                options.minimumInputLength = Number($select.data('minimum-input-length') || 1);
                options.ajax = {
                    url: ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        var payload = {
                            q: params.term || ''
                        };

                        if ($select.data('ajax-include-center')) {
                            var centerValue = jQuery('[name="distribution_center_id"]').val();
                            if (centerValue) {
                                payload.distribution_center_id = centerValue;
                            }
                        }

                        return payload;
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                };
            }

            $select.select2(options);
        });

        return true;
    }

    function bootSelect2(attempt) {
        attempt = attempt || 0;

        if (initSelect2(document)) {
            if (!window.__cnclSelect2Observer) {
                window.__cnclSelect2Observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                initSelect2(node);
                            }
                        });
                    });
                });

                window.__cnclSelect2Observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            return;
        }

        if (attempt < 40) {
            window.setTimeout(function () {
                bootSelect2(attempt + 1);
            }, 100);
        }
    }

    window.initSelect2 = initSelect2;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bootSelect2(0);
        });
    } else {
        bootSelect2(0);
    }
})();
