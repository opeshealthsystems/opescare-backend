/*
 * Explainer pages — step-through flow (resources/views/public/explainer/_flow.blade.php).
 *
 * External and nonce-tagged (CSP script-src is nonce-only); no inline handlers.
 * Without this script, or with prefers-reduced-motion, every step stays fully
 * visible and the controls stay hidden. With it, the flow advances on a timer
 * only while it is on screen (IntersectionObserver) and the tab is visible,
 * and the viewer can go back, forward, or pause.
 */
(function () {
    'use strict';

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) {
        return;
    }

    var DELAY = 3400;

    Array.prototype.forEach.call(document.querySelectorAll('[data-ex-flow]'), function (root) {
        var steps = Array.prototype.slice.call(root.querySelectorAll('[data-ex-step]'));
        if (steps.length < 2) {
            return;
        }

        var controls = root.querySelector('[data-ex-controls]');
        var count = root.querySelector('[data-ex-count]');
        var toggle = root.querySelector('[data-ex-toggle]');
        var prev = root.querySelector('[data-ex-prev]');
        var next = root.querySelector('[data-ex-next]');
        var bar = root.querySelector('[data-ex-bar]');

        var at = 0;
        var timer = null;
        var onScreen = false;
        var paused = false;

        function show(n) {
            at = (n + steps.length) % steps.length;
            steps.forEach(function (s, k) {
                s.classList.toggle('is-on', k === at);
                s.classList.toggle('is-done', k < at);
                if (k === at) {
                    s.setAttribute('aria-current', 'step');
                } else {
                    s.removeAttribute('aria-current');
                }
            });
            if (bar) {
                bar.style.setProperty('--ex-p', String((at + 1) / steps.length));
            }
            if (count) {
                count.textContent = (count.getAttribute('data-tpl') || '')
                    .replace(':n', String(at + 1))
                    .replace(':total', String(steps.length));
            }
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (!paused && onScreen && !document.hidden) {
                timer = window.setInterval(function () { show(at + 1); }, DELAY);
            }
        }

        function setPaused(p) {
            paused = p;
            if (toggle) {
                toggle.setAttribute('aria-pressed', p ? 'true' : 'false');
                toggle.textContent = toggle.getAttribute(p ? 'data-play' : 'data-pause') || '';
            }
            start();
        }

        root.classList.add('is-anim');
        if (controls) {
            controls.hidden = false;
        }
        show(0);

        if (toggle) {
            toggle.addEventListener('click', function () { setPaused(!paused); });
        }
        if (prev) {
            prev.addEventListener('click', function () { setPaused(true); show(at - 1); });
        }
        if (next) {
            next.addEventListener('click', function () { setPaused(true); show(at + 1); });
        }

        document.addEventListener('visibilitychange', start);

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    onScreen = e.isIntersecting;
                });
                start();
            }, { threshold: 0, rootMargin: '-15% 0px -15% 0px' }).observe(root);
        } else {
            onScreen = true;
            start();
        }
    });
})();
