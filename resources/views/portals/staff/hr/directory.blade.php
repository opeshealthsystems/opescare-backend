@extends('layouts.portal')

@section('title', __('staff_hr.title_directory', [], app()->getLocale()) ?: 'Staff Directory')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_overview', [], app()->getLocale()) ?: 'Overview' }}</div>
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
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_hr', [], app()->getLocale()) ?: 'HR & Staff' }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link active">
        <i data-lucide="users"></i>
        <span>{{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'Directory' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
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
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_inventory', [], app()->getLocale()) ?: 'Inventory' }}</div>
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
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('staff_hr.nav_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</span>
    </a>
</div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_hr.nav_lbl_operations', [], app()->getLocale()) ?: 'Operations' }}</div>
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
@section('breadcrumb_section', __('public.stf_hr_dir_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.stf_hr_dir_title') }}</h1>
        <p class="page-subtitle">{{ __('public.stf_hr_dir_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openAddStaffModal()">
        <i data-lucide="user-plus"></i>
        {{ __('public.stf_hr_dir_add_btn') }}
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

{{-- Filters --}}
<form method="GET" action="{{ route('portals.staff.hr.directory') }}" class="filter-bar">
    <select name="status" class="form-control">
        <option value="">{{ __('public.stf_hr_dir_all_statuses') }}</option>
        @foreach(['active','inactive','on_leave','suspended','terminated'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@enum($s)</option>
        @endforeach
    </select>
    <select name="staff_category" class="form-control">
        <option value="">{{ __('public.stf_hr_dir_all_categories') }}</option>
        @foreach(['clinical','administrative','support','management'] as $c)
            <option value="{{ $c }}" {{ request('staff_category') === $c ? 'selected' : '' }}>@enum($c, 'staff_category')</option>
        @endforeach
    </select>
    @if($departments->isNotEmpty())
    <select name="department" class="form-control">
        <option value="">{{ __('public.stf_hr_dir_all_departments') }}</option>
        @foreach($departments as $d)
            <option value="{{ $d }}" {{ request('department') === $d ? 'selected' : '' }}>{{ $d }}</option>
        @endforeach
    </select>
    @endif
    <input type="text" name="search" class="form-control" placeholder="{{ __('public.stf_hr_dir_search_ph') }}" value="{{ request('search') }}">
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.stf_hr_dir_filter_btn') }}
    </button>
    <a href="{{ route('portals.staff.hr.directory') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_hr_dir_clear_btn') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($staff->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="users"></i></div>
                <h3>{{ __('public.stf_hr_dir_empty_title') }}</h3>
                <p>{{ __('public.stf_hr_dir_empty_desc') }}</p>
                <button type="button" class="btn btn-primary btn-sm mt-6" onclick="openAddStaffModal()">
                    {{ __('public.stf_hr_dir_add_btn') }}
                </button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_hr_dir_col_name') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_employee') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_category') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_department') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_type') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_status') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_licenses') }}</th>
                            <th>{{ __('public.stf_hr_dir_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staff as $member)
                        @php
                            $statusBadge = match($member->status) {
                                'active'     => 'badge-success',
                                'on_leave'   => 'badge-warning',
                                'suspended'  => 'badge-danger',
                                'terminated' => 'badge-danger',
                                default      => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_hr_dir_col_name') }}">
                                <strong class="td-strong">{{ $member->full_name }}</strong>
                                @if($member->job_title)
                                    <br><span class="td-muted">{{ $member->job_title }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_employee') }}">
                                <span class="mono">{{ $member->employee_number ?? '—' }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_category') }}">
                                <span class="badge badge-neutral">@enum($member->staff_category)</span>
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_department') }}">{{ $member->department ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_hr_dir_col_type') }}">
                                <span class="badge badge-neutral">@enum($member->employment_type)</span>
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_status') }}">
                                <span class="badge {{ $statusBadge }}">@enum($member->status)</span>
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_licenses') }}">
                                @if($member->licenses->isNotEmpty())
                                    @foreach($member->licenses as $lic)
                                        <span class="badge badge-sm {{ $lic->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $lic->profession }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_hr_dir_col_actions') }}">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openLicenseModal('{{ $member->id }}', '{{ addslashes($member->full_name) }}')">
                                        <i data-lucide="badge-check"></i>
                                        {{ __('public.stf_hr_dir_btn_license') }}
                                    </button>
                                    @if($member->status !== 'terminated')
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openStatusModal('{{ $member->id }}', '{{ $member->status }}', '{{ addslashes($member->full_name) }}')">
                                        <i data-lucide="refresh-cw"></i>
                                        {{ __('public.stf_hr_dir_btn_status') }}
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Add Staff Modal --}}
<div id="add-staff-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.stf_hr_dir_modal_add_title') }}</h3>
        </div>
        <form method="POST" action="{{ route('portals.staff.hr.directory.store') }}">
            @csrf
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_first_name') }}</label>
                    <input type="text" name="first_name" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_last_name') }}</label>
                    <input type="text" name="last_name" class="form-control" required maxlength="100">
                </div>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_email') }}</label>
                    <input type="email" name="email" class="form-control" maxlength="200">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_phone') }}</label>
                    <input type="text" name="phone" class="form-control" maxlength="30">
                </div>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_job_title') }}</label>
                    <input type="text" name="job_title" class="form-control" maxlength="150">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_department') }}</label>
                    <input type="text" name="department" class="form-control" maxlength="100" placeholder="{{ __('public.stf_hr_dir_ph_department') }}">
                </div>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_staff_category') }}</label>
                    <select name="staff_category" class="form-control" required>
                        <option value="clinical">{{ __('staff_hr.cat_clinical', [], app()->getLocale()) ?: 'Clinical' }}</option>
                        <option value="administrative">{{ __('staff_hr.cat_administrative', [], app()->getLocale()) ?: 'Administrative' }}</option>
                        <option value="support">{{ __('staff_hr.cat_support', [], app()->getLocale()) ?: 'Support' }}</option>
                        <option value="management">{{ __('staff_hr.cat_management', [], app()->getLocale()) ?: 'Management' }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_employment_type') }}</label>
                    <select name="employment_type" class="form-control" required>
                        <option value="full_time">{{ __('staff_hr.emp_full_time', [], app()->getLocale()) ?: 'Full Time' }}</option>
                        <option value="part_time">{{ __('staff_hr.emp_part_time', [], app()->getLocale()) ?: 'Part Time' }}</option>
                        <option value="contract">{{ __('staff_hr.emp_contract', [], app()->getLocale()) ?: 'Contract' }}</option>
                        <option value="locum">{{ __('staff_hr.emp_locum', [], app()->getLocale()) ?: 'Locum' }}</option>
                    </select>
                </div>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_hire_date') }}</label>
                    <input type="date" name="hire_date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_employee_number') }}</label>
                    <input type="text" name="employee_number" class="form-control" placeholder="{{ __('public.stf_hr_dir_ph_employee_number') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeAddStaffModal()">{{ __('public.stf_hr_dir_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="user-plus"></i>
                    {{ __('public.stf_hr_dir_btn_add_staff') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- License Modal --}}
<div id="license-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.stf_hr_dir_modal_license_title') }}</h3>
        </div>
        <p id="license-staff-name" class="td-muted mb-4"></p>
        <form id="license-form" method="POST" action="">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_dir_lbl_profession') }}</label>
                <input type="text" name="profession" class="form-control" required placeholder="{{ __('staff_hr.ph_profession', [], app()->getLocale()) ?: 'e.g. Doctor, Nurse, Pharmacist' }}">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_dir_lbl_license_number') }}</label>
                <input type="text" name="license_number" class="form-control" required maxlength="100">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_dir_lbl_issuing_body') }}</label>
                <input type="text" name="issuing_body" class="form-control" required maxlength="200" placeholder="{{ __('staff_hr.ph_issuing_body', [], app()->getLocale()) ?: 'e.g. Medical Council of Cameroon' }}">
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_issue_date') }}</label>
                    <input type="date" name="issue_date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_dir_lbl_expiry_date') }}</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeLicenseModal()">{{ __('public.stf_hr_dir_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="badge-check"></i>
                    {{ __('public.stf_hr_dir_btn_add_license') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Status Modal --}}
<div id="status-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.stf_hr_dir_modal_status_title') }}</h3>
        </div>
        <p id="status-staff-name" class="td-muted mb-4"></p>
        <form id="status-form" method="POST" action="">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_dir_lbl_new_status') }}</label>
                <select id="status-select" name="status" class="form-control" required></select>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeStatusModal()">{{ __('public.stf_hr_dir_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.stf_hr_dir_btn_update') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openAddStaffModal()  { document.getElementById('add-staff-modal').classList.add('open'); }
    function closeAddStaffModal() { document.getElementById('add-staff-modal').classList.remove('open'); }
    document.getElementById('add-staff-modal').addEventListener('click', function(e) {
        if (e.target === this) closeAddStaffModal();
    });

    function openLicenseModal(staffId, staffName) {
        document.getElementById('license-staff-name').textContent = staffName;
        document.getElementById('license-form').action = '{{ url('/portals/staff/hr/directory') }}/' + staffId + '/license';
        document.getElementById('license-modal').classList.add('open');
    }
    function closeLicenseModal() { document.getElementById('license-modal').classList.remove('open'); }
    document.getElementById('license-modal').addEventListener('click', function(e) {
        if (e.target === this) closeLicenseModal();
    });

    var statusTransitions = {
        'active':     ['inactive','on_leave','suspended','terminated'],
        'inactive':   ['active','terminated'],
        'on_leave':   ['active','inactive','terminated'],
        'suspended':  ['active','terminated'],
        'terminated': [],
    };

    function openStatusModal(staffId, currentStatus, staffName) {
        document.getElementById('status-staff-name').textContent = staffName;
        document.getElementById('status-form').action = '{{ url('/portals/staff/hr/directory') }}/' + staffId + '/status';

        var select = document.getElementById('status-select');
        select.innerHTML = '';
        var options = statusTransitions[currentStatus] || [];
        options.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            select.appendChild(opt);
        });

        document.getElementById('status-modal').classList.add('open');
    }
    function closeStatusModal() { document.getElementById('status-modal').classList.remove('open'); }
    document.getElementById('status-modal').addEventListener('click', function(e) {
        if (e.target === this) closeStatusModal();
    });
</script>
@endsection
