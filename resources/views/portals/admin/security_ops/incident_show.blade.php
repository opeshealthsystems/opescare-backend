@extends('layouts.portal')
@section('title', __('admin_desk.incident_detail'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.inc_title'))
@section('breadcrumb_home_url', route('portals.admin.security.incidents'))
@section('breadcrumb_section', __('admin_desk.incident_detail'))

@section('content')
@php
    $sevBadge = match($incident->severity) {
        'critical' => 'badge-danger',
        'high' => 'badge-warning',
        'medium' => 'badge-primary',
        default => 'badge-neutral',
    };
    $stBadge = match($incident->status) {
        'open' => 'badge-danger',
        'investigating' => 'badge-warning',
        'contained' => 'badge-primary',
        'resolved', 'closed' => 'badge-success',
        default => 'badge-neutral',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $incident->incident_type }}</h1>
        <p class="page-subtitle">
            <span class="badge {{ $sevBadge }}">@enum($incident->severity, 'severity')</span>
            <span class="badge {{ $stBadge }}">@enum($incident->status)</span>
            @if($incident->is_demo)<span class="badge badge-neutral">{{ __('security_ops.badge_demo') }}</span>@endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.security.incidents') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="adm-detail-grid">
<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('security_ops.inc_col_summary') }}</h3></div>
        <div class="panel-body adm-pre">{{ $incident->summary }}</div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('admin_desk.details') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('security_ops.inc_col_detected') }}</td><td class="kv-strong">{{ $incident->detected_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('security_ops.inc_form_contained_at') }}</td><td class="kv-strong">{{ $incident->contained_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('security_ops.inc_col_resolved') }}</td><td class="kv-strong">{{ $incident->resolved_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.opened_by') }}</td><td class="kv-strong">{{ $incident->creator?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.created_at') }}</td><td class="kv-strong">{{ $incident->created_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.reference') }}</td><td class="kv-strong adm-mono">{{ $incident->id }}</td></tr>
            </table>
        </div>
    </div>
</div>

<div>
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="pencil"></i> {{ __('security_ops.inc_modal_update_title') }}</h3></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.security.incidents.update', $incident->id) }}">
                @csrf
                <input type="hidden" name="stay" value="1">
                <div class="form-group mb-3">
                    <label class="form-label" for="upd-status">{{ __('security_ops.inc_form_status') }}</label>
                    <select id="upd-status" name="status" class="form-control" required>
                        @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $incident->status) === $st)>@enum($st)</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="upd-summary">{{ __('security_ops.inc_form_summary') }}</label>
                    <textarea id="upd-summary" name="summary" class="form-control" rows="5" maxlength="2000">{{ old('summary', $incident->summary) }}</textarea>
                </div>
                <div class="form-row mb-4">
                    <div class="form-group">
                        <label class="form-label" for="upd-contained">{{ __('security_ops.inc_form_contained_at') }}</label>
                        <input id="upd-contained" type="date" name="contained_at" class="form-control" value="{{ old('contained_at', $incident->contained_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="upd-resolved">{{ __('security_ops.inc_form_resolved_at') }}</label>
                        <input id="upd-resolved" type="date" name="resolved_at" class="form-control" value="{{ old('resolved_at', $incident->resolved_at?->format('Y-m-d')) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('security_ops.inc_btn_save') }}</button>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
