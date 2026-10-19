/* OpesCare Finder — accessible loading state for public searches.
 * Any GET search form (form.odp-search, form[role=search], [data-finder-search])
 * marks itself busy on submit: the submit button is disabled with a spinner,
 * and the shared live region announces the translated "Searching…" text.
 * CSP: external file, no inline handlers. */
(function () {
    'use strict';
    var SEL = 'form.odp-search, form[role="search"], form[data-finder-search]';
    var region = document.getElementById('f-loading-status');

    function busy(form) {
        form.setAttribute('aria-busy', 'true');
        form.classList.add('is-loading');
        var btns = form.querySelectorAll('button[type="submit"], button:not([type])');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.add('is-loading');
            btns[i].setAttribute('aria-disabled', 'true');
        }
        if (region) {
            
            region.textContent = region.getAttribute('data-text') || '';
        }
        var main = document.getElementById('f-main');
        if (main) { main.setAttribute('aria-busy', 'true'); }
    }

    function reset() {
        var forms = document.querySelectorAll(SEL);
        for (var i = 0; i < forms.length; i++) {
            forms[i].removeAttribute('aria-busy');
            forms[i].classList.remove('is-loading');
            var btns = forms[i].querySelectorAll('.is-loading');
            for (var j = 0; j < btns.length; j++) {
                btns[j].classList.remove('is-loading');
                btns[j].removeAttribute('aria-disabled');
            }
        }
        if (region) { region.textContent = ''; }
        var main = document.getElementById('f-main');
        if (main) { main.removeAttribute('aria-busy'); }
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches(SEL)) { return; }
        if (form.getAttribute('aria-busy') === 'true') { e.preventDefault(); return; }
        busy(form);
    });
    // Back/forward cache restores the busy state otherwise.
    window.addEventListener('pageshow', reset);
})();
