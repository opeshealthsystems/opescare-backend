{{--
    Topbar notification bell + dropdown.

    Rendered inside layouts/portal.blade.php > .topbar-actions via
    @includeIf('partials.notifications.bell'). The polling / render / sound-signal
    logic lives in public/js/notifications.js, which reads the data-* attributes
    on #notif-bell below. This partial only renders markup + scoped styles.

    Backend contract (see NotificationCenterController):
      GET  notifications.feed   -> { unread:int, events:[{id,event_type,category,priority,title,body,url,created_at,is_new}] }
      POST notifications.read   -> { ok:true }  (marks all read; CSRF via X-CSRF-TOKEN)
      GET  notifications.center -> full page ("view all")
--}}
@php
    $__loc = app()->getLocale();
    // Strings the JS needs are handed over as a JSON blob so notifications.js
    // stays a static, framework-free file (no Blade / no hardcoded English).
    $notifI18n = [
        'loading'    => __('notifications_ui.loading', [], $__loc) ?: 'Loading…',
        'empty'      => __('notifications_ui.empty', [], $__loc) ?: 'You have no notifications yet.',
        'empty_desc' => __('notifications_ui.empty_desc', [], $__loc) ?: 'Orders, alerts and updates will appear here.',
        'just_now'   => __('notifications_ui.just_now', [], $__loc) ?: 'Just now',
        'new_badge'  => __('notifications_ui.new_badge', [], $__loc) ?: 'New',
        'title'      => __('notifications_ui.title', [], $__loc) ?: 'Notifications',
        'categories' => [
            'blood'       => __('notifications_ui.category_blood', [], $__loc) ?: 'Blood',
            'pharmacy'    => __('notifications_ui.category_pharmacy', [], $__loc) ?: 'Pharmacy',
            'appointment' => __('notifications_ui.category_appointment', [], $__loc) ?: 'Appointment',
            'account'     => __('notifications_ui.category_account', [], $__loc) ?: 'Account',
            'admin'       => __('notifications_ui.category_admin', [], $__loc) ?: 'Admin',
            'general'     => __('notifications_ui.category_general', [], $__loc) ?: 'Update',
        ],
    ];
@endphp

<div class="notif-bell" id="notif-bell"
     data-feed-url="{{ route('notifications.feed') }}"
     data-read-url="{{ route('notifications.read') }}"
     data-center-url="{{ route('notifications.center') }}"
     data-csrf="{{ csrf_token() }}"
     data-i18n="{{ json_encode($notifI18n, JSON_UNESCAPED_UNICODE) }}">

    <button type="button" class="topbar-icon-btn notif-bell-btn" id="notif-bell-btn"
            aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel"
            aria-label="{{ __('notifications_ui.bell_label', [], $__loc) ?: 'Notifications' }}">
        <i data-lucide="bell" aria-hidden="true"></i>
        <span class="notif-badge" id="notif-badge" aria-live="polite" hidden>0</span>
    </button>

    <div class="notif-panel" id="notif-panel" role="dialog" aria-modal="false"
         aria-label="{{ __('notifications_ui.title', [], $__loc) ?: 'Notifications' }}" hidden>

        <div class="notif-panel-header">
            <span class="notif-panel-title">{{ __('notifications_ui.title', [], $__loc) ?: 'Notifications' }}</span>
        </div>

        <div class="notif-list" id="notif-list" role="list">
            <div class="notif-loading">{{ __('notifications_ui.loading', [], $__loc) ?: 'Loading…' }}</div>
        </div>

        <div class="notif-panel-footer">
            <button type="button" class="notif-foot-btn" id="notif-mark-read">
                {{ __('notifications_ui.mark_all_read', [], $__loc) ?: 'Mark all as read' }}
            </button>
            <a href="{{ route('notifications.center') }}" class="notif-foot-link">
                {{ __('notifications_ui.view_all', [], $__loc) ?: 'View all notifications' }}
            </a>
            <button type="button" class="notif-foot-btn notif-sound-toggle" data-notif-sound-toggle
                    aria-label="{{ __('notifications_ui.sound_alerts', [], $__loc) ?: 'Sound alerts' }}">
                <i data-lucide="volume-2" aria-hidden="true"></i>
                <span>{{ __('notifications_ui.sound_alerts', [], $__loc) ?: 'Sound alerts' }}</span>
            </button>
        </div>
    </div>
</div>

<style>
    /* Scoped to .notif-* — reuses portal.css --p-* tokens; brand navy #0F4C81. */
    #notif-bell.notif-bell { position: relative; display: inline-flex; }

    .notif-bell-btn { position: relative; }

    .notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--p-danger, #B91C1C);
        color: #fff;
        font-size: 0.625rem;
        font-weight: 700;
        line-height: 1;
        border-radius: 9999px;
        border: 2px solid var(--p-topbar-bg, #fff);
        box-sizing: content-box;
        pointer-events: none;
    }
    .notif-badge[hidden] { display: none; }

    .notif-panel {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        width: 360px;
        max-width: calc(100vw - 1.5rem);
        background: var(--p-surface, #fff);
        border: 1px solid var(--p-border, #E2E8F0);
        border-radius: var(--p-radius-lg, 0.75rem);
        box-shadow: var(--p-shadow-lg, 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05));
        z-index: 70;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .notif-panel[hidden] { display: none; }

    .notif-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--p-border, #E2E8F0);
    }
    .notif-panel-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--p-text, #0F172A);
    }

    .notif-list {
        max-height: 380px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .notif-loading,
    .notif-empty {
        padding: 1.5rem 1rem;
        text-align: center;
        color: var(--p-text-muted, #64748B);
        font-size: 0.8125rem;
    }
    .notif-empty-title {
        font-weight: 600;
        color: var(--p-text-2, #334155);
        margin-bottom: 0.25rem;
    }

    .notif-item {
        display: block;
        text-decoration: none;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--p-border, #E2E8F0);
        color: var(--p-text, #0F172A);
        cursor: default;
    }
    a.notif-item { cursor: pointer; }
    a.notif-item:hover { background: var(--p-surface-2, #F1F5F9); }
    .notif-item:last-child { border-bottom: none; }
    .notif-item.is-new { background: rgba(15, 76, 129, 0.05); }
    a.notif-item.is-new:hover { background: rgba(15, 76, 129, 0.09); }

    .notif-item-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }
    .notif-cat {
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--p-primary, #0F4C81);
        background: rgba(15, 76, 129, 0.08);
        padding: 0.05rem 0.4rem;
        border-radius: 9999px;
        white-space: nowrap;
    }
    .notif-new-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--p-danger, #B91C1C);
        flex-shrink: 0;
    }
    .notif-time {
        margin-left: auto;
        font-size: 0.6875rem;
        color: var(--p-text-muted, #64748B);
        white-space: nowrap;
    }
    .notif-item-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--p-text, #0F172A);
        line-height: 1.35;
    }
    .notif-item-body {
        font-size: 0.75rem;
        color: var(--p-text-muted, #64748B);
        margin-top: 0.15rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notif-panel-footer {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        padding: 0.5rem 0.75rem;
        border-top: 1px solid var(--p-border, #E2E8F0);
    }
    .notif-foot-btn,
    .notif-foot-link {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--p-text-muted, #64748B);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.3rem 0.4rem;
        border-radius: var(--p-radius-sm, 0.375rem);
        text-decoration: none;
    }
    .notif-foot-link { color: var(--p-primary, #0F4C81); }
    .notif-foot-btn:hover,
    .notif-foot-link:hover {
        color: var(--p-primary, #0F4C81);
        background: var(--p-surface-2, #F1F5F9);
    }
    .notif-sound-toggle { margin-left: auto; }
    .notif-sound-toggle i,
    .notif-sound-toggle [data-lucide] { width: 0.875rem; height: 0.875rem; }

    /* Mobile: dropdown becomes a full-width sheet under the topbar. */
    @media (max-width: 640px) {
        .notif-panel {
            position: fixed;
            top: var(--p-topbar-h, 60px);
            left: 0;
            right: 0;
            width: 100%;
            max-width: 100%;
            border-left: none;
            border-right: none;
            border-radius: 0;
            max-height: calc(100vh - var(--p-topbar-h, 60px));
        }
        .notif-list { max-height: 60vh; }
    }
</style>
