@php $l = app()->getLocale(); @endphp
{{-- Rendered for pharmacist, pharmacy_technician and dispensing_officer. Every
     link self-gates with the same @capability as its route
     (config/staff_capabilities.php: dispense, pharmacy_stock, messaging). --}}
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('portal.pharmacist_role', [], $l) ?: 'Pharmacist' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>

@capability('pharmacy_stock', 'dispense', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) ?: 'Pharmacy' }}</div>
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock*') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.portal.nav_stock', [], $l) ?: 'Stock' }}</span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.portal.nav_prescriptions', [], $l) ?: 'Prescriptions' }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions.lookup') ? 'active' : '' }}">
        <i data-lucide="scan-line"></i><span>{{ __('prescriber.counter_link', [], $l) ?: 'Counter lookup' }}</span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.portal.nav_controlled', [], $l) ?: 'Controlled Substances' }}</span>
    </a>
    @endcapability
    @capability('messaging')
    {{-- The staff inbox lives under /facility; pharmacy roles are in that
         portal bucket too (EnsurePortalAccess), so the link resolves. --}}
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('messaging.nav', [], $l) ?: 'Messages' }}</span>
    </a>
    @endcapability
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')
