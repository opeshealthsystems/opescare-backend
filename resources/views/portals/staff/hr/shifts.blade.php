@extends('layouts.portal')

@section('title', __('public.staff_portal.hr_shifts_title', [], app()->getLocale()) ?: 'Shift Definitions')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_overview', [], app()->getLocale()) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}</span>
    </a>
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_clinical', [], app()->getLocale()) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link">
        <i data-lucide="stethoscope"></i>
        <span>{{ __('public.portal.nav_visits', [], app()->getLocale()) ?: 'Visits' }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_hr', [], app()->getLocale()) ?: 'HR & Staff' }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i>
        <span>{{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'Directory' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link active">
        <i data-lucide="clock"></i>
        <span>{{ __('public.portal.nav_staff_shifts', [], app()->getLocale()) ?: 'Shifts' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.roster') }}" class="sidebar-link">
        <i data-lucide="calendar-range"></i>
        <span>{{ __('public.portal.nav_staff_roster', [], app()->getLocale()) ?: 'Duty Roster' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.leave') }}" class="sidebar-link">
        <i data-lucide="plane-takeoff"></i>
        <span>{{ __('public.portal.nav_staff_leave', [], app()->getLocale()) ?: 'Leave' }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_inventory', [], app()->getLocale()) ?: 'Inventory' }}</div>
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="sidebar-link">
        <i data-lucide="pill"></i>
        <span>{{ __('public.portal.nav_inventory_pharmacy', [], app()->getLocale()) ?: 'Pharmacy' }}</span>
    </a>
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank' }}</span>
    </a>
</div>
@endfeature
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('public.staff_portal.sidebar_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</span>
    </a>
</div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_operations', [], app()->getLocale()) ?: 'Operations' }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i>
        <span>{{ __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span>{{ __('public.portal.nav_support', [], app()->getLocale()) ?: 'Support' }}</span>
    </a>
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link">
        <i data-lucide="upload-cloud"></i>
        <span>{{ __('public.portal.nav_data_import', [], app()->getLocale()) ?: 'Data Import' }}</span>
    </a>
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i>
        <span>{{ __('public.portal.nav_search', [], app()->getLocale()) ?: 'Global Search' }}</span>
    </a>
    <a href="{{ route('portals.staff.files.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.files*') ? 'active' : '' }}">
        <i data-lucide="paperclip"></i>
        <span>{{ __('public.portal.nav_files', [], app()->getLocale()) ?: 'Files & Attachments' }}</span>
    </a>
    <a href="{{ route('portals.staff.wards') }}" class="sidebar-link {{ request()->routeIs('portals.staff.wards*') ? 'active' : '' }}">
        <i data-lucide="bed"></i>
        <span>{{ __('public.portal.nav_wards', [], app()->getLocale()) ?: 'Wards & Beds' }}</span>
    </a>
</div>
@endsection

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.hr_shifts_breadcrumb', [], app()->getLocale()) ?: 'Shifts')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.staff_portal.hr_shifts_title', [], app()->getLocale()) ?: 'Shift Definitions' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.hr_shifts_subtitle', [], app()->getLocale()) ?: 'Define the shift templates used across duty rosters.' }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openShiftModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.staff_portal.hr_shifts_btn_new', [], app()->getLocale()) ?: 'New Shift' }}
    </button>
</div>

@if(session('success'))
    <div class="auth-alert auth-alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="auth-alert auth-alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($shifts->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="clock"></i></div>
                <h3>{{ __('public.staff_portal.hr_shifts_empty_title', [], app()->getLocale()) ?: 'No Shifts Defined' }}</h3>
                <p>{{ __('public.staff_portal.hr_shifts_empty_desc', [], app()->getLocale()) ?: 'Create shift templates like Morning, Afternoon, Night, or On-Call.' }}</p>
                <button type="button" class="btn btn-primary btn-sm mt-6" onclick="openShiftModal()">
                    {{ __('public.staff_portal.hr_shifts_btn_new', [], app()->getLocale()) ?: 'New Shift' }}
                </button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.staff_portal.hr_shifts_col_name', [], app()->getLocale()) ?: 'Name' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_dept', [], app()->getLocale()) ?: 'Department' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_start', [], app()->getLocale()) ?: 'Start' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_end', [], app()->getLocale()) ?: 'End' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_duration', [], app()->getLocale()) ?: 'Duration' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_midnight', [], app()->getLocale()) ?: 'Crosses Midnight' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                            <th>{{ __('public.staff_portal.hr_shifts_col_actions', [], app()->getLocale()) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                        <tr>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_name', [], app()->getLocale()) ?: 'Name' }}"><strong class="td-strong">{{ $shift->name }}</strong></td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_dept', [], app()->getLocale()) ?: 'Department' }}">{{ $shift->department ?? '—' }}</td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_start', [], app()->getLocale()) ?: 'Start' }}">{{ $shift->start_time }}</td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_end', [], app()->getLocale()) ?: 'End' }}">{{ $shift->end_time }}</td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_duration', [], app()->getLocale()) ?: 'Duration' }}">{{ $shift->duration_hours ? $shift->duration_hours . 'h' : '—' }}</td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_midnight', [], app()->getLocale()) ?: 'Crosses Midnight' }}">
                                @if($shift->crosses_midnight)
                                    <span class="badge badge-warning">{{ __('public.staff_portal.hr_shifts_yes', [], app()->getLocale()) ?: 'Yes' }}</span>
                                @else
                                    <span class="badge badge-neutral">{{ __('public.staff_portal.hr_shifts_no', [], app()->getLocale()) ?: 'No' }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_status', [], app()->getLocale()) ?: 'Status' }}">
                                <span class="badge {{ $shift->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                                    @enum($shift->status)
                                </span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.hr_shifts_col_actions', [], app()->getLocale()) ?: 'Actions' }}">
                                <form method="POST" action="{{ route('portals.staff.hr.shifts.toggle', $shift->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-xs">
                                        <i data-lucide="{{ $shift->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                        {{ $shift->status === 'active' ? (__('public.staff_portal.hr_shifts_deactivate', [], app()->getLocale()) ?: 'Deactivate') : (__('public.staff_portal.hr_shifts_activate', [], app()->getLocale()) ?: 'Activate') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- New Shift Modal --}}
<div id="shift-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.staff_portal.hr_shifts_modal_title', [], app()->getLocale()) ?: 'New Shift' }}</h3>
        </div>
        <form method="POST" action="{{ route('portals.staff.hr.shifts.store') }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_name', [], app()->getLocale()) ?: 'Shift Name *' }}</label>
                <input type="text" name="name" class="form-control" required maxlength="100" placeholder="{{ __('public.staff_portal.hr_shifts_ph_name', [], app()->getLocale()) ?: 'e.g. Morning, Night, On-Call' }}">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_dept', [], app()->getLocale()) ?: 'Department' }}</label>
                <input type="text" name="department" class="form-control" maxlength="100" placeholder="{{ __('public.staff_portal.hr_shifts_ph_dept', [], app()->getLocale()) ?: 'Leave blank for all departments' }}">
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_start', [], app()->getLocale()) ?: 'Start Time *' }}</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_end', [], app()->getLocale()) ?: 'End Time *' }}</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_duration', [], app()->getLocale()) ?: 'Duration (hours)' }}</label>
                    <input type="number" name="duration_hours" class="form-control" min="1" max="24" placeholder="8">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.staff_portal.hr_shifts_lbl_midnight', [], app()->getLocale()) ?: 'Crosses Midnight?' }}</label>
                    <select name="crosses_midnight" class="form-control">
                        <option value="0">{{ __('public.staff_portal.hr_shifts_no', [], app()->getLocale()) ?: 'No' }}</option>
                        <option value="1">{{ __('public.staff_portal.hr_shifts_yes', [], app()->getLocale()) ?: 'Yes' }}</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeShiftModal()">{{ __('public.staff_portal.hr_shifts_btn_cancel', [], app()->getLocale()) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="clock"></i>
                    {{ __('public.staff_portal.hr_shifts_btn_create', [], app()->getLocale()) ?: 'Create Shift' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openShiftModal()  { document.getElementById('shift-modal').classList.add('open'); }
    function closeShiftModal() { document.getElementById('shift-modal').classList.remove('open'); }
    document.getElementById('shift-modal').addEventListener('click', function(e) {
        if (e.target === this) closeShiftModal();
    });
</script>
@endsection
