@extends('layouts.portal')
@section('title', __('security_ops.inc_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.inc_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.inc_title') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm" data-call="openCreateModal">
        <i data-lucide="plus-circle"></i> {{ __('security_ops.inc_btn_log') }}
    </button>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.inc_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<form method="GET" action="{{ route('portals.admin.security.incidents') }}" class="filter-bar">
    <select name="severity" class="filter-select" aria-label="{{ __('security_ops.inc_filter_severity') }}">
        <option value="">{{ __('security_ops.inc_filter_all_severities') }}</option>
        @foreach(['critical', 'high', 'medium', 'low'] as $sev)
            <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>@enum($sev, 'severity')</option>
        @endforeach
    </select>
    <select name="status" class="filter-select" aria-label="{{ __('security_ops.inc_filter_status') }}">
        <option value="">{{ __('security_ops.inc_filter_all_statuses') }}</option>
        @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>@enum($st)</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
    <a href="{{ route('portals.admin.security.incidents') }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($incidents->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('security_ops.inc_empty_heading') }}</h3>
                <p>{{ __('security_ops.inc_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.inc_col_type') }}</th>
                    <th>{{ __('security_ops.inc_col_severity') }}</th>
                    <th>{{ __('security_ops.inc_col_status') }}</th>
                    <th>{{ __('security_ops.inc_col_summary') }}</th>
                    <th>{{ __('security_ops.inc_col_detected') }}</th>
                    <th>{{ __('security_ops.inc_col_resolved') }}</th>
                    <th>{{ __('security_ops.inc_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($incidents as $inc)
                    @php
                        $sevBadge = match($inc->severity) {
                            'critical' => 'badge-danger',
                            'high' => 'badge-warning',
                            'medium' => 'badge-primary',
                            default => 'badge-neutral',
                        };
                        $stBadge = match($inc->status) {
                            'open' => 'badge-danger',
                            'investigating' => 'badge-warning',
                            'contained' => 'badge-primary',
                            'resolved', 'closed' => 'badge-success',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('security_ops.inc_col_type') }}" class="td-strong">
                            {{ $inc->incident_type }}
                            @if($inc->is_demo)
                                <span class="badge badge-neutral badge-sm">{{ __('security_ops.badge_demo') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.inc_col_severity') }}"><span class="badge {{ $sevBadge }} badge-sm">@enum($inc->severity, 'severity')</span></td>
                        <td data-label="{{ __('security_ops.inc_col_status') }}"><span class="badge {{ $stBadge }} badge-sm">@enum($inc->status)</span></td>
                        <td data-label="{{ __('security_ops.inc_col_summary') }}">{{ Str::limit($inc->summary, 80) }}</td>
                        <td data-label="{{ __('security_ops.inc_col_detected') }}" class="td-muted">{{ $inc->detected_at?->translatedFormat('M d, Y') }}</td>
                        <td data-label="{{ __('security_ops.inc_col_resolved') }}" class="td-muted">{{ $inc->resolved_at?->translatedFormat('M d, Y') ?? '—' }}</td>
                        <td class="row-actions">
                            <button type="button" class="btn btn-ghost btn-sm"
                                data-call="openUpdateModal" data-args="{{ json_encode([$inc->id, $inc->status, $inc->summary, $inc->contained_at?->format('Y-m-d') ?? '', $inc->resolved_at?->format('Y-m-d') ?? '']) }}">
                                <i data-lucide="pencil"></i> {{ __('security_ops.inc_btn_update') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $incidents->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Create modal --}}
<div id="create-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('security_ops.inc_modal_create_title') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('security_ops.btn_close') }}" data-call="closeCreateModal"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="{{ route('portals.admin.security.incidents.store') }}">
            @csrf
            <div class="form-row mb-3">
                <div class="form-group">
                    <label class="form-label" for="inc-type">{{ __('security_ops.inc_form_type') }}</label>
                    <input id="inc-type" type="text" name="incident_type" class="form-control" required maxlength="100"
                           placeholder="{{ __('security_ops.inc_form_type_placeholder') }}" value="{{ old('incident_type') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="inc-sev">{{ __('security_ops.inc_form_severity') }}</label>
                    <select id="inc-sev" name="severity" class="form-control" required>
                        @foreach(['low', 'medium', 'high', 'critical'] as $sev)
                            <option value="{{ $sev }}" {{ old('severity') === $sev ? 'selected' : '' }}>@enum($sev, 'severity')</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="inc-detected">{{ __('security_ops.inc_form_detected_at') }}</label>
                <input id="inc-detected" type="datetime-local" name="detected_at" class="form-control" required
                       value="{{ old('detected_at', now()->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="inc-summary">{{ __('security_ops.inc_form_summary') }}</label>
                <textarea id="inc-summary" name="summary" class="form-control" rows="4" required maxlength="2000"
                          placeholder="{{ __('security_ops.inc_form_summary_placeholder') }}">{{ old('summary') }}</textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" data-call="closeCreateModal">{{ __('security_ops.btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('security_ops.inc_btn_log') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Update modal --}}
<div id="update-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('security_ops.inc_modal_update_title') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('security_ops.btn_close') }}" data-call="closeUpdateModal"><i data-lucide="x"></i></button>
        </div>
        <form id="update-form" method="POST" action="">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label" for="upd-status">{{ __('security_ops.inc_form_status') }}</label>
                <select id="upd-status" name="status" class="form-control" required>
                    @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
                        <option value="{{ $st }}">@enum($st)</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="upd-summary">{{ __('security_ops.inc_form_summary') }}</label>
                <textarea id="upd-summary" name="summary" class="form-control" rows="4" maxlength="2000"></textarea>
            </div>
            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label" for="upd-contained">{{ __('security_ops.inc_form_contained_at') }}</label>
                    <input id="upd-contained" type="date" name="contained_at" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="upd-resolved">{{ __('security_ops.inc_form_resolved_at') }}</label>
                    <input id="upd-resolved" type="date" name="resolved_at" class="form-control">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" data-call="closeUpdateModal">{{ __('security_ops.btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('security_ops.inc_btn_save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
function openCreateModal()  { document.getElementById('create-modal').style.display = 'flex'; }
function closeCreateModal() { document.getElementById('create-modal').style.display = 'none'; }
function openUpdateModal(id, status, summary, containedAt, resolvedAt) {
    document.getElementById('update-form').action = '{{ url('/admin/security/incidents') }}/' + id;
    document.getElementById('upd-status').value = status;
    document.getElementById('upd-summary').value = summary || '';
    document.getElementById('upd-contained').value = containedAt;
    document.getElementById('upd-resolved').value = resolvedAt;
    document.getElementById('update-modal').style.display = 'flex';
}
function closeUpdateModal() { document.getElementById('update-modal').style.display = 'none'; }
['create-modal', 'update-modal'].forEach(function (id) {
    document.getElementById(id).addEventListener('click', function (e) { if (e.target === this) this.style.display = 'none'; });
});
@if($errors->any())
openCreateModal();
@endif
</script>
@endsection
