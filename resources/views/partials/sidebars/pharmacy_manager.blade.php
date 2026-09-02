@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="pill" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.pharmacy_manager_role', [], $l) ?: 'Pharmacy Manager' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy_portal', [], $l) ?: 'Pharmacy Portal' }}</div>
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link" style="background:rgba(16,185,129,.08);border-left:3px solid #10b981;">
        <i data-lucide="pill" style="color:#10b981;"></i>
        <span style="font-weight:700;">{{ __('public.portal.nav_pharmacy_portal_my', [], $l) ?: 'My Pharmacy Portal' }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    </div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy' }}</div>
    @feature('inventory_ops')
            @endfeature
    @feature('billing')
        @endfeature
    @feature('analytics_dashboards')
    @endfeature
    <a href="{{ route('portals.listing.edit') }}" class="sidebar-link {{ request()->routeIs('portals.listing.*') ? 'active' : '' }}">
        <i data-lucide="map-pin"></i>
        <span>{{ __('caremap_claim.nav_my_listing', [], $l) }}</span>
    </a>
    </div>
