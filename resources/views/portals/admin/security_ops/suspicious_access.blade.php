{{-- Group D import: web page over the platform's SuspiciousAccessDetectionService. No patient content:
     a flag shows the user, the type and counts. Reviewing annotates the flag; log rows are never touched. --}}
@extends('layouts.portal')
@section('title', __('security_ops.sa_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.sa_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.sa_title') }}</h2>
    <div class="page-head__spacer"></div>
    <form method="POST" action="{{ route('portals.admin.security.suspicious_access.scan') }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="radar"></i> {{ __('security_ops.sa_btn_scan') }}</button>
    </form>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.sa_subtitle', ['threshold' => $threshold, 'minutes' => $window]) }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<form method="GET" action="{{ route('portals.admin.security.suspicious_access') }}" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('security_ops.sa_col_status') }}">
        <option value="">{{ __('security_ops.sa_filter_all') }}</option>
        @foreach(['open', 'reviewed', 'dismissed', 'escalated'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __('security_ops.sa_status_'.$s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($flags->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('security_ops.sa_empty') }}</h3>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.sa_col_user') }}</th>
                    <th>{{ __('security_ops.sa_col_type') }}</th>
                    <th>{{ __('security_ops.sa_col_severity') }}</th>
                    <th>{{ __('security_ops.sa_col_evidence') }}</th>
                    <th>{{ __('security_ops.sa_col_status') }}</th>
                    <th>{{ __('security_ops.sa_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($flags as $flag)
                    <tr data-flag="{{ $flag->id }}">
                        <td data-label="{{ __('security_ops.sa_col_user') }}" class="td-strong">{{ $users[$flag->user_id]->email ?? substr($flag->user_id, 0, 12).'…' }}</td>
                        <td data-label="{{ __('security_ops.sa_col_type') }}">{{ __('security_ops.sa_type_'.$flag->flag_type) }}</td>
                        <td data-label="{{ __('security_ops.sa_col_severity') }}"><span class="badge {{ in_array($flag->severity, ['critical', 'high']) ? 'badge-danger' : 'badge-warning' }} badge-sm">@enum($flag->severity, 'severity')</span></td>
                        <td data-label="{{ __('security_ops.sa_col_evidence') }}" class="td-muted">
                            @if(isset($flag->evidence['record_count']))
                                {{ __('security_ops.sa_evidence_volume', ['count' => $flag->evidence['record_count'], 'minutes' => $flag->evidence['window_minutes'] ?? $window]) }}
                            @else
                                {{ __('security_ops.sa_evidence_emergency') }}
                            @endif
                            <div class="code-muted">{{ $flag->created_at?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td data-label="{{ __('security_ops.sa_col_status') }}">
                            {{ __('security_ops.sa_status_'.$flag->status) }}
                            @if($flag->reviewed_at)
                                <div class="code-muted">{{ __('security_ops.sa_reviewed_by', ['who' => $users[$flag->reviewed_by]->email ?? '—', 'when' => $flag->reviewed_at->format('d/m/Y H:i')]) }}</div>
                                @if($flag->review_notes)<div class="td-muted">{{ \Illuminate\Support\Str::limit($flag->review_notes, 80) }}</div>@endif
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.sa_col_actions') }}">
                            @if($flag->isOpen())
                            <form method="POST" action="{{ route('portals.admin.security.suspicious_access.review', $flag->id) }}" class="inline-form">
                                @csrf
                                <select name="decision" class="filter-select" aria-label="{{ __('security_ops.sa_col_status') }}">
                                    @foreach($decisions as $d)<option value="{{ $d }}">{{ __('security_ops.sa_status_'.$d) }}</option>@endforeach
                                </select>
                                <input type="text" name="notes" required class="form-control" placeholder="{{ __('security_ops.sa_ph_notes') }}" aria-label="{{ __('security_ops.sa_ph_notes') }}">
                                <button type="submit" class="btn btn-secondary btn-sm">{{ __('security_ops.sa_btn_review') }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
<div class="mt-4">{{ $flags->links() }}</div>
@endsection
