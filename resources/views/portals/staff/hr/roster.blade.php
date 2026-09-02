@extends('layouts.portal')

@section('title', __('staff_hr.title_roster', [], app()->getLocale()) ?: 'Duty Roster')

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
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i>
        <span>{{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'Directory' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
        <i data-lucide="clock"></i>
        <span>{{ __('public.portal.nav_staff_shifts', [], app()->getLocale()) ?: 'Shifts' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.roster') }}" class="sidebar-link active">
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
@section('breadcrumb_section', __('public.stf_hr_roster_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.stf_hr_roster_title') }}</h1>
        <p class="page-subtitle">{{ __('public.stf_hr_roster_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openRosterModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.stf_hr_roster_new_btn') }}
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
<form method="GET" action="{{ route('portals.staff.hr.roster') }}" class="filter-bar">
    <select name="status" class="form-control">
        <option value="">{{ __('public.stf_hr_roster_all_statuses') }}</option>
        @foreach(['draft','published','archived'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@enum($s)</option>
        @endforeach
    </select>
    @if($departments->isNotEmpty())
    <select name="department" class="form-control">
        <option value="">{{ __('public.stf_hr_roster_all_departments') }}</option>
        @foreach($departments as $d)
            <option value="{{ $d }}" {{ request('department') === $d ? 'selected' : '' }}>{{ $d }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.stf_hr_roster_filter_btn') }}
    </button>
    <a href="{{ route('portals.staff.hr.roster') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_hr_roster_clear_btn') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($rosters->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="calendar-range"></i></div>
                <h3>{{ __('public.stf_hr_roster_empty_title') }}</h3>
                <p>{{ __('public.stf_hr_roster_empty_desc') }}</p>
                <button type="button" class="btn btn-primary btn-sm mt-6" onclick="openRosterModal()">{{ __('public.stf_hr_roster_new_btn') }}</button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_hr_roster_col_department') }}</th>
                            <th>{{ __('public.stf_hr_roster_col_period') }}</th>
                            <th>{{ __('public.stf_hr_roster_col_assignments') }}</th>
                            <th>{{ __('public.stf_hr_roster_col_status') }}</th>
                            <th>{{ __('public.stf_hr_roster_col_published') }}</th>
                            <th>{{ __('public.stf_hr_roster_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rosters as $roster)
                        @php
                            $rBadge = match($roster->status) {
                                'draft'     => 'badge-warning',
                                'published' => 'badge-success',
                                'archived'  => 'badge-neutral',
                                default     => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_hr_roster_col_department') }}"><strong class="td-strong">{{ $roster->department }}</strong></td>
                            <td data-label="{{ __('public.stf_hr_roster_col_period') }}">
                                {{ \Carbon\Carbon::parse($roster->period_start)->format('M d') }} –
                                {{ \Carbon\Carbon::parse($roster->period_end)->format('M d, Y') }}
                            </td>
                            <td data-label="{{ __('public.stf_hr_roster_col_assignments') }}">{{ $roster->assignments_count }}</td>
                            <td data-label="{{ __('public.stf_hr_roster_col_status') }}">
                                <span class="badge {{ $rBadge }}">@enum($roster->status)</span>
                            </td>
                            <td data-label="{{ __('public.stf_hr_roster_col_published') }}">
                                {{ $roster->published_at ? \Carbon\Carbon::parse($roster->published_at)->format('M d, Y') : '—' }}
                            </td>
                            <td data-label="{{ __('public.stf_hr_roster_col_actions') }}">
                                <div class="row-actions-inline">
                                    @if($roster->status === 'draft')
                                        <button type="button" class="btn btn-ghost btn-xs"
                                            onclick="openAssignModal('{{ $roster->id }}', '{{ addslashes($roster->department) }}')">
                                            <i data-lucide="user-plus"></i>
                                            {{ __('public.stf_hr_roster_btn_assign') }}
                                        </button>
                                        <form method="POST" action="{{ route('portals.staff.hr.roster.publish', $roster->id) }}" class="inline-form">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-xs">
                                                <i data-lucide="send"></i>
                                                {{ __('public.stf_hr_roster_btn_publish') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if(in_array($roster->status, ['draft','published']))
                                        <button type="button" class="btn btn-ghost btn-xs"
                                            onclick="openArchiveModal('{{ route('portals.staff.hr.roster.archive', $roster->id) }}')">
                                            <i data-lucide="archive"></i>
                                            {{ __('public.stf_hr_roster_btn_archive') }}
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

{{-- New Roster Modal --}}
<div id="roster-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.stf_hr_roster_modal_title') }}</h3>
        </div>
        <form method="POST" action="{{ route('portals.staff.hr.roster.store') }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_department') }}</label>
                <input type="text" name="department" class="form-control" required maxlength="100" placeholder="{{ __('public.stf_hr_roster_ph_department') }}">
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_roster_lbl_period_start') }}</label>
                    <input type="date" name="period_start" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_hr_roster_lbl_period_end') }}</label>
                    <input type="date" name="period_end" class="form-control" required>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeRosterModal()">{{ __('public.stf_hr_roster_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="calendar-range"></i>
                    {{ __('public.stf_hr_roster_btn_create') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Assign Staff Modal --}}
<div id="assign-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.stf_hr_roster_assign_title') }}</h3>
        </div>
        <p id="assign-dept-label" class="td-muted mb-4"></p>
        <form id="assign-form" method="POST" action="">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_staff') }}</label>
                <select name="staff_profile_id" class="form-control" required>
                    <option value="">{{ __('staff_hr.select_placeholder', [], app()->getLocale()) ?: '— Select —' }}</option>
                    @foreach($staff as $member)
                        <option value="{{ $member->id }}">{{ $member->full_name }} ({{ $member->job_title ?? $member->staff_category }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_shift') }}</label>
                <select name="staff_shift_id" class="form-control" required>
                    <option value="">{{ __('staff_hr.select_placeholder', [], app()->getLocale()) ?: '— Select —' }}</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }}–{{ $shift->end_time }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_work_date') }}</label>
                <input type="date" name="work_date" class="form-control" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_hr_roster_lbl_notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="300"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeAssignModal()">{{ __('public.stf_hr_roster_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="user-plus"></i>
                    {{ __('public.stf_hr_roster_btn_assign_confirm') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Archive confirm modal --}}
<div id="archive-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title"><i data-lucide="archive"></i> {{ __('public.stf_hr_roster_archive_title') }}</h3>
        </div>
        <div class="modal__body">{{ __('public.stf_hr_roster_archive_body') }}</div>
        <form id="archive-form" method="POST" action="">
            @csrf
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeArchiveModal()">{{ __('public.stf_hr_roster_cancel') }}</button>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i data-lucide="archive"></i>
                    {{ __('public.stf_hr_roster_btn_archive_confirm') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openRosterModal()  { document.getElementById('roster-modal').classList.add('open'); }
    function closeRosterModal() { document.getElementById('roster-modal').classList.remove('open'); }
    document.getElementById('roster-modal').addEventListener('click', function(e) {
        if (e.target === this) closeRosterModal();
    });

    function openAssignModal(rosterId, dept) {
        document.getElementById('assign-dept-label').textContent = @json(__('staff_hr.js_department_label', ['dept' => '__DEPT__'], app()->getLocale()) ?: 'Department: __DEPT__').replace('__DEPT__', dept);
        document.getElementById('assign-form').action = '{{ url('/portals/staff/hr/roster') }}/' + rosterId + '/assign';
        document.getElementById('assign-modal').classList.add('open');
    }
    function closeAssignModal() { document.getElementById('assign-modal').classList.remove('open'); }
    document.getElementById('assign-modal').addEventListener('click', function(e) {
        if (e.target === this) closeAssignModal();
    });

    function openArchiveModal(action) {
        document.getElementById('archive-form').action = action;
        document.getElementById('archive-modal').classList.add('open');
    }
    function closeArchiveModal() { document.getElementById('archive-modal').classList.remove('open'); }
    document.getElementById('archive-modal').addEventListener('click', function(e) {
        if (e.target === this) closeArchiveModal();
    });
</script>
@endsection
