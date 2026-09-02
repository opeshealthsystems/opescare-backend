/**
 * Auth pages behaviour — CSP-safe (no inline scripts / handlers).
 *
 * The app sends a strict Content-Security-Policy (`script-src 'self'` with no
 * 'unsafe-inline'), so inline <script> blocks and onclick="" attributes are
 * blocked by the browser. This external file is served from 'self' and wires
 * everything up with addEventListener instead.
 *
 * Responsibilities:
 *   - render Lucide icons (replaces <i data-lucide> with <svg>)
 *   - password show/hide toggle        [data-toggle-password="<inputId>"]
 *   - demo panel collapse              [data-demo-panel-toggle]
 *   - demo tab switching               [data-demo-tab="<tab>"]
 *   - demo one-click login             [data-demo-role][data-demo-email]
 */
(function () {
    'use strict';

    function renderIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        renderIcons();

        // ── Password show/hide ────────────────────────────────────────────
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-toggle-password');
                var input = document.getElementById(id);
                var icon = document.getElementById(id + '-toggle-icon');
                if (!input) return;
                var reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                if (icon) {
                    icon.setAttribute('data-lucide', reveal ? 'eye-off' : 'eye');
                    renderIcons();
                }
            });
        });

        // ── Demo panel collapse ───────────────────────────────────────────
        var panelToggle = document.querySelector('[data-demo-panel-toggle]');
        if (panelToggle) {
            panelToggle.addEventListener('click', function () {
                var body = document.getElementById('demoPanelBody');
                var header = document.getElementById('demoPanelHeader');
                var chevron = document.getElementById('demoPanelChevron');
                if (!body) return;
                var open = body.classList.toggle('open');
                if (header) header.classList.toggle('open', open);
                if (chevron) {
                    chevron.setAttribute('data-lucide', open ? 'chevron-up' : 'chevron-down');
                    renderIcons();
                }
            });
        }

        // ── Demo tab switching ────────────────────────────────────────────
        document.querySelectorAll('[data-demo-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-demo-tab');
                document.querySelectorAll('.demo-tab-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.demo-tab-pane').forEach(function (p) { p.classList.remove('active'); });
                var pane = document.getElementById('demo-tab-' + tab);
                if (pane) pane.classList.add('active');
            });
        });

        // ── Demo one-click login ──────────────────────────────────────────
        document.querySelectorAll('[data-demo-role]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var roleInput = document.getElementById('demoRoleInput');
                var emailInput = document.getElementById('demoEmailInput');
                var form = document.getElementById('demoLoginForm');
                if (roleInput) roleInput.value = btn.getAttribute('data-demo-role') || '';
                if (emailInput) emailInput.value = btn.getAttribute('data-demo-email') || '';
                if (form) form.submit();
            });
        });
    });

    // Belt-and-suspenders: re-render icons after full load.
    window.addEventListener('load', renderIcons);
})();
