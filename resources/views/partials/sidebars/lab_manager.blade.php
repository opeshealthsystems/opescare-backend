@php $l = app()->getLocale(); @endphp
{{-- Rendered for lab_manager and lab_validator. Every capability-mapped link
     self-gates with the same @capability as its route.

     The laboratory desk (routes/lab.php) is the web page behind `lab_results`
     and `sample_collection`; validation is taken from the order page. --}}
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    {{ __('portal.lab_manager_role', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

<div class="sidebar-nav-section">
    <a href="{{ route('portals.staff') }}" class="sidebar-link {{ request()->routeIs('portals.staff') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard', [], $l) }}</span>
    </a>
    {{-- Global search (facilities, documents, medicines, lab tests, partners,
         messages — never patients). The screen existed, complete, with no
         route: the audit called it unreachable. --}}
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i><span>{{ __('public.staff_portal.page_heading_search', [], $l) }}</span>
    </a>
</div>

<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) }}</div>
    {{-- The centre landing (routes/diagnostic.php): lab + imaging on one page.
         Only where the facility IS a centre — a lab-only facility would be
         redirected straight back, so the link would be noise. --}}
    @php $__diagFid = app(\App\Services\Portal\PortalContextService::class)->facilityId(); @endphp
    @if(\App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesTo($__diagFid))
    @capability('lab_results', 'imaging')
    <a href="{{ route('portals.diagnostic.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.diagnostic.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-grid"></i><span>{{ __('diagnostic_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @endif
    {{-- The bench: worklist, result entry, release, validation. Same gate as routes/lab.php. --}}
    @capability('lab_results')
    <a href="{{ route('portals.lab_manager.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.lab_manager.dashboard') ? 'active' : '' }}">
        <i data-lucide="gauge"></i><span>{{ __('lab_manager.nav_dashboard', [], $l) }}</span>
    </a>
    <a href="{{ route('portals.lab_manager.requests') }}" class="sidebar-link {{ request()->routeIs('portals.lab_manager.requests') ? 'active' : '' }}">
        <i data-lucide="inbox"></i><span>{{ __('lab_manager.nav_requests', [], $l) }}</span>
    </a>
    @endcapability
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
    {{-- The imaging half of a diagnostic centre (routes/imaging_desk.php). --}}
    @capability('imaging')
    <a href="{{ route('portals.imaging.index') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.*') && ! request()->routeIs('portals.imaging.reports.*') ? 'active' : '' }}">
        <i data-lucide="scan"></i><span>{{ __('imaging_desk.title', [], $l) }}</span>
    </a>
    @endcapability
    @capability('imaging_report')
    <a href="{{ route('portals.imaging.reports.index') }}" class="sidebar-link {{ request()->routeIs('portals.imaging.reports.index') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('imaging_desk.nav_reports', [], $l) }}</span>
    </a>
    @endcapability
    {{-- Blood Bank is NOT behind @feature('inventory_ops'). It is carved out of
         that freeze because it is the only reachable writer of the public Blood
         Finder's data — see the 'inventory_ops' block in bootstrap/app.php.
         A screen with no way in is a feature that does not exist. --}}
    @capability('blood_inventory')
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link {{ request()->routeIs('portals.staff.inventory.blood*') ? 'active' : '' }}">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], $l) }}</span>
    </a>
    {{-- The queue of patients asking this blood bank for units. Sits beside
         the shelf it is asked against, behind the same capability. --}}
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.blood_requests.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('blood_desk.nav', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) }}</span>
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
        <i data-lucide="book-open"></i><span>{{ __('catalogue.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>


{{-- Team management (/facility/team): invite, reissue, revoke, approve join
     requests. Self-gates on the same capability as the route. --}}
@capability('facility_team')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_facility', [], $l) }}</div>
    @capability('service_catalogue')
    <a href="{{ route('portals.facility.settings.index') }}" class="sidebar-link {{ request()->routeIs('portals.facility.settings.*') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('facility_settings.nav', [], $l) }}</span>
    </a>
    @endcapability
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
