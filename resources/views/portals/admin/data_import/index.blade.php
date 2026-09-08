@extends('layouts.portal')
@section('title', __('staff_data.title_import'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.portal.nav_dashboard'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('staff_data.title_import'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_import_data_import_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.data_import.create') }}" class="btn btn-primary btn-sm">
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
<form method="GET" action="{{ route('portals.admin.data_import.index') }}" class="filter-bar">
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
    <a href="{{ route('portals.admin.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if(count($jobs) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="upload-cloud"></i></div>
                <h3>{{ __('public.stf_import_no_imports_title') }}</h3>
                <p>{{ __('public.stf_import_no_imports_desc') }}</p>
                <a href="{{ route('portals.admin.data_import.create') }}" class="btn btn-primary btn-sm mt-6">
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
                                'completed'                       => 'badge-success',
                                'completed_with_errors'           => 'badge-warning',
                                'failed', 'validation_failed'     => 'badge-danger',
                                'importing','approved_for_import' => 'badge-teal',
                                'validated','preview_ready'       => 'badge-primary',
                                default                           => 'badge-neutral',
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
                                    @if($job->status === 'mapping_required')
                                        <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="btn btn-primary btn-xs">{{ __('public.stf_import_map_columns_btn') }}</a>
                                    @elseif($job->status === 'preview_ready')
                                        <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="btn btn-ghost btn-xs">{{ __('public.stf_import_edit_mapping') }}</a>
                                        <form method="POST" action="{{ route('portals.admin.data_import.validate', $job->id) }}" class="inline-form">@csrf
                                            <button type="submit" class="btn btn-primary btn-xs">{{ __('public.stf_import_validate') }}</button>
                                        </form>
                                    @elseif(in_array($job->status, ['validated','validation_failed']))
                                        <a href="{{ route('portals.admin.data_import.preview', $job->id) }}" class="btn btn-primary btn-xs">{{ __('public.stf_import_preview') }}</a>
                                    @elseif($job->canBeRolledBack())
                                        <button type="button" class="btn btn-ghost btn-xs" data-rollback-job="{{ $job->id }}">{{ __('public.stf_import_rollback') }}</button>
                                    @endif

                                    <a href="{{ route('portals.admin.data_import.audit', $job->id) }}" class="btn btn-ghost btn-xs">
                                        <i data-lucide="scroll-text"></i> {{ __('public.stf_import_log') }}
                                    </a>

                                    @if($job->canBeCancelled())
                                        <form method="POST" action="{{ route('portals.admin.data_import.cancel', $job->id) }}" class="inline-form">@csrf
                                            <button type="submit" class="btn btn-ghost btn-xs" onclick="return confirm('{{ __('staff_data.confirm_cancel') }}')">{{ __('public.stf_import_cancel') }}</button>
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

{{-- Rollback modal --}}
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
                <button type="button" class="btn btn-ghost btn-sm" data-rollback-close>{{ __('public.stf_import_rollback_back') }}</button>
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
(function () {
    var modal = document.getElementById('rollback-modal');
    var form  = document.getElementById('rollback-form');
    if (!modal || !form) return;

    // Built from the named route so the URL never has to be hardcoded here.
    var template = @json(route('portals.admin.data_import.rollback', ['id' => '__ID__']));

    function close() { modal.setAttribute('hidden', ''); }

    document.querySelectorAll('[data-rollback-job]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.setAttribute('action', template.replace('__ID__', btn.getAttribute('data-rollback-job')));
            modal.removeAttribute('hidden');
        });
    });

    document.querySelectorAll('[data-rollback-close]').forEach(function (btn) {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', function (e) { if (e.target === this) close(); });
})();
</script>
@endsection
