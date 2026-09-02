@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.4);color:#93C5FD;">
    <i data-lucide="flask-conical" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.labtech_role', [], $l) ?: 'Lab Technician' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab_portal', [], $l) ?: 'Lab Portal' }}</div>
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
            {{-- Blood Bank is NOT behind @feature('inventory_ops'). It is carved out of
         that freeze because it is the only reachable writer of the public Blood
         Finder's data — see the 'inventory_ops' block in bootstrap/app.php.
         A screen with no way in is a feature that does not exist. --}}
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank' }}</span>
    </a>
    @feature('inventory_ops')
        @endfeature
    </div>
