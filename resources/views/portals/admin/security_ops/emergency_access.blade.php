@extends('layouts.portal')
@section('title', __('public.adm_secops_emerg_title'))
@include('portals.admin.security_ops._sidebar')
@section('breadcrumb_home', __('public.adm_secops_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_secops_sidebar_emergency_access'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_secops_emerg_title') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_secops_emerg_subtitle') }}</p>
    </div>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('portals.admin.security.emergency_access') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="provider_id" value="{{ request('provider_id') }}" placeholder="{{ __('public.adm_secops_emerg_filter_provider') }}" aria-label="{{ __('public.adm_secops_emerg_filter_provider') }}">
    </label>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="facility_id" value="{{ request('facility_id') }}" placeholder="{{ __('public.adm_secops_emerg_filter_facility') }}" aria-label="{{ __('public.adm_secops_emerg_filter_facility') }}">
    </label>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_secops_btn_filter') }}</button>
    <a href="{{ route('portals.admin.security.emergency_access') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_secops_btn_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('public.adm_secops_emerg_empty_heading') }}</h3>
                <p>{{ __('public.adm_secops_emerg_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_secops_emerg_col_patient') }}</th><th>{{ __('public.adm_secops_emerg_col_provider') }}</th><th>{{ __('public.adm_secops_emerg_col_facility') }}</th><th>{{ __('public.adm_secops_emerg_col_reason') }}</th><th>{{ __('public.adm_secops_emerg_col_records') }}</th><th>{{ __('public.adm_secops_emerg_col_datetime') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($events as $ev)
                    <tr>
                        <td data-label="{{ __('public.adm_secops_emerg_col_patient') }}" class="td-strong">
                            {{ $ev->patient?->health_id ?? ($ev->patient_id ? substr($ev->patient_id,0,12).'…' : '—') }}
                        </td>
                        <td data-label="{{ __('public.adm_secops_emerg_col_provider') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->provider_id ? substr($ev->provider_id,0,12).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_secops_emerg_col_facility') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->facility_id ? substr($ev->facility_id,0,12).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_secops_emerg_col_reason') }}">{{ Str::limit($ev->reason ?? '—', 60) }}</td>
                        <td data-label="{{ __('public.adm_secops_emerg_col_records') }}">
                            @if($ev->records_viewed)
                                <span class="badge badge-warning badge-sm">
                                    {{ is_array($ev->records_viewed) ? count($ev->records_viewed) : '?' }} {{ __('public.adm_secops_emerg_records_suffix') }}
                                </span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_secops_emerg_col_datetime') }}" class="td-muted">
                            {{ \Carbon\Carbon::parse($ev->created_at)->format('M d, Y H:i') }}
                            <div class="code-muted">{{ \Carbon\Carbon::parse($ev->created_at)->diffForHumans() }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $events->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
