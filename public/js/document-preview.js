(function () {
    'use strict';

    var currentSlug = null;
    var currentUrl  = null;

    function loadDoc(slug, name, code, color, built) {
        currentSlug = slug;
        currentUrl  = '/document-preview/' + slug;

        // Sidebar active state
        document.querySelectorAll('.doc-item').forEach(function (el) {
            el.classList.remove('active');
        });
        var item = document.getElementById('item-' + slug);
        if (item) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        // Toolbar
        document.getElementById('toolbarTitle').textContent = name;

        var badge = document.getElementById('toolbarBadge');
        badge.textContent = code;
        badge.style.display = 'inline-flex';
        badge.style.background = color + '22';
        badge.style.color = color;
        badge.style.border = '1px solid ' + color + '44';

        document.getElementById('btnOpenFull').style.display = 'flex';
        document.getElementById('btnPrint').style.display  = 'flex';

        document.getElementById('statusText').textContent = 'Loading ' + name + '…';

        // Hide welcome, show loading placeholder in iframe
        document.getElementById('welcomeState').style.display = 'none';
        var frame = document.getElementById('docFrame');
        frame.style.display = 'block';
        frame.srcdoc = '<!DOCTYPE html><html><head><style>'
            + '*{margin:0;padding:0;box-sizing:border-box;}'
            + 'body{display:flex;flex-direction:column;align-items:center;justify-content:center;'
            + 'min-height:100vh;font-family:Inter,system-ui,sans-serif;background:#F8FAFC;color:#64748B;}'
            + '.sp{width:36px;height:36px;border:3px solid #E2E8F0;border-top-color:' + color + ';'
            + 'border-radius:50%;animation:spin .7s linear infinite;margin-bottom:14px;}'
            + '@keyframes spin{to{transform:rotate(360deg)}}'
            + 'p{font-size:13px;font-weight:500;}'
            + '</style></head><body>'
            + '<div class="sp"></div><p>Loading ' + name + '…</p>'
            + '</body></html>';

        // Fetch HTML and inject via srcdoc (bypasses X-Frame-Options & frame-ancestors)
        fetch(currentUrl, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function (html) {
                frame.srcdoc = html;
                document.getElementById('statusText').textContent =
                    name + ' · ' + (built ? '✓ Built Template' : '◈ Preview Template');
            })
            .catch(function (err) {
                frame.srcdoc = '<!DOCTYPE html><html><head><style>'
                    + 'body{display:flex;flex-direction:column;align-items:center;justify-content:center;'
                    + 'min-height:100vh;font-family:Inter,sans-serif;background:#FEF2F2;color:#B91C1C;text-align:center;padding:40px;}'
                    + 'h3{font-size:16px;margin-bottom:8px;}p{font-size:12px;opacity:.7;}'
                    + '</style></head><body>'
                    + '<h3>Could not load template</h3>'
                    + '<p>' + err.message + ' — try the Open Full Page button</p>'
                    + '</body></html>';
                document.getElementById('statusText').textContent = 'Error loading ' + name;
            });
    }

    function openFull() {
        if (currentUrl) window.open(currentUrl, '_blank');
    }

    function printDoc() {
        var frame = document.getElementById('docFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }

    function filterDocs(query) {
        var q = query.toLowerCase().trim();
        document.querySelectorAll('.doc-item').forEach(function (el) {
            var name = el.querySelector('.doc-name').textContent.toLowerCase();
            var code = el.querySelector('.badge-code').textContent.toLowerCase();
            el.style.display = (!q || name.includes(q) || code.includes(q)) ? 'flex' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Wire sidebar doc items via data attributes (no inline onclick)
        document.querySelectorAll('.doc-item[data-slug]').forEach(function (el) {
            el.addEventListener('click', function () {
                loadDoc(
                    el.dataset.slug,
                    el.dataset.name,
                    el.dataset.code,
                    el.dataset.color,
                    el.dataset.built === 'true'
                );
            });
        });

        // Wire welcome-grid quick-launch cards
        document.querySelectorAll('.welcome-card[data-slug]').forEach(function (el) {
            el.addEventListener('click', function () {
                loadDoc(
                    el.dataset.slug,
                    el.dataset.name,
                    el.dataset.code,
                    el.dataset.color,
                    el.dataset.built === 'true'
                );
            });
        });

        // Toolbar buttons
        var btnOpen = document.getElementById('btnOpenFull');
        if (btnOpen) btnOpen.addEventListener('click', openFull);

        var btnPrint = document.getElementById('btnPrint');
        if (btnPrint) btnPrint.addEventListener('click', printDoc);

        // Search
        var searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.addEventListener('input', function () {
            filterDocs(this.value);
        });

        // Auto-load first doc
        var firstItem = document.querySelector('.doc-item[data-slug]');
        if (firstItem) firstItem.click();
    });
}());
