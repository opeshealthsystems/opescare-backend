@php $l = app()->getLocale(); @endphp
{{-- Rendered for labtech and lab_scientist. Every link self-gates with the
     same @capability as its route (config/staff_capabilities.php).

     The laboratory desk (routes/lab.php) is the web page behind `lab_results`
     and `sample_collection`; before it shipped both capabilities gated nothing. --}}
<div class="sidebar-role-badge">
    <i data-lucide="flask-conical"></i>
    {{ __('portal.labtech_role', [], $l) ?: 'Lab Technician' }}
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

@capability('lab_results', 'sample_collection', 'blood_inventory', 'messaging')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_lab', [], $l) ?: 'Laboratory' }}</div>
    {{-- The bench: worklist, result entry, release. Same gate as routes/lab.php. --}}
    @capability('lab_results')
    <a href="{{ route('portals.lab_manager.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.lab_manager.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('lab_manager.nav_dashboard', [], $l) }}</span>
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
