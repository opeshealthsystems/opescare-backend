@extends('layouts.portal')
@section('title', __('healthorg.reports_title') . ' — OpesCare')
@section('sidebar_role_badge')
<div class="sidebar-role-badge"><i data-lucide="heart-handshake"></i> {{ __('public.healthorg_portal.role_badge', [], app()->getLocale()) ?: 'Health Org' }}</div>
@endsection
@section('sidebar_user_role', __('public.healthorg_portal.role_label', [], app()->getLocale()) ?: 'Health Org Admin')
@section('sidebar_nav')@include('portals.healthorg._sidebar')@endsection
@section('breadcrumb_home', __('public.healthorg_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Health Org Portal')
@section('breadcrumb_home_url', route('portals.healthorg.dashboard'))
@section('breadcrumb_section', __('healthorg.reports_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('healthorg.reports_title') }}</h1>
        <p class="page-subtitle">{{ __('healthorg.reports_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('new-report').hidden = !document.getElementById('new-report').hidden">
        <i data-lucide="plus"></i> {{ __('healthorg.new_report') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-report" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-plus"></i> {{ __('healthorg.new_report') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.healthorg.reports.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;"><label class="form-label" for="report_type_id">{{ __('healthorg.field_report_type') }}</label>
                    <select id="report_type_id" name="report_type_id" class="form-control" required>
                        <option value="">—</option>
                        @foreach($reportTypes as $rt)<option value="{{ $rt->id }}" @selected(old('report_type_id')===$rt->id)>{{ $rt->name }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="reporting_period_start">{{ __('healthorg.field_period_start') }}</label>
                    <input type="date" id="reporting_period_start" name="reporting_period_start" class="form-control" value="{{ old('reporting_period_start') }}" required></div>
                <div><label class="form-label" for="reporting_period_end">{{ __('healthorg.field_period_end') }}</label>
                    <input type="date" id="reporting_period_end" name="reporting_period_end" class="form-control" value="{{ old('reporting_period_end') }}" required></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="notes">{{ __('healthorg.field_notes') }}</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('healthorg.create_report') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('healthorg.field_report_type') }}</th><th>{{ __('healthorg.col_period') }}</th>
                <th>{{ __('healthorg.field_status') }}</th><th>{{ __('healthorg.col_created') }}</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td data-label="{{ __('healthorg.field_report_type') }}"><span class="td-strong">{{ $report->reportType?->name ?? '—' }}</span></td>
                    <td data-label="{{ __('healthorg.col_period') }}"><span class="td-muted">{{ $report->reporting_period_start?->isoFormat('ll') }} → {{ $report->reporting_period_end?->isoFormat('ll') }}</span></td>
                    <td data-label="{{ __('healthorg.field_status') }}">
                        <span class="badge badge-{{ match($report->status) { 'submitted','approved' => 'success', 'draft' => 'warning', 'rejected' => 'danger', default => 'neutral' } }}">@enum($report->status)</span>
                    </td>
                    <td data-label="{{ __('healthorg.col_created') }}"><span class="td-muted">{{ $report->created_at?->isoFormat('ll') }}</span></td>
                    <td class="row-actions">
                        @if($report->status === 'draft')
                        <form method="POST" action="{{ route('portals.healthorg.reports.submit', $report->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="send"></i> {{ __('healthorg.submit_report') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('healthorg.no_reports') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $reports->links() }}</div>
</div>

@endsection
