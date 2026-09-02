<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F4C81">
    <title>@yield('title', 'OpesCare Lite')</title>
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/helix-v2.css') }}">
    <style>
        /* OpesCare Lite — simplified, large-touch UI overrides */
        :root {
            --lite-primary:   #0F4C81;
            --lite-accent:    #7c3aed;
            --lite-success:   #16a34a;
            --lite-warning:   #d97706;
            --lite-danger:    #dc2626;
            --lite-bg:        #f8fafc;
            --lite-card-bg:   #ffffff;
            --lite-border:    #e2e8f0;
            --lite-text:      #1e293b;
            --lite-muted:     #64748b;
        }
        body { background: var(--lite-bg); font-family: 'Inter', sans-serif; margin: 0; }
        .lite-shell { display: flex; flex-direction: column; min-height: 100vh; }

        /* Top bar */
        .lite-topbar {
            background: var(--lite-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 56px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .lite-topbar__brand { font-weight: 800; font-size: 1.1rem; letter-spacing: -0.3px; }
        .lite-topbar__badge {
            font-size: 0.68rem; background: rgba(255,255,255,0.15);
            padding: 2px 8px; border-radius: 20px; margin-left: 8px;
        }
        .lite-topbar__right { display: flex; align-items: center; gap: 8px; }

        /* Offline/sync indicator */
        .lite-status-bar {
            background: #fff7ed;
            border-bottom: 1px solid #fed7aa;
            padding: 4px 16px;
            font-size: 0.78rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .lite-status-bar.online { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
        .lite-status-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block; }

        /* Bottom nav (mobile-first) */
        .lite-bottomnav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 1px solid var(--lite-border);
            display: flex;
            z-index: 100;
        }
        .lite-bottomnav__item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 4px;
            text-decoration: none;
            color: var(--lite-muted);
            font-size: 0.65rem;
            font-weight: 500;
            gap: 3px;
            border-top: 3px solid transparent;
            transition: color .15s;
        }
        .lite-bottomnav__item.active { color: var(--lite-primary); border-color: var(--lite-primary); }
        .lite-bottomnav__item i { width: 20px; height: 20px; }

        /* Main content area */
        .lite-main {
            flex: 1;
            padding: 16px;
            padding-bottom: 80px; /* room for bottom nav */
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        /* Big-button cards */
        .lite-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .lite-btn-card {
            background: var(--lite-card-bg);
            border: 1px solid var(--lite-border);
            border-radius: 12px;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--lite-text);
            font-weight: 600;
            font-size: 0.88rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            transition: transform .1s, box-shadow .1s;
        }
        .lite-btn-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .lite-btn-card__icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .lite-btn-card__icon i { width: 24px; height: 24px; }

        /* Section headings */
        .lite-section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--lite-muted);
            margin: 20px 0 10px;
        }

        /* Stat chips */
        .lite-stat-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .lite-stat-chip {
            background: #fff;
            border: 1px solid var(--lite-border);
            border-radius: 10px;
            padding: 10px 14px;
            min-width: 90px;
            text-align: center;
        }
        .lite-stat-chip__val { font-size: 1.5rem; font-weight: 800; color: var(--lite-primary); line-height: 1; }
        .lite-stat-chip__label { font-size: 0.68rem; color: var(--lite-muted); margin-top: 2px; }
        .lite-stat-chip--warning .lite-stat-chip__val { color: var(--lite-warning); }
        .lite-stat-chip--success .lite-stat-chip__val { color: var(--lite-success); }
        .lite-stat-chip--danger  .lite-stat-chip__val { color: var(--lite-danger); }

        /* Layout + content helpers (replace per-view inline styles) */
        .lite-page-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        .lite-btn--sm { padding: 6px 12px; font-size: 0.78rem; }
        .lite-btn--xs { padding: 4px 10px; font-size: 0.75rem; }
        .lite-btn--icon { padding: 0; background: none; border: none; cursor: pointer; color: var(--lite-danger); }
        .lite-row { display: flex; gap: 8px; align-items: center; }
        .lite-row--end { justify-content: flex-end; }
        .lite-row--between { justify-content: space-between; }
        .lite-flex-1 { flex: 1; min-width: 0; }
        .lite-mt { margin-top: 8px; }
        .lite-ml-auto { margin-left: auto; }
        .lite-mb { margin-bottom: 14px; }
        .lite-td-strong { font-weight: 600; }
        .lite-td-right { text-align: right; }
        .lite-mono { font-family: monospace; font-size: 0.8rem; }
        .lite-mono--accent { font-family: monospace; font-size: 0.8rem; color: var(--lite-success); }
        .lite-muted-link { font-size: 0.78rem; color: var(--lite-muted); text-decoration: none; }
        .lite-alert__link { color: inherit; font-weight: 700; }
        .lite-empty { text-align: center; padding: 40px 0; color: var(--lite-muted); }
        .lite-empty i { width: 40px; height: 40px; margin-bottom: 12px; opacity: .4; }
        .lite-empty--success i { color: var(--lite-success); opacity: .5; }
        .lite-total { font-size: 1.3rem; font-weight: 800; color: var(--lite-success); }
        .lite-patient-chip { display: flex; align-items: center; gap: 12px; }
        .lite-patient-chip__avatar { width: 44px; height: 44px; border-radius: 50%; background: #f0fdf4; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--lite-success); }
        .lite-patient-chip__avatar--primary { background: #e7eef6; color: var(--lite-primary); }
        .lite-mono--primary { font-family: monospace; font-size: 0.78rem; color: var(--lite-primary); }
        .lite-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .lite-form-group--flush { margin: 0; }
        .lite-radio-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .lite-radio-chip { display: flex; align-items: center; gap: 5px; cursor: pointer; padding: 6px 12px; border: 1px solid var(--lite-border); border-radius: 8px; font-size: 0.83rem; font-weight: 500; }
        .lite-radio-chip input { accent-color: var(--lite-primary); }
        .lite-alert--column { flex-direction: column; align-items: flex-start; gap: 4px; }
        .lite-alert--sm { font-size: 0.8rem; }
        .lite-card__body--flush { padding: 0; }
        .lite-card__body--scroll { padding: 0; overflow-x: auto; }
        .lite-code { font-size: 0.78rem; background: #e7eef6; padding: 1px 5px; border-radius: 3px; font-family: monospace; }
        .lite-sub-id { margin-left: 8px; font-size: 0.78rem; font-family: monospace; color: var(--lite-muted); }
        .lite-applied { color: var(--lite-success); }
        .lite-back { color: var(--lite-muted); display: inline-flex; }
        .lite-page-head--plain { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .lite-card__head--row { display: flex; justify-content: space-between; align-items: center; }
        .lite-input--cell { width: 100%; min-width: 0; }
        .lite-col-narrow { width: 70px; }
        .lite-col-amt { width: 110px; }
        .lite-col-x { width: 40px; text-align: center; }

        /* Quick-action card icon tints */
        .lite-btn-card__icon { background: #f1f5f9; color: var(--lite-muted); }
        .lite-btn-card__icon--info    { background: #eff6ff; color: #2563eb; }
        .lite-btn-card__icon--success { background: #f0fdf4; color: var(--lite-success); }
        .lite-btn-card__icon--warning { background: #fff7ed; color: var(--lite-warning); }

        /* Alerts */
        .lite-alert { border-radius: 8px; padding: 10px 14px; font-size: 0.83rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .lite-alert--success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #14532d; }
        .lite-alert--warning { background: #fff7ed; border: 1px solid #fed7aa; color: #92400e; }
        .lite-alert--danger  { background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; }
        .lite-alert--info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; }

        /* Forms */
        .lite-form-group { margin-bottom: 14px; }
        .lite-label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--lite-text); margin-bottom: 5px; }
        .lite-input {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--lite-border);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--lite-text);
            background: #fff;
            box-sizing: border-box;
        }
        .lite-input:focus { outline: none; border-color: var(--lite-primary); box-shadow: 0 0 0 3px rgba(15,76,129,.12); }
        .lite-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .lite-btn--primary  { background: var(--lite-primary); color: #fff; }
        .lite-btn--success  { background: var(--lite-success); color: #fff; }
        .lite-btn--outline  { background: transparent; border: 1px solid var(--lite-border); color: var(--lite-text); }
        .lite-btn--danger   { background: var(--lite-danger); color: #fff; }
        .lite-btn--full     { width: 100%; justify-content: center; }

        /* Table */
        .lite-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .lite-table th { background: #f1f5f9; font-weight: 600; padding: 8px 12px; text-align: left; color: var(--lite-muted); }
        .lite-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .lite-table tr:last-child td { border-bottom: none; }

        /* Card */
        .lite-card { background: #fff; border: 1px solid var(--lite-border); border-radius: 12px; margin-bottom: 14px; overflow: hidden; }
        .lite-card__head { padding: 12px 16px; border-bottom: 1px solid var(--lite-border); font-weight: 700; font-size: 0.88rem; color: var(--lite-text); }
        .lite-card__body { padding: 16px; }

        /* Page title */
        .lite-page-title { font-size: 1.1rem; font-weight: 800; color: var(--lite-text); margin: 0 0 4px; }
        .lite-page-sub   { font-size: 0.82rem; color: var(--lite-muted); margin: 0 0 16px; }

        /* Badge */
        .lite-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .lite-badge--success { background: #dcfce7; color: #15803d; }
        .lite-badge--warning { background: #fef3c7; color: #b45309; }
        .lite-badge--danger  { background: #fee2e2; color: #b91c1c; }
        .lite-badge--info    { background: #dbeafe; color: #1d4ed8; }
        .lite-badge--default { background: #f1f5f9; color: #475569; }

        /* Inline-style replacements */
        .lite-footer-link{font-size:.78rem;opacity:.7}
        .lite-title-sm{font-size:.78rem;opacity:.8}
        .lite-sidenav__footer{margin-top:auto;padding:12px 16px}
        .lite-notify-row{padding:8px 16px}
        .lite-notify-icon{width:16px;height:16px;flex-shrink:0}

        @media (min-width: 640px) {
            .lite-main { padding: 24px; padding-bottom: 24px; }
            .lite-bottomnav { display: none; }
            /* Desktop side nav */
            .lite-shell { flex-direction: row; }
            .lite-sidenav {
                width: 220px;
                background: var(--lite-primary);
                min-height: 100vh;
                padding: 16px 0;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                position: sticky;
                top: 0;
                height: 100vh;
            }
            .lite-sidenav__brand {
                color: #fff;
                font-weight: 800;
                font-size: 1rem;
                padding: 0 16px 20px;
                border-bottom: 1px solid rgba(255,255,255,.15);
                margin-bottom: 8px;
            }
            .lite-sidenav__badge {
                font-size: 0.65rem;
                background: rgba(255,255,255,.15);
                padding: 2px 6px;
                border-radius: 10px;
                margin-left: 6px;
            }
            .lite-sidenav__link {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 9px 16px;
                color: rgba(255,255,255,.75);
                text-decoration: none;
                font-size: 0.86rem;
                font-weight: 500;
                border-radius: 0;
                transition: background .12s, color .12s;
            }
            .lite-sidenav__link:hover,
            .lite-sidenav__link.active { background: rgba(255,255,255,.12); color: #fff; }
            .lite-sidenav__link i { width: 16px; height: 16px; flex-shrink: 0; }
            .lite-sidenav__section {
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: rgba(255,255,255,.45);
                padding: 12px 16px 4px;
            }
            .lite-topbar { display: none; }
            .lite-main { padding-bottom: 24px; }
        }
    </style>
    @yield('head')
</head>
<body>
<div class="lite-shell">

    {{-- Desktop side nav --}}
    <nav class="lite-sidenav" style="display:none;" id="lite-sidenav">
        <div class="lite-sidenav__brand">
            OpesCare <span class="lite-sidenav__badge">Lite</span>
        </div>
        <a href="{{ route('portals.lite.dashboard') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i> {{ __('lite_chrome.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}
        </a>
        <a href="{{ route('portals.lite.lookup') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.lookup') ? 'active' : '' }}">
            <i data-lucide="search"></i> {{ __('lite_chrome.nav_lookup', [], app()->getLocale()) ?: 'Health ID Lookup' }}
        </a>
        <a href="{{ route('portals.lite.checkin') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.checkin') ? 'active' : '' }}">
            <i data-lucide="log-in"></i> {{ __('lite_chrome.nav_checkin', [], app()->getLocale()) ?: 'Check-In' }}
        </a>
        <a href="{{ route('portals.lite.consultation') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.consultation') ? 'active' : '' }}">
            <i data-lucide="stethoscope"></i> {{ __('lite_chrome.nav_consultation', [], app()->getLocale()) ?: 'Consultation' }}
        </a>
        @feature('billing')
        <a href="{{ route('portals.lite.billing') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.billing') ? 'active' : '' }}">
            <i data-lucide="receipt"></i> {{ __('lite_chrome.nav_billing', [], app()->getLocale()) ?: 'Billing' }}
        </a>
        @endfeature
        <div class="lite-sidenav__section">{{ __('lite_chrome.nav_admin', [], app()->getLocale()) ?: 'Admin' }}</div>
        <a href="{{ route('portals.lite.devices') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.devices') ? 'active' : '' }}">
            <i data-lucide="monitor-smartphone"></i> {{ __('lite_chrome.nav_devices', [], app()->getLocale()) ?: 'Devices' }}
        </a>
        <a href="{{ route('portals.lite.conflicts') }}"
           class="lite-sidenav__link {{ request()->routeIs('portals.lite.conflicts') ? 'active' : '' }}">
            <i data-lucide="git-merge"></i> {{ __('lite_chrome.nav_conflicts', [], app()->getLocale()) ?: 'Conflicts' }}
        </a>
        <div class="lite-sidenav__footer">
            <a href="{{ route('portals.staff') }}" class="lite-sidenav__link lite-footer-link">
                <i data-lucide="arrow-left"></i> {{ __('lite_chrome.nav_full_portal', [], app()->getLocale()) ?: 'Full Portal' }}
            </a>
        </div>
    </nav>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        {{-- Mobile top bar --}}
        <header class="lite-topbar">
            <div>
                <span class="lite-topbar__brand">OpesCare</span>
                <span class="lite-topbar__badge">Lite</span>
            </div>
            <div class="lite-topbar__right">
                <span class="lite-title-sm">@yield('title', 'Dashboard')</span>
            </div>
        </header>

        {{-- Online/offline status bar --}}
        <div class="lite-status-bar online" id="lite-status-bar"
             data-online-text="{{ __('lite_chrome.status_online', [], app()->getLocale()) ?: 'Online — Synced' }}"
             data-offline-text="{{ __('lite_chrome.status_offline', [], app()->getLocale()) ?: 'Offline — Changes will sync when reconnected' }}">
            <span class="lite-status-dot"></span>
            <span id="lite-status-text">{{ __('lite_chrome.status_online', [], app()->getLocale()) ?: 'Online — Synced' }}</span>
        </div>

        @if(session('success'))
            <div class="lite-notify-row">
                <div class="lite-alert lite-alert--success">
                    <i data-lucide="check-circle" class="lite-notify-icon"></i>
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="lite-notify-row">
                <div class="lite-alert lite-alert--danger">
                    <i data-lucide="alert-circle" class="lite-notify-icon"></i>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="lite-main">
            @yield('content')
        </main>
    </div>

    {{-- Mobile bottom nav --}}
    <nav class="lite-bottomnav">
        <a href="{{ route('portals.lite.dashboard') }}"
           class="lite-bottomnav__item {{ request()->routeIs('portals.lite.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i>{{ __('lite_chrome.bottom_home', [], app()->getLocale()) ?: 'Home' }}
        </a>
        <a href="{{ route('portals.lite.lookup') }}"
           class="lite-bottomnav__item {{ request()->routeIs('portals.lite.lookup') ? 'active' : '' }}">
            <i data-lucide="search"></i>{{ __('lite_chrome.bottom_lookup', [], app()->getLocale()) ?: 'Lookup' }}
        </a>
        <a href="{{ route('portals.lite.checkin') }}"
           class="lite-bottomnav__item {{ request()->routeIs('portals.lite.checkin') ? 'active' : '' }}">
            <i data-lucide="log-in"></i>{{ __('lite_chrome.bottom_checkin', [], app()->getLocale()) ?: 'Check-In' }}
        </a>
        <a href="{{ route('portals.lite.consultation') }}"
           class="lite-bottomnav__item {{ request()->routeIs('portals.lite.consultation') ? 'active' : '' }}">
            <i data-lucide="stethoscope"></i>{{ __('lite_chrome.bottom_consult', [], app()->getLocale()) ?: 'Consult' }}
        </a>
        <a href="{{ route('portals.lite.devices') }}"
           class="lite-bottomnav__item {{ request()->routeIs('portals.lite.*') && !request()->routeIs('portals.lite.dashboard') && !request()->routeIs('portals.lite.lookup') && !request()->routeIs('portals.lite.checkin') && !request()->routeIs('portals.lite.consultation') && !request()->routeIs('portals.lite.billing') ? 'active' : '' }}">
            <i data-lucide="monitor-smartphone"></i>{{ __('lite_chrome.bottom_admin', [], app()->getLocale()) ?: 'Admin' }}
        </a>
    </nav>

</div>

<script src="{{ asset('js/lucide.min.js') }}"></script>
<script>
lucide.createIcons();

// Desktop sidenav reveal
if (window.innerWidth >= 640) {
    document.getElementById('lite-sidenav').style.display = 'flex';
}

// Online/offline detection
function updateOnlineStatus() {
    const bar  = document.getElementById('lite-status-bar');
    const text = document.getElementById('lite-status-text');
    if (navigator.onLine) {
        bar.className  = 'lite-status-bar online';
        text.textContent = bar.dataset.onlineText;
    } else {
        bar.className  = 'lite-status-bar';
        text.textContent = bar.dataset.offlineText;
    }
}
window.addEventListener('online',  updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();
</script>
@yield('scripts')
</body>
</html>
