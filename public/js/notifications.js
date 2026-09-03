/*
 * OpesCare — topbar notification bell.
 *
 * Vanilla JS, no dependencies. Polls the notification feed, renders the
 * dropdown list, marks-all-read on open, and raises two signals for other
 * layers to react to genuinely NEW events:
 *
 *   - window CustomEvent 'opescare:new-notification'
 *         detail = { event: <feed event object>, unread: <int> }
 *     (the sound layer, notification-sounds.js, listens for this)
 *   - a browser push Notification (only after the user has opened the bell
 *     once and granted permission)
 *
 * Reads its config from #notif-bell data-* attributes:
 *   data-feed-url, data-read-url, data-center-url, data-csrf, data-i18n(JSON)
 *
 * Feed contract (NotificationCenterController::feed):
 *   { unread:int, events:[ { id, event_type, category, priority,
 *                            title, body, url, created_at, is_new } ] }
 *   title / body / url may be null.
 */
(function () {
    'use strict';

    var POLL_INTERVAL_MS = 25000;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        var root = document.getElementById('notif-bell');
        if (!root) { return; }

        var feedUrl   = root.getAttribute('data-feed-url');
        var readUrl   = root.getAttribute('data-read-url');
        var centerUrl = root.getAttribute('data-center-url'); // link handled in Blade
        var csrf      = root.getAttribute('data-csrf') || '';

        var i18n = {};
        try { i18n = JSON.parse(root.getAttribute('data-i18n') || '{}') || {}; }
        catch (e) { i18n = {}; }
        var cats = i18n.categories || {};

        var btn     = document.getElementById('notif-bell-btn');
        var panel   = document.getElementById('notif-panel');
        var badge   = document.getElementById('notif-badge');
        var list    = document.getElementById('notif-list');
        var markBtn = document.getElementById('notif-mark-read');

        if (!btn || !panel || !badge || !list) { return; }

        var seenIds        = Object.create(null); // ids observed across polls (Set-like)
        var firstPoll      = true;
        var isOpen         = false;
        var permissionAsked = false;

        // ------------------------------------------------------------------
        // Polling
        // ------------------------------------------------------------------
        function poll() {
            fetch(feedUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
                .then(function (data) {
                    if (!data || typeof data !== 'object') { return; }
                    var unread = parseInt(data.unread, 10) || 0;
                    var events = Array.isArray(data.events) ? data.events : [];

                    if (!isOpen) { updateBadge(unread); }
                    renderList(events);
                    detectNew(events, unread);
                    firstPoll = false;
                })
                .catch(function () {
                    // Fetch failure: keep last known state silently.
                });
        }

        // Emit 'opescare:new-notification' (and a browser push) for every event
        // that is flagged is_new AND is being seen for the first time. Never on
        // the very first poll — that would replay history as "new".
        function detectNew(events, unread) {
            var fresh = [];
            for (var i = 0; i < events.length; i++) {
                var ev = events[i];
                if (!ev || ev.id === null || ev.id === undefined) { continue; }
                var id = String(ev.id);
                var newlySeen = !seenIds[id];
                seenIds[id] = true;
                if (!firstPoll && newlySeen && ev.is_new) { fresh.push(ev); }
            }
            if (firstPoll) { return; }
            for (var j = 0; j < fresh.length; j++) {
                var e = fresh[j];
                try {
                    window.dispatchEvent(new CustomEvent('opescare:new-notification', {
                        detail: { event: e, unread: unread }
                    }));
                } catch (err) { /* CustomEvent unsupported — ignore */ }
                maybeBrowserNotify(e);
            }
        }

        // ------------------------------------------------------------------
        // Rendering
        // ------------------------------------------------------------------
        function updateBadge(n) {
            var count = parseInt(n, 10) || 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = false;
                root.classList.add('has-unread');
            } else {
                badge.hidden = true;
                root.classList.remove('has-unread');
            }
        }

        function renderList(events) {
            if (!events || !events.length) {
                list.innerHTML =
                    '<div class="notif-empty">' +
                        '<div class="notif-empty-title">' + esc(i18n.empty || 'No notifications') + '</div>' +
                        '<div class="notif-empty-desc">' + esc(i18n.empty_desc || '') + '</div>' +
                    '</div>';
                return;
            }
            var html = '';
            for (var i = 0; i < events.length; i++) {
                html += renderItem(events[i]);
            }
            list.innerHTML = html;
            // Render any lucide icons injected into the list.
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function renderItem(ev) {
            if (!ev) { return ''; }
            var title = ev.title || humanize(ev.event_type);
            var body  = ev.body || '';
            var label = categoryLabel(ev.category);
            var time  = relativeTime(ev.created_at);
            var isNew = !!ev.is_new;
            var url   = safeUrl(ev.url);

            var tag      = url ? 'a' : 'div';
            var hrefAttr = url ? ' href="' + esc(url) + '"' : '';
            var catClass = 'notif-cat-' + esc(String(ev.category || 'general')).replace(/[^a-z0-9_-]/gi, '');

            return '<' + tag + ' class="notif-item' + (isNew ? ' is-new' : '') + '"' + hrefAttr + ' role="listitem">' +
                '<div class="notif-item-row">' +
                    '<span class="notif-cat ' + catClass + '">' + esc(label) + '</span>' +
                    (isNew ? '<span class="notif-new-dot" title="' + esc(i18n.new_badge || 'New') + '"></span>' : '') +
                    '<span class="notif-time">' + esc(time) + '</span>' +
                '</div>' +
                '<div class="notif-item-title">' + esc(title) + '</div>' +
                (body ? '<div class="notif-item-body">' + esc(body) + '</div>' : '') +
            '</' + tag + '>';
        }

        function categoryLabel(cat) {
            if (cat && cats[cat]) { return cats[cat]; }
            if (!cat) { return cats.general || 'Update'; }
            return humanize(cat);
        }

        // "blood_order_created" -> "Blood order created"
        function humanize(s) {
            if (!s) { return ''; }
            var t = String(s).replace(/[_\-.]+/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
            return t.charAt(0).toUpperCase() + t.slice(1);
        }

        function relativeTime(ts) {
            if (!ts) { return ''; }
            var then = parseDate(ts);
            if (!then) { return ''; }
            var diff = (Date.now() - then.getTime()) / 1000;
            if (diff < 0) { diff = 0; }
            if (diff < 60)    { return i18n.just_now || 'Just now'; }
            if (diff < 3600)  { return Math.floor(diff / 60) + 'm'; }
            if (diff < 86400) { return Math.floor(diff / 3600) + 'h'; }
            try {
                var locale = document.documentElement.getAttribute('lang') || undefined;
                return then.toLocaleDateString(locale, { month: 'short', day: 'numeric' });
            } catch (e) {
                return then.toLocaleDateString();
            }
        }

        function parseDate(ts) {
            var d = new Date(ts);
            if (!isNaN(d.getTime())) { return d; }
            // Postgres "YYYY-MM-DD HH:MM:SS" -> ISO-ish
            d = new Date(String(ts).replace(' ', 'T'));
            return isNaN(d.getTime()) ? null : d;
        }

        // ------------------------------------------------------------------
        // Open / close
        // ------------------------------------------------------------------
        function openPanel() {
            if (isOpen) { return; }
            isOpen = true;
            panel.hidden = false;
            root.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');

            markAllRead();
            updateBadge(0);
            requestPushPermissionOnce();

            document.addEventListener('click', onOutside, true);
            document.addEventListener('keydown', onKey, true);
        }

        function closePanel() {
            if (!isOpen) { return; }
            isOpen = false;
            panel.hidden = true;
            root.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');

            document.removeEventListener('click', onOutside, true);
            document.removeEventListener('keydown', onKey, true);
        }

        function togglePanel() { isOpen ? closePanel() : openPanel(); }

        function onOutside(e) {
            if (!root.contains(e.target)) { closePanel(); }
        }
        function onKey(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                closePanel();
                if (btn && typeof btn.focus === 'function') { btn.focus(); }
            }
        }

        function markAllRead() {
            if (!readUrl) { return; }
            fetch(readUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).catch(function () { /* silent */ });
        }

        // ------------------------------------------------------------------
        // Browser push
        // ------------------------------------------------------------------
        // Only ever ask on the first bell interaction (a user gesture), never
        // on page load.
        function requestPushPermissionOnce() {
            if (permissionAsked) { return; }
            permissionAsked = true;
            if (!('Notification' in window)) { return; }
            if (window.Notification.permission === 'default') {
                try { window.Notification.requestPermission(); } catch (e) { /* ignore */ }
            }
        }

        function maybeBrowserNotify(ev) {
            if (!('Notification' in window)) { return; }
            if (window.Notification.permission !== 'granted') { return; }
            var title = ev.title || humanize(ev.event_type) || (i18n.title || 'Notification');
            var url   = safeUrl(ev.url);
            try {
                var n = new window.Notification(title, {
                    body: ev.body || '',
                    tag: String(ev.id),
                    icon: '/favicon.ico'
                });
                n.onclick = function () {
                    try { window.focus(); } catch (e) { /* ignore */ }
                    if (url) { window.location.href = url; }
                    n.close();
                };
            } catch (e) { /* ignore */ }
        }

        // ------------------------------------------------------------------
        // Helpers
        // ------------------------------------------------------------------
        function esc(s) {
            return String(s === null || s === undefined ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Only allow http(s) and same-origin relative URLs — blocks javascript:
        // and other schemes from the (server-controlled, but be safe) payload.
        function safeUrl(u) {
            if (!u) { return null; }
            var s = String(u).trim();
            if (!s) { return null; }
            if (/^https?:\/\//i.test(s)) { return s; }
            if (s.charAt(0) === '/' && s.charAt(1) !== '/') { return s; }
            return null;
        }

        // ------------------------------------------------------------------
        // Wire up
        // ------------------------------------------------------------------
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            togglePanel();
        });

        if (markBtn) {
            markBtn.addEventListener('click', function (e) {
                e.preventDefault();
                markAllRead();
                updateBadge(0);
                var items = list.querySelectorAll('.notif-item.is-new');
                for (var i = 0; i < items.length; i++) {
                    items[i].classList.remove('is-new');
                }
            });
        }

        // Kick off: immediate poll, then every 25s.
        poll();
        setInterval(poll, POLL_INTERVAL_MS);
    }
})();
