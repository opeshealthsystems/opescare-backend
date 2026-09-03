@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="package-2"></i>
    {{ __('portal.medicine_stock_role', [], $l) ?: 'Medicine Stock' }}
</div>
<div class="sidebar-spacer"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy_portal', [], $l) ?: 'Pharmacy Portal' }}</div>
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link">
        <i data-lucide="pill"></i>
        <span>{{ __('public.portal.nav_pharmacy_portal_my', [], $l) ?: 'My Pharmacy Portal' }}</span>
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
    </div>
