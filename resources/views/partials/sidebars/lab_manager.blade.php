@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    {{ __('portal.lab_manager_role', [], $l) ?: 'Lab Manager' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
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
    <a href="{{ route('portals.listing.edit') }}" class="sidebar-link {{ request()->routeIs('portals.listing.*') ? 'active' : '' }}">
        <i data-lucide="map-pin"></i>
        <span>{{ __('caremap_claim.nav_my_listing', [], $l) }}</span>
    </a>
</div>
