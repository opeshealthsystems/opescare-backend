<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OpesCare Portal')</title>
    <meta name="theme-color" content="#0F4C81">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('assets/finder.css')))
        <link rel="stylesheet" href="{{ asset('assets/finder.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/helix-v2.css') }}">
    <style>
        .sidebar-spacer{margin-bottom:var(--p-space-3,.75rem)}
        .min-w-0{min-width:0}
    </style>
    @yield('head')
</head>
<body class="portal-body">

<div class="portal-wrap">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

    @php
        /*
         * The role that picks the sidebar (and names the badge) is the one held
         * at the ACTIVE facility — the same resolution EnsurePortalAccess and the
         * capability gate use (session active_facility_id → primary facility →
         * global role). It used to be the global `users.role_id`, so a clinician
         * posted as a nurse at this hospital and a doctor elsewhere was gated as
         * a nurse but shown the doctor's chrome. Gate and chrome now agree.
         */
        $sidebarUser        = auth()->user();
        $sidebarRole        = $sidebarUser
            ? \App\Support\StaffCapabilities::roleModelFor($sidebarUser, \App\Support\StaffCapabilities::contextFacilityId($sidebarUser))
            : null;
        $sidebarProfileKey  = $sidebarRole?->dashboard_profile_key ?? 'patient';

        /*
         * The role label shown in the brand-sub, the sidebar footer and the
         * topbar — three places on every portal page, in every role.
         *
         * It used to be `roles.description` straight off the table. That is a
         * database column, so it is English and only English: a French-speaking
         * visiting doctor read "Visiting Doctor" three times per page while the
         * badge between those two lines said "Médecin vacataire", which
         * lang/fr/ had been carrying all along. `portal.role_name.*` is keyed
         * by the role slug and translated on both sides.
         *
         * The column stays as the fallback, on purpose: a role seeded into the
         * table with no lang entry yet should read as its stored English name,
         * not as a blank sidebar and not as a slug.
         */
        $sidebarRoleKey     = $sidebarRole?->name;
        $sidebarRoleLabel   = $sidebarRoleKey && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $sidebarRoleKey)
            ? __('portal.role_name.' . $sidebarRoleKey)
            : ($sidebarRole?->description ?? (auth()->user()?->roleName() ?? 'Portal'));

        $topbarFacilityName = auth()->user()?->primaryFacility?->name;
    @endphp

    <!-- Sidebar -->
    <aside class="portal-sidebar" id="portal-sidebar" role="navigation" aria-label="Portal navigation">

        <a href="{{ url('/') }}" class="sidebar-brand" aria-label="OpesCare Home">
            <div class="sidebar-brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <div>
                <div class="sidebar-brand-name">OpesCare</div>
                <div class="sidebar-brand-sub">
                    @hasSection('sidebar_user_role')@yield('sidebar_user_role')@else{{ $sidebarRoleLabel }}@endif
                </div>
            </div>
        </a>

        <div class="sidebar-nav">
            @hasSection('sidebar_nav')
                @yield('sidebar_role_badge')
                <div class="sidebar-spacer"></div>
                @yield('sidebar_nav')
            @else
                @includeIf('partials.sidebars.' . $sidebarProfileKey)
            @endif
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="sidebar-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="sidebar-user-role">
                        @hasSection('sidebar_user_role')@yield('sidebar_user_role')@else{{ $sidebarRoleLabel }}@endif
                    </div>
                </div>
            </div>
            @php
                $me = auth()->user();
                $myProfileUrl = $me?->isProfessional()
                    ? route('professional.profile')
                    : (in_array($me?->role?->dashboard_profile_key, ['patient', 'guardian', 'caregiver', 'dependent_manager'], true)
                        ? route('portals.patient.profile')
                        : null);
            @endphp
            @if($myProfileUrl)
                {{-- Narrowed from professional.* so it does not light up at the
                     same time as the "Join a facility" link below it. --}}
                <a href="{{ $myProfileUrl }}" class="sidebar-link sidebar-link-footer {{ request()->routeIs('professional.profile*') || request()->routeIs('professional.qualifications.*') || request()->routeIs('portals.patient.profile') ? 'active' : '' }}">
                    <i data-lucide="id-card"></i><span>{{ __('professional.nav_my_profile', [], app()->getLocale()) ?: 'My Profile' }}</span>
                </a>
            @endif
            @if($me?->isProfessional())
                {{-- Lives here, next to My Profile, rather than in one of the 40+
                     role sidebars: asking to join a facility is a personal action
                     that has to work for a clinician who has NO facility yet, and
                     those are exactly the accounts whose role sidebar links to
                     pages facility.context will not let them reach. --}}
                <a href="{{ route('professional.join') }}" class="sidebar-link sidebar-link-footer {{ request()->routeIs('professional.join*') ? 'active' : '' }}">
                    <i data-lucide="building-2"></i><span>{{ __('team.join_nav', [], app()->getLocale()) ?: 'Join a facility' }}</span>
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    {{ __('public.portal.sign_out', [], app()->getLocale()) ?: 'Sign Out' }}
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="portal-main" id="portal-main">

        <!-- Topbar -->
        <header class="portal-topbar" role="banner">
            <button class="topbar-menu-btn" id="sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="portal-sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                <a href="@yield('breadcrumb_home_url', url('/'))">@yield('breadcrumb_home', 'Portal')</a>
                @hasSection('breadcrumb_section')
                    <span class="topbar-breadcrumb-sep" aria-hidden="true">/</span>
                    <span class="topbar-breadcrumb-current">@yield('breadcrumb_section')</span>
                @endif
            </nav>

            @if($topbarFacilityName)
            <div class="topbar-facility" title="{{ $topbarFacilityName }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/></svg>
                <span>{{ $topbarFacilityName }}</span>
            </div>
            @endif

            <div class="topbar-actions">
                @includeIf('partials.notifications.bell')
                @php
                    $locale = app()->getLocale();
                    $otherLocale = $locale === 'fr' ? 'en' : 'fr';
                    $otherLabel  = $locale === 'fr' ? 'EN' : 'FR';
                @endphp
                <a href="{{ url('/lang/' . $otherLocale) }}" class="topbar-lang" aria-label="Switch language to {{ $otherLabel }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span>{{ $otherLabel }}</span>
                </a>

                <a href="{{ route('public.help') }}" class="topbar-icon-btn" aria-label="Help">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </a>

                <div class="topbar-user">
                    <button type="button" class="topbar-user-btn" id="topbar-user-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="topbar-user-menu" aria-label="{{ auth()->user()->name ?? 'User' }}">
                        <span class="topbar-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="topbar-user-menu" id="topbar-user-menu" hidden>
                        <div class="topbar-user-meta">
                            <div class="topbar-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="topbar-user-role">
                                @hasSection('sidebar_user_role')@yield('sidebar_user_role')@else{{ $sidebarRoleLabel }}@endif
                            </div>
                            @if($topbarFacilityName)
                                <div class="topbar-user-facility">{{ $topbarFacilityName }}</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="topbar-user-signout">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                {{ __('public.portal.sign_out', [], app()->getLocale()) ?: 'Sign Out' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @yield('patient_banner')

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="flash-bar" role="alert">
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <div>{{ session('success') }}</div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="flash-bar" role="alert">
            <div class="alert alert-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>{{ session('error') }}</div>
            </div>
        </div>
        @endif

        <!-- Page Content -->
        <main class="portal-content" id="main-content" role="main">
            @yield('content')
        </main>

    </div><!-- /.portal-main -->

</div><!-- /.portal-wrap -->

<!-- Lucide Icons -->
<script src="{{ asset('js/lucide.min.js') }}"></script>
@auth
<script src="{{ asset('js/notification-sounds.js') }}" defer></script>
<script src="{{ asset('js/notifications.js') }}" defer></script>
@endauth
<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>

<!-- Portal JS -->
<script>
(function() {
    var sidebar  = document.getElementById('portal-sidebar');
    var overlay  = document.getElementById('sidebar-overlay');
    var toggleBtn = document.getElementById('sidebar-toggle');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', function() {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Topbar user menu
    var userToggle = document.getElementById('topbar-user-toggle');
    var userMenu   = document.getElementById('topbar-user-menu');

    function closeUserMenu() {
        if (userMenu && !userMenu.hidden) {
            userMenu.hidden = true;
            userToggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (userToggle && userMenu) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = userMenu.hidden;
            userMenu.hidden = !open;
            userToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function(e) {
            if (!userMenu.hidden && !userMenu.contains(e.target)) closeUserMenu();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sidebar.classList.contains('open')) closeSidebar();
            closeUserMenu();
        }
    });

    // Mark the active sidebar link.
    //
    // This compared getAttribute('href') against location.pathname. route()
    // emits ABSOLUTE urls, so it was comparing 'https://host/portals/staff'
    // with '/portals/staff' — never equal, and the whole block was dead. Only
    // the handful of links carrying a Blade routeIs() guard ever highlighted.
    //
    // Now: compare paths to paths, and fall back to the longest matching
    // prefix so a detail page (/portals/staff/visits/123) still lights up its
    // section (/portals/staff/visits) instead of leaving the user with no
    // indication of where they are.
    var currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    var best = null, bestLen = -1;

    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        var href = link.getAttribute('href') || '';
        var path;
        try {
            path = new URL(href, window.location.origin).pathname;
        } catch (e) {
            return;
        }
        path = path.replace(/\/+$/, '') || '/';

        if (path === currentPath) {
            best = link; bestLen = Infinity;               // exact wins outright
        } else if (
            bestLen !== Infinity &&
            path !== '/' &&
            currentPath.indexOf(path + '/') === 0 &&    // real segment boundary
            path.length > bestLen
        ) {
            best = link; bestLen = path.length;             // deepest prefix wins
        }
    });

    // Blade routeIs() guards already mark some links server-side; clear those
    // so exactly one item is highlighted rather than two.
    if (best) {
        document.querySelectorAll('.sidebar-link.active').forEach(function (l) {
            if (l !== best) l.classList.remove('active');
        });
        best.classList.add('active');
    }
})();
</script>

@yield('scripts')

</body>
</html>
