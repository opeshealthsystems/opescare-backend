/* /download — copy the SHA-256 fingerprint, and drive the "Inside the app"
   phone walk-throughs. External file: CSP is nonce-only, no inline handlers. */
(function () {
    'use strict';

    function initCopy() {
        var btn = document.querySelector('[data-dl-copy]');
        if (!btn || !navigator.clipboard || !navigator.clipboard.writeText) { return; }
        var target = document.getElementById(btn.getAttribute('data-dl-copy'));
        var label = btn.querySelector('span');
        if (!target || !label) { return; }
        var original = label.textContent;
        btn.hidden = false;
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(target.textContent.trim()).then(function () {
                label.textContent = btn.getAttribute('data-copied') || original;
                setTimeout(function () { label.textContent = original; }, 2000);
            }, function () {});
        });
    }

    /* Each flow is a list of .dl-scr screens with a data-ms duration. One timer
       advances screen by screen, then to the next flow. Paused while the section
       is off-screen, the tab is hidden, or the visitor pressed Pause. Reduced
       motion: no autoplay; picking a flow shows its first screen, static. */
    function initFlows() {
        var root = document.querySelector('[data-dl-flows]');
        if (!root) { return; }
        var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
        var panels = tabs.map(function (t) { return document.getElementById(t.getAttribute('aria-controls')); });
        if (!tabs.length || panels.indexOf(null) !== -1) { return; }
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var pp = root.querySelector('[data-dl-pp]');
        var flow = 0, step = 0, timer = null, left = 0, startedAt = 0;
        var inView = false, userPaused = false;

        function screens(i) { return Array.prototype.slice.call(panels[i].querySelectorAll('.dl-scr')); }
        function ms(el) { return parseInt(el.getAttribute('data-ms'), 10) || 3000; }

        function restartBar() {
            var bar = tabs[flow].querySelector('.dl-fx__bar i');
            if (!bar || reduce) { return; }
            var total = screens(flow).reduce(function (a, s) { return a + ms(s); }, 0);
            bar.classList.remove('is-run');
            void bar.offsetWidth; // restart the CSS animation
            bar.style.setProperty('--dur', total + 'ms');
            bar.classList.add('is-run');
        }

        function showStep(i) {
            var list = screens(flow);
            list.forEach(function (s, n) {
                s.classList.remove('is-on');
                if (n === i) { void s.offsetWidth; s.classList.add('is-on'); }
            });
            step = i;
            left = ms(list[i]);
        }

        function select(i, focus) {
            tabs.forEach(function (t, n) {
                var on = n === i;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                panels[n].hidden = !on;
                var bar = t.querySelector('.dl-fx__bar i');
                if (bar) { bar.classList.remove('is-run'); }
            });
            flow = i;
            if (focus) { tabs[i].focus(); }
            showStep(0);
            restartBar();
            schedule();
        }

        function running() { return !reduce && inView && !userPaused && !document.hidden; }

        function tick() {
            timer = null;
            var list = screens(flow);
            if (step + 1 < list.length) { showStep(step + 1); schedule(); }
            else { select((flow + 1) % tabs.length, false); }
        }

        function schedule() {
            clearTimeout(timer); timer = null;
            root.classList.toggle('is-paused', !running());
            if (!running()) { return; }
            startedAt = Date.now();
            timer = setTimeout(tick, left);
        }

        function pause() {
            if (timer) { clearTimeout(timer); timer = null; left = Math.max(300, left - (Date.now() - startedAt)); }
            root.classList.add('is-paused');
        }

        function update() { if (running()) { if (!timer) { schedule(); } } else { pause(); } }

        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { select(i, false); });
            t.addEventListener('keydown', function (e) {
                var k = e.key, n = null;
                if (k === 'ArrowRight' || k === 'ArrowDown') { n = (i + 1) % tabs.length; }
                else if (k === 'ArrowLeft' || k === 'ArrowUp') { n = (i - 1 + tabs.length) % tabs.length; }
                else if (k === 'Home') { n = 0; } else if (k === 'End') { n = tabs.length - 1; }
                if (n !== null) { e.preventDefault(); select(n, true); }
            });
        });

        if (pp && !reduce) {
            pp.hidden = false;
            pp.addEventListener('click', function () {
                userPaused = !userPaused;
                pp.textContent = userPaused ? pp.getAttribute('data-play') : pp.getAttribute('data-pause');
                pp.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
                update();
            });
        }

        document.addEventListener('visibilitychange', update);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                inView = entries[0].isIntersecting;
                update();
            }, { threshold: 0.25 }).observe(root);
        } else {
            inView = true;
        }

        left = ms(screens(0)[0]);
        restartBar();
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCopy();
        initFlows();
    });
})();
