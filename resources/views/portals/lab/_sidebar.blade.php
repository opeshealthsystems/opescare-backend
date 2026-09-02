@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="microscope"></i>
    {{ __('public.lab_portal.role_badge', [], $l) ?: 'Laboratory' }}
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.lab_portal.nav_section', [], $l) ?: 'Laboratory' }}</div>
    <a href="{{ route('portals.lab.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.lab.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.lab_portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.lab.orders') }}" class="sidebar-link {{ request()->routeIs('portals.lab.orders') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('public.lab_portal.nav_work_queue', [], $l) ?: 'Work queue' }}</span>
    </a>
    <a href="{{ route('portals.lab.samples') }}" class="sidebar-link {{ request()->routeIs('portals.lab.samples') ? 'active' : '' }}">
        <i data-lucide="test-tube"></i><span>{{ __('public.lab_portal.nav_samples', [], $l) ?: 'Sample tracking' }}</span>
    </a>
    <a href="{{ route('portals.lab.results') }}" class="sidebar-link {{ request()->routeIs('portals.lab.results') ? 'active' : '' }}">
        <i data-lucide="file-bar-chart"></i><span>{{ __('public.lab_portal.nav_results', [], $l) ?: 'Results' }}</span>
    </a>
</div>
