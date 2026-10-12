/*
 * op-actions.js — the one place inline event handlers used to live.
 *
 * The Content-Security-Policy sends script-src WITHOUT 'unsafe-inline'
 * (app/Http/Middleware/AddSecurityHeaders.php). Inline on*= attributes can
 * never carry a nonce, so every former onclick= / onchange= / onsubmit= is
 * expressed as a data-* attribute and dispatched here by delegation:
 *
 *   data-confirm="…"            ask before the click / submit goes ahead
 *   data-call="fnName"          call window.fnName(...args) — the page's own
 *                               nonced <script> defines it; injected markup
 *                               cannot define new globals, so this is safe
 *   data-args='["a", 1]'        JSON array of arguments for data-call
 *   data-call-self              pass the element itself as the only argument
 *   data-prevent                preventDefault() before calling
 *   data-autosubmit             (change) submit the owning form
 *   data-call-change="fnName"   (change) call fn(this.value) — or fn(this)
 *                               when data-call-self is present
 *   data-toggle-hidden="id"     flip the [hidden] attribute on #id
 *   data-show="id"              #id.style.display = data-display || 'block'
 *   data-hide="id"              #id.style.display = 'none'
 *   data-add-class="id:cls"     #id.classList.add(cls)
 *   data-remove-class="id:cls"  #id.classList.remove(cls)
 *   data-scroll-to="id"         #id.scrollIntoView({behavior:'smooth'})
 *   data-click="id"             #id.click()  (e.g. open a hidden file input)
 *   data-select-on-click        select the element's own text (copy fields)
 *   data-submit-closest         submit the nearest <form> and swallow the click
 *   data-print                  window.print()
 */
(function () {
    'use strict';

    var SELECTOR = '[data-confirm],[data-call],[data-toggle-hidden],[data-show],[data-hide],' +
        '[data-add-class],[data-remove-class],[data-scroll-to],[data-click],[data-select-on-click],' +
        '[data-submit-closest],[data-print]';

    function byId(id) { return document.getElementById(id); }

    function parseArgs(el) {
        if (el.hasAttribute('data-call-self')) return [el];
        var raw = el.getAttribute('data-args');
        if (!raw) return [];
        try { var v = JSON.parse(raw); return Array.isArray(v) ? v : [v]; }
        catch (e) { return [raw]; }
    }

    function call(name, el, args) {
        var fn = window[name];
        if (typeof fn === 'function') return fn.apply(el, args);
        if (window.console) console.warn('op-actions: no function named ' + name);
    }

    function splitIdClass(v) { var i = v.indexOf(':'); return [v.slice(0, i), v.slice(i + 1)]; }

    document.addEventListener('click', function (e) {
        var el = e.target.closest(SELECTOR);
        if (!el) return;
        var ds = el.dataset;

        // Forms keep their own submit-time confirm (below); on buttons/links it runs here.
        if (ds.confirm !== undefined && el.tagName !== 'FORM' && !window.confirm(ds.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        if (ds.prevent !== undefined || ds.submitClosest !== undefined || ds.print !== undefined) e.preventDefault();

        if (ds.call) call(ds.call, el, parseArgs(el));
        if (ds.toggleHidden) { var t = byId(ds.toggleHidden); if (t) t.hidden = !t.hidden; }
        if (ds.show) { var s = byId(ds.show); if (s) s.style.display = ds.display || 'block'; }
        if (ds.hide) { var h = byId(ds.hide); if (h) h.style.display = 'none'; }
        if (ds.addClass) { var a = splitIdClass(ds.addClass), ae = byId(a[0]); if (ae) ae.classList.add(a[1]); }
        if (ds.removeClass) { var r = splitIdClass(ds.removeClass), re = byId(r[0]); if (re) re.classList.remove(r[1]); }
        if (ds.scrollTo) { var sc = byId(ds.scrollTo); if (sc) sc.scrollIntoView({ behavior: 'smooth' }); }
        if (ds.click) { var c = byId(ds.click); if (c) c.click(); }
        if (ds.selectOnClick !== undefined && typeof el.select === 'function') el.select();
        if (ds.submitClosest !== undefined) { var f = el.closest('form'); if (f) f.submit(); }
        if (ds.print !== undefined) window.print();
    });

    document.addEventListener('change', function (e) {
        var el = e.target;
        if (!(el instanceof Element)) return;
        if (el.hasAttribute('data-autosubmit') && el.form) el.form.submit();
        var fn = el.getAttribute('data-call-change');
        if (fn) call(fn, el, el.hasAttribute('data-call-self') ? [el] : [el.value]);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        var msg = form.getAttribute('data-confirm');
        if (msg !== null && !window.confirm(msg)) e.preventDefault();
    });
})();
