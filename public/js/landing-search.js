/*
 * Home page hero search — tab switching. Loaded external + nonce-tagged.
 * Without JS the form is a plain GET to the facility directory and the
 * Medicines / Blood tabs are ordinary links to their finder pages.
 */
(function () {
    'use strict';
    function init() {
        var form = document.querySelector('[data-hm-search]');
        if (!form) { return; }
        var input = form.querySelector('[data-hm-input]');
        var group = form.querySelector('[data-hm-group]');
        var tabs = form.querySelectorAll('[data-hm-tab]');
        Array.prototype.forEach.call(tabs, function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                Array.prototype.forEach.call(tabs, function (t) {
                    t.classList.remove('is-on');
                    t.removeAttribute('aria-current');
                });
                tab.classList.add('is-on');
                tab.setAttribute('aria-current', 'true');
                form.setAttribute('action', tab.getAttribute('data-action'));
                var isBlood = tab.getAttribute('data-hm-tab') === 'blood';
                input.hidden = isBlood;
                input.disabled = isBlood;
                group.hidden = !isBlood;
                group.disabled = !isBlood;
                if (!isBlood) {
                    input.setAttribute('name', tab.getAttribute('data-name'));
                    input.setAttribute('placeholder', tab.getAttribute('data-placeholder') || '');
                    input.focus();
                } else {
                    group.focus();
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
