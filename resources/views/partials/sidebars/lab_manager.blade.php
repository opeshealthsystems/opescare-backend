@php $l = app()->getLocale(); @endphp
{{-- Rendered for lab_manager and lab_validator. Every capability-mapped link
     self-gates with the same @capability as its route.

     The laboratory desk (routes/lab.php) is the web page behind `lab_results`
     and `sample_collection`; validation is taken from the order page. --}}
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    {{ __('portal.lab_manager_role', [], $l) ?: 'Lab Manager' }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
    {{-- Global search (facilities, documents, medicines, lab tests, partners,
         messages — never patients). The screen existed, complete, with no
         route: the audit called it unreachable. --}}
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) ?: 'Global Search' }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) ?: 'Laboratory' }}</div>
    {{-- The bench: worklist, result entry, release, validation. Same gate as routes/lab.php. --}}
    @capability('lab_results')
    <a href="{{ route('portals.lab.index') }}" class="sidebar-link {{ request()->routeIs('portals.lab.index', 'portals.lab.show') ? 'active' : '' }}">
        <i data-lucide="flask-conical"></i><span>{{ __('lab_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('lab_results', 'sample_collection')
    <a href="{{ route('portals.lab.collection') }}" class="sidebar-link {{ request()->routeIs('portals.lab.collection') ? 'active' : '' }}">
        <i data-lucide="test-tube-2"></i><span>{{ __('lab_desk.nav_collection', [], $l) }}</span>
    </a>
    @endcapability
    {{-- Blood Bank is NOT behind @feature('inventory_ops'). It is carved out of
         that freeze because it is the only reachable writer of the public Blood
         Finder's data — see the 'inventory_ops' block in bootstrap/app.php.
         A screen with no way in is a feature that does not exist. --}}
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], $l) ?: 'Blood Bank' }}</span>
    </a>
    {{-- The queue of patients asking this blood bank for units. Sits beside
         the shelf it is asked against, behind the same capability. --}}
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.blood_requests.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('blood_desk.nav', [], $l) ?: 'Blood Requests' }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) ?: 'Messages' }}</span>
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
        <span>{{ __('caremap_claim.nav_claim_listing', [], $l) ?: 'Claim your listing' }}</span>
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


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')
