@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="flask-conical" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.labtech_role', [], $l) ?: 'Lab Technician' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab_portal', [], $l) ?: 'Lab Portal' }}</div>
    <a href="{{ route('portals.lab.dashboard') }}" class="sidebar-link" style="background:rgba(14,165,233,.08);border-left:3px solid #0ea5e9;">
        <i data-lucide="microscope" style="color:#0ea5e9;"></i>
        <span style="font-weight:700;">{{ __('public.portal.nav_lab_portal_my', [], $l) ?: 'My Lab Portal' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) ?: 'Laboratory' }}</div>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], $l) ?: 'Sample Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.immunizations') }}" class="sidebar-link">
        <i data-lucide="syringe"></i>
        <span>{{ __('public.portal.nav_immunizations', [], $l) ?: 'Immunizations' }}</span>
    </a>
    {{-- Blood Bank is NOT behind @feature('inventory_ops'). It is carved out of
         that freeze because it is the only reachable writer of the public Blood
         Finder's data — see the 'inventory_ops' block in bootstrap/app.php.
         A screen with no way in is a feature that does not exist. --}}
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank' }}</span>
    </a>
    @feature('inventory_ops')
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link">
        <i data-lucide="package"></i>
        <span>{{ __('public.portal.nav_supply', [], $l) ?: 'Supply Chain' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span>{{ __('public.portal.nav_support', [], $l) ?: 'Support' }}</span>
    </a>
</div>
