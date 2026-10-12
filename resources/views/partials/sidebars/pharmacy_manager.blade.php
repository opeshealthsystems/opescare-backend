@php $l = app()->getLocale(); @endphp
{{-- A pharmacy manager holds dispense, pharmacy_stock and messaging
     (config/staff_capabilities.php `$pharmacy`) — the same counter and stock
     pages as the pharmacist, which used to be reachable only by URL from this
     sidebar. Every link self-gates with the same @capability as its route. --}}
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('portal.pharmacy_manager_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.pharmacy.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_pharmacy', [], $l) }}</div>
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock') ? 'active' : '' }}">
        <i data-lucide="package"></i><span>{{ __('public.portal.nav_stock', [], $l) }}</span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.portal.nav_prescriptions', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prescriptions.lookup') ? 'active' : '' }}">
        <i data-lucide="scan-line"></i><span>{{ __('prescriber.counter_link', [], $l) }}</span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.controlled') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.controlled*') ? 'active' : '' }}">
        <i data-lucide="shield-alert"></i><span>{{ __('public.portal.nav_controlled', [], $l) }}</span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.orders.index') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.orders.*') ? 'active' : '' }}">
        <i data-lucide="shopping-bag"></i><span>{{ __('pharmacy_orders.title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.prices.index') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.prices.*') ? 'active' : '' }}">
        <i data-lucide="tags"></i><span>{{ __('pharmacy_prices.nav_prices', [], $l) }}</span>
    </a>
    @endcapability
    @capability('service_catalogue')
    <a href="{{ route('portals.pharmacy.settings.index') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.settings.*') ? 'active' : '' }}">
        <i data-lucide="store"></i><span>{{ __('pharmacy_settings.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i><span>{{ __('messaging.nav', [], $l) }}</span>
    </a>
    @endcapability
    {{-- The listing is claim-based (FacilityListingController), not capability-gated. --}}
    {{-- "My listing" only means something once this user holds an APPROVED
         claim on a directory listing. Without one, portals.listing.edit renders
         the empty claims state — and nearly every one of the 348 pharmacy
         managers has no claim yet, so the old link dead-ended for almost all
         of them. Say what the next step actually is. --}}
    @if(app(\App\Modules\CareMap\Services\FacilityClaimService::class)->approvedListingFor(auth()->id()))
    <a href="{{ route('portals.listing.edit') }}" class="sidebar-link {{ request()->routeIs('portals.listing.edit') ? 'active' : '' }}">
        <i data-lucide="map-pin"></i>
        <span>{{ __('caremap_claim.nav_my_listing', [], $l) }}</span>
    </a>
    @else
    <a href="{{ route('portals.listing.claims') }}" class="sidebar-link {{ request()->routeIs('portals.listing.*') ? 'active' : '' }}">
        <i data-lucide="map-pin-plus"></i>
        <span>{{ __('caremap_claim.nav_claim_listing', [], $l) }}</span>
    </a>
    @endif
    {{-- "What we offer": the facility's own service list, drawn from the
         institutional code dictionary. Self-gates on the same capability the
         route does, so a role without it sees no dead link. --}}
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.catalogue') }}" class="sidebar-link {{ request()->routeIs('portals.facility.catalogue*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>

{{-- The ledger behind the public listing: lots & expiry, the append-only
     movement log, minimum levels and deliveries (routes/pharmacy_ops.php,
     same capability as the routes). --}}
@capability('pharmacy_stock')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('pharmacy_ops.nav_section', [], $l) }}</div>
    <a href="{{ route('portals.pharmacy.stock.batches') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.batches*') ? 'active' : '' }}">
        <i data-lucide="layers"></i><span>{{ __('pharmacy_ops.nav_batches', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.adjustments*') ? 'active' : '' }}">
        <i data-lucide="arrow-left-right"></i><span>{{ __('pharmacy_ops.nav_adjustments', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.reorder') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.reorder*') ? 'active' : '' }}">
        <i data-lucide="trending-down"></i><span>{{ __('pharmacy_ops.nav_reorder', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="sidebar-link {{ request()->routeIs('portals.pharmacy.stock.receipts*') ? 'active' : '' }}">
        <i data-lucide="truck"></i><span>{{ __('pharmacy_ops.nav_receipts', [], $l) }}</span>
    </a>
</div>
@endcapability


{{-- Team management (/facility/team): invite, reissue, revoke, approve join
     requests. Self-gates on the same capability as the route. --}}
@capability('facility_team')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_facility', [], $l) }}</div>
    <a href="{{ route('portals.facility.team') }}" class="sidebar-link {{ request()->routeIs('portals.facility.team*') ? 'active' : '' }}">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_team', [], $l) }}</span>
    </a>
</div>
@endcapability

{{-- Tasks + Help (routes/imports_tasks.php, imported from the platform).
     No capability gates these routes: every operational role has an inbox
     and may raise a ticket, so the links are not gated either. --}}
<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
        <i data-lucide="check-square"></i><span>{{ __('tasks.nav', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link {{ request()->routeIs('portals.staff.support*') ? 'active' : '' }}">
        <i data-lucide="life-buoy"></i><span>{{ __('support.nav', [], $l) }}</span>
    </a>
</div>

{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')
