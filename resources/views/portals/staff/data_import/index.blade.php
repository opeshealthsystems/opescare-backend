@extends('layouts.portal')

@section('title', __('staff_data.title_import', [], app()->getLocale()) ?: 'Data Import')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.cdss_sidebar_role') }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.cdss_sidebar_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_overview') }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard') }}</span>
    </a>
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_clinical') }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i><span>{{ __('public.portal.nav_appointments') }}</span>
    </a>
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i><span>{{ __('public.portal.nav_queue') }}</span>
    </a>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link">
        <i data-lucide="stethoscope"></i><span>{{ __('public.portal.nav_visits') }}</span>
    </a>
    @feature('clinical_decision_support')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_hr') }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i><span>{{ __('public.portal.nav_staff_directory') }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
        <i data-lucide="clock"></i><span>{{ __('public.portal.nav_staff_shifts') }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.roster') }}" class="sidebar-link">
        <i data-lucide="calendar-range"></i><span>{{ __('public.portal.nav_staff_roster') }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.leave') }}" class="sidebar-link">
        <i data-lucide="plane-takeoff"></i><span>{{ __('public.portal.nav_staff_leave') }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_inventory') }}</div>
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="sidebar-link">
        <i data-lucide="pill"></i><span>{{ __('public.portal.nav_inventory_pharmacy') }}</span>
    </a>
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link">
        <i data-lucide="droplets"></i><span>{{ __('public.portal.nav_inventory_blood') }}</span>
    </a>
</div>
@endfeature
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_supply') }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('public.portal.nav_supply') }}</span>
    </a>
</div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_operations') }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i><span>{{ __('public.portal.nav_billing') }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i><span>{{ __('public.portal.nav_support') }}</span>
    </a>
    @feature('insurance')
    <a href="{{ route('portals.insurance.policies') }}" class="sidebar-link">
        <i data-lucide="shield-check"></i><span>{{ __('public.portal.nav_insurance') }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link active">
        <i data-lucide="upload-cloud"></i><span>{{ __('public.portal.nav_data_import') }}</span>
    </a>
</div>
@endsection

@section('breadcrumb_home', __('staff_data.bc_home', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_data.bc_section', [], app()->getLocale()) ?: 'Data Import')

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_import_data_import_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.data_import.create') }}" class="btn btn-primary btn-sm">
        <i data-lucide="plus-circle"></i>
        {{ __('public.stf_import_new_import') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('public.stf_import_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('portals.staff.data_import.index') }}" class="filter-bar">
    <select name="status" class="filter-select">
        <option value="">{{ __('public.stf_import_all_statuses') }}</option>
        @foreach(['uploaded','mapping_required','preview_ready','validated','validation_failed','approved_for_import','importing','completed','completed_with_errors','failed','rolled_back','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@enum($s)</option>
        @endforeach
    </select>
    <select name="import_type" class="filter-select">
        <option value="">{{ __('public.stf_import_all_types') }}</option>
        @foreach($importTypes as $key => $def)
            <option value="{{ $key }}" {{ request('import_type') === $key ? 'selected' : '' }}>{{ $def['label'] }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.stf_import_filter') }}
    </button>
    <a href="{{ route('portals.staff.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if(count($jobs) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="upload-cloud"></i></div>
                <h3>{{ __('public.stf_import_no_imports_title') }}</h3>
                <p>{{ __('public.stf_import_no_imports_desc') }}</p>
                <a href="{{ route('portals.staff.data_import.create') }}" class="btn btn-primary btn-sm mt-6">
                    {{ __('public.stf_import_start') }}
                </a>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_import_col_file') }}</th>
                            <th>{{ __('public.stf_import_col_type') }}</th>
                            <th>{{ __('public.stf_import_col_status') }}</th>
                            <th>{{ __('public.stf_import_col_rows') }}</th>
                            <th>{{ __('public.stf_import_col_created') }}</th>
                            <th>{{ __('public.stf_import_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                        @php
                            $statusBadge = match($job->status) {
                                'completed'                => 'badge-success',
                                'completed_with_errors'    => 'badge-warning',
                                'failed', 'validation_failed' => 'badge-danger',
                                'importing','approved_for_import' => 'badge-teal',
                                'validated','preview_ready' => 'badge-primary',
                                'rolled_back','cancelled'  => 'badge-neutral',
                                default                    => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_import_col_file') }}">
                                <span class="td-strong">{{ $job->original_filename }}</span>
                                <div class="td-muted">{{ strtoupper($job->file_extension) }} · {{ number_format($job->file_size_bytes / 1024, 1) }} KB</div>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_type') }}">
                                <span class="badge badge-neutral">{{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_status') }}">
                                <span class="badge {{ $statusBadge }}">@enum($job->status)</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_rows') }}">
                                @if($job->total_rows > 0)
                                    <span class="badge badge-success">{{ $job->valid_rows }}</span>
                                    @if($job->invalid_rows > 0)
                                        <span class="badge badge-danger">{{ $job->invalid_rows }}</span>
                                    @endif
                                    <span class="td-muted">/ {{ $job->total_rows }} {{ __('public.stf_import_total') }}</span>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_import_col_created') }}" class="td-muted">
                                {{ \Carbon\Carbon::parse($job->created_at)->format('M d, Y H:i') }}
                            </td>
                            <td data-label="{{ __('public.stf_import_col_actions') }}">
                                <div class="row-actions-inline">
                                    {{-- Continue wizard --}}
                                    @if($job->status === 'mapping_required')
                                        <a href="{{ route('portals.staff.data_import.mapping', $job->id) }}" class="btn btn-primary btn-xs">{{ __('public.stf_import_map_columns_btn') }}</a>
                                    @elseif(in_array($job->status, ['preview_ready']))
                                        <a href="{{ route('portals.staff.data_import.mapping', $job->id) }}" class="btn btn-ghost btn-xs">{{ __('public.stf_import_edit_mapping') }}</a>
                                        <form method="POST" action="{{ route('portals.staff.data_import.validate', $job->id) }}" class="inline-form">@csrf
                                            <button type="submit" class="btn btn-primary btn-xs">{{ __('public.stf_import_validate') }}</button>
                                        </form>
                                    @elseif(in_array($job->status, ['validated','validation_failed']))
                                        <a href="{{ route('portals.staff.data_import.preview', $job->id) }}" class="btn btn-primary btn-xs">{{ __('public.stf_import_preview') }}</a>
                                    @elseif($job->canBeRolledBack())
                                        <button type="button" class="btn btn-ghost btn-xs" onclick="openRollbackModal('{{ $job->id }}')">{{ __('public.stf_import_rollback') }}</button>
                                    @endif

                                    {{-- Audit log --}}
                                    <a href="{{ route('portals.staff.data_import.audit', $job->id) }}" class="btn btn-ghost btn-xs">
                                        <i data-lucide="scroll-text"></i> {{ __('public.stf_import_log') }}
                                    </a>

                                    {{-- Cancel --}}
                                    @if($job->canBeCancelled())
                                        <form method="POST" action="{{ route('portals.staff.data_import.cancel', $job->id) }}" class="inline-form">@csrf
                                            <button type="submit" class="btn btn-ghost btn-xs" onclick="return confirm('{{ __('staff_data.confirm_cancel', [], app()->getLocale()) ?: 'Cancel this import?' }}')">{{ __('public.stf_import_cancel') }}</button>
                                        </form>
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

{{-- Rollback Modal --}}
<div id="rollback-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="rotate-ccw"></i> {{ __('public.stf_import_rollback_modal_title') }}</h3>
        <form id="rollback-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p>{{ __('public.stf_import_rollback_desc') }}</p>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_import_rollback_reason') }} <span class="td-muted">{{ __('public.stf_import_rollback_optional') }}</span></label>
                    <textarea name="reason" class="form-control" rows="3" maxlength="500" placeholder="{{ __('public.stf_import_rollback_ph') }}"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeRollbackModal()">{{ __('public.stf_import_rollback_back') }}</button>
                <button type="submit" class="btn btn-danger btn-sm">
                    <i data-lucide="rotate-ccw"></i> {{ __('public.stf_import_rollback_btn') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openRollbackModal(jobId) {
        document.getElementById('rollback-form').setAttribute('action', '{{ url('/portals/staff/data-import') }}/' + jobId + '/rollback');
        document.getElementById('rollback-modal').removeAttribute('hidden');
    }
    function closeRollbackModal() { document.getElementById('rollback-modal').setAttribute('hidden',''); }
    document.getElementById('rollback-modal').addEventListener('click', function(e) {
        if (e.target === this) closeRollbackModal();
    });
</script>
@endsection
