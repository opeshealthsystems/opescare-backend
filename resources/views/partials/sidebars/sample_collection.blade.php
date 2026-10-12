@php $l = app()->getLocale(); @endphp
{{-- Sample collection holds identify_patient, sample_collection and messaging
     (config/staff_capabilities.php). The collection page (routes/lab.php,
     /facility/lab/collection) is the one lab screen this role reaches: it
     books a sample in and never shows a result. --}}
<div class="sidebar-role-badge">
    <i data-lucide="test-tube-2"></i>
    {{ __('portal.sample_collection_role', [], $l) }}
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

@capability('sample_collection', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) }}</div>
    {{-- Same gate as the collection routes in routes/lab.php. --}}
    @capability('lab_results', 'sample_collection')
    <a href="{{ route('portals.lab.collection') }}" class="sidebar-link {{ request()->routeIs('portals.lab.collection') ? 'active' : '' }}">
        <i data-lucide="test-tube-2"></i><span>{{ __('lab_desk.nav_collection', [], $l) }}</span>
    </a>
    @endcapability
    @capability('messaging')
    <a href="{{ route('portals.staff.messages') }}" class="sidebar-link {{ request()->routeIs('portals.staff.messages*') ? 'active' : '' }}">
        <i data-lucide="messages-square"></i>
        <span>{{ __('messaging.nav', [], $l) }}</span>
    </a>
    @endcapability
</div>
@endcapability


{{-- Facility switching for clinicians posted at more than one facility.
     Self-gating: renders nothing unless this user holds more than one active
     posting (see partials/sidebars/_switch_facility.blade.php). --}}
@include('partials.sidebars._switch_facility')
