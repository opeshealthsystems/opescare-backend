@extends('layouts.portal')
@section('title', __('public.adm_secops_index_title'))
@include('portals.admin.security_ops._sidebar')
@section('breadcrumb_home', __('public.adm_secops_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_secops_index_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_secops_index_title') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_secops_index_subtitle') }}</p>
    </div>
</div>

{{-- Critical incidents banner --}}
@if($stats['critical_incidents'] > 0)
<div class="banner banner--danger">
    <i data-lucide="shield-alert"></i>
    <strong>{{ $stats['critical_incidents'] }} {{ __('public.adm_secops_index_critical_banner') }}</strong>
    <div class="banner__spacer"></div>
    <a href="{{ route('portals.admin.security.incidents', ['severity' => 'critical', 'status' => 'open']) }}"
       class="btn btn-danger btn-sm">{{ __('public.adm_secops_index_btn_view_now') }}</a>
</div>
@endif

{{-- KPI cards --}}
<div class="stat-grid mb-6">
    @php $kpis = [
        [__('public.adm_secops_kpi_open_incidents'),       $stats['open_incidents'],     'file-warning', $stats['critical_incidents'] > 0 ? 'danger' : 'primary', route('portals.admin.security.incidents')],
        [__('public.adm_secops_kpi_emergency_accesses'),   $stats['emergency_accesses'], 'siren',        'warning', route('portals.admin.security.emergency_access')],
        [__('public.adm_secops_kpi_audit_today'),          $stats['audit_events_today'], 'search-code',  '', route('portals.admin.security.audit_explorer')],
        [__('public.adm_secops_kpi_critical_open'),        $stats['critical_incidents'], 'skull',        'danger', route('portals.admin.security.incidents', ['severity'=>'critical'])],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $variant, $url])
    <a href="{{ $url }}" class="stat-card {{ $variant ? 'stat-card--'.$variant : '' }}">
        <div class="stat-card__head">
            <i data-lucide="{{ $icon }}"></i>
            <span class="stat-card__label">{{ $label }}</span>
        </div>
        <div class="stat-card__value">{{ $count }}</div>
    </a>
    @endforeach
</div>

<div class="grid-2">

{{-- Recent Incidents --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-warning"></i> {{ __('public.adm_secops_index_panel_incidents') }}</h3>
        <a href="{{ route('portals.admin.security.incidents') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_secops_index_btn_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($recentIncidents->isEmpty())
            <div class="td-muted empty-cell">{{ __('public.adm_secops_index_no_incidents') }}</div>
        @else
        <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('public.adm_secops_inc_col_type') }}</th>
                <th>{{ __('public.adm_secops_inc_col_severity') }}</th>
                <th>{{ __('public.adm_secops_inc_col_status') }}</th>
                <th>{{ __('public.adm_secops_index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentIncidents as $inc)
                @php $sevBadge = match($inc->severity) { 'critical'=>'badge-danger', 'high'=>'badge-warning', 'medium'=>'badge-primary', default=>'badge-neutral' }; @endphp
                <tr>
                    <td data-label="{{ __('public.adm_secops_inc_col_type') }}">{{ $inc->incident_type }}</td>
                    <td data-label="{{ __('public.adm_secops_inc_col_severity') }}"><span class="badge {{ $sevBadge }} badge-sm">@enum($inc->severity, 'severity')</span></td>
                    <td data-label="{{ __('public.adm_secops_inc_col_status') }}"><span class="badge badge-neutral badge-sm">@enum($inc->status)</span></td>
                    <td data-label="{{ __('public.adm_secops_index_col_when') }}" class="td-muted">{{ \Carbon\Carbon::parse($inc->detected_at)->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

{{-- Recent Emergency Access --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="siren"></i> {{ __('public.adm_secops_index_panel_emergency') }}</h3>
        <a href="{{ route('portals.admin.security.emergency_access') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_secops_index_btn_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($recentEmergency->isEmpty())
            <div class="td-muted empty-cell">{{ __('public.adm_secops_index_no_emergency') }}</div>
        @else
        <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('public.adm_secops_emerg_col_patient') }}</th>
                <th>{{ __('public.adm_secops_emerg_col_provider') }}</th>
                <th>{{ __('public.adm_secops_emerg_col_reason') }}</th>
                <th>{{ __('public.adm_secops_index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentEmergency as $ev)
                <tr>
                    <td data-label="{{ __('public.adm_secops_emerg_col_patient') }}" class="td-strong">{{ $ev->patient?->health_id ?? substr($ev->patient_id,0,8).'…' }}</td>
                    <td data-label="{{ __('public.adm_secops_emerg_col_provider') }}" class="td-muted">{{ $ev->provider_id ? substr($ev->provider_id,0,8).'…' : '—' }}</td>
                    <td data-label="{{ __('public.adm_secops_emerg_col_reason') }}">{{ Str::limit($ev->reason,40) }}</td>
                    <td data-label="{{ __('public.adm_secops_index_col_when') }}" class="td-muted">{{ \Carbon\Carbon::parse($ev->created_at)->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

</div>
@endsection
