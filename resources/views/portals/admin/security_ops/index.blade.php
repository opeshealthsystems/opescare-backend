@extends('layouts.portal')
@section('title', __('security_ops.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.index_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.index_title') }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.index_subtitle') }}</p>

{{-- The one thing this desk exists for. Unreviewed overrides are shown before
     anything else, and loudly; a reviewed-clean state says so explicitly
     rather than showing nothing (silence and "all clear" must not look alike). --}}
@if($stats['unreviewed_breakglass'] > 0)
<div class="alert alert-danger mb-6">
    <i data-lucide="siren"></i>
    <div>
        <strong>{{ $stats['unreviewed_breakglass'] }} {{ __('security_ops.index_banner_unreviewed') }}</strong>
    </div>
    <a href="{{ route('portals.admin.security.emergency_access', ['review' => 'unreviewed']) }}" class="btn btn-danger btn-sm">
        {{ __('security_ops.index_btn_review_now') }}
    </a>
</div>
@else
<div class="alert alert-success mb-6">
    <i data-lucide="shield-check"></i>
    <div>{{ __('security_ops.index_banner_none') }}</div>
</div>
@endif

{{-- KPI cards --}}
<div class="stat-grid mb-6">
    @php $kpis = [
        [__('security_ops.kpi_unreviewed'),         $stats['unreviewed_breakglass'], 'siren',        route('portals.admin.security.emergency_access', ['review' => 'unreviewed'])],
        [__('security_ops.kpi_live_grants'),        $stats['live_grants'],           'unlock',       route('portals.admin.security.emergency_access', ['grant' => 'live'])],
        [__('security_ops.kpi_breakglass_7d'),      $stats['breakglass_7d'],         'clock-alert',  route('portals.admin.security.emergency_access')],
        [__('security_ops.kpi_open_incidents'),     $stats['open_incidents'],        'file-warning', route('portals.admin.security.incidents')],
        [__('security_ops.kpi_critical_incidents'), $stats['critical_incidents'],    'shield-alert', route('portals.admin.security.incidents', ['severity' => 'critical'])],
        [__('security_ops.kpi_audit_today'),        $stats['audit_events_today'],    'search-code',  route('portals.admin.security.audit_explorer')],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $url])
    <a href="{{ $url }}" class="stat-card">
        <div class="stat-card__head">
            <i data-lucide="{{ $icon }}"></i>
            <span class="stat-card__label">{{ $label }}</span>
        </div>
        <div class="stat-card__value">{{ $count }}</div>
    </a>
    @endforeach
</div>

{{-- Awaiting review — oldest first --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="siren"></i> {{ __('security_ops.index_panel_unreviewed') }}</h3>
        <a href="{{ route('portals.admin.security.emergency_access', ['review' => 'unreviewed']) }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($unreviewed->isEmpty())
            <div class="td-muted empty-cell">{{ __('security_ops.index_no_emergency') }}</div>
        @else
        <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('security_ops.emerg_col_patient') }}</th>
                <th>{{ __('security_ops.emerg_col_provider') }}</th>
                <th>{{ __('security_ops.emerg_col_reason') }}</th>
                <th>{{ __('security_ops.emerg_col_grant') }}</th>
                <th>{{ __('security_ops.index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($unreviewed as $ev)
                @php
                    $live = $ev->expires_at && $ev->expires_at->isFuture() && ! $ev->revoked_at;
                @endphp
                <tr>
                    <td data-label="{{ __('security_ops.emerg_col_patient') }}" class="td-strong">
                        {{ $ev->patient?->health_id ?? ($ev->patient_id ? substr($ev->patient_id, 0, 8).'…' : '—') }}
                    </td>
                    <td data-label="{{ __('security_ops.emerg_col_provider') }}" class="td-muted">
                        {{ $ev->provider_id ? substr($ev->provider_id, 0, 8).'…' : '—' }}
                    </td>
                    <td data-label="{{ __('security_ops.emerg_col_reason') }}">{{ Str::limit($ev->reason ?? '—', 60) }}</td>
                    <td data-label="{{ __('security_ops.emerg_col_grant') }}">
                        @if($ev->revoked_at)
                            <span class="badge badge-neutral badge-sm">{{ __('security_ops.emerg_grant_revoked') }}</span>
                        @elseif($live)
                            <span class="badge badge-danger badge-sm">{{ __('security_ops.emerg_grant_live') }}</span>
                        @elseif($ev->expires_at)
                            <span class="badge badge-neutral badge-sm">{{ __('security_ops.emerg_grant_expired') }}</span>
                        @else
                            <span class="td-muted">{{ __('security_ops.emerg_grant_none') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="td-muted">
                        {{ $ev->created_at?->diffForHumans() }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

<div class="grid-2">

{{-- Recent incidents --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-warning"></i> {{ __('security_ops.index_panel_incidents') }}</h3>
        <a href="{{ route('portals.admin.security.incidents') }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($recentIncidents->isEmpty())
            <div class="td-muted empty-cell">{{ __('security_ops.index_no_incidents') }}</div>
        @else
        <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('security_ops.inc_col_type') }}</th>
                <th>{{ __('security_ops.inc_col_severity') }}</th>
                <th>{{ __('security_ops.inc_col_status') }}</th>
                <th>{{ __('security_ops.index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentIncidents as $inc)
                @php $sevBadge = match($inc->severity) { 'critical' => 'badge-danger', 'high' => 'badge-warning', 'medium' => 'badge-primary', default => 'badge-neutral' }; @endphp
                <tr>
                    <td data-label="{{ __('security_ops.inc_col_type') }}">{{ $inc->incident_type }}</td>
                    <td data-label="{{ __('security_ops.inc_col_severity') }}"><span class="badge {{ $sevBadge }} badge-sm">@enum($inc->severity, 'severity')</span></td>
                    <td data-label="{{ __('security_ops.inc_col_status') }}"><span class="badge badge-neutral badge-sm">@enum($inc->status)</span></td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="td-muted">{{ $inc->detected_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

{{-- Recent audit events --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="scroll-text"></i> {{ __('security_ops.index_panel_audit') }}</h3>
        <a href="{{ route('portals.admin.security.audit_explorer') }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($recentAudit->isEmpty())
            <div class="td-muted empty-cell">{{ __('security_ops.index_no_audit') }}</div>
        @else
        <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('security_ops.audit_col_action') }}</th>
                <th>{{ __('security_ops.audit_col_resource') }}</th>
                <th>{{ __('security_ops.audit_col_authority') }}</th>
                <th>{{ __('security_ops.index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentAudit as $ev)
                <tr>
                    <td data-label="{{ __('security_ops.audit_col_action') }}">{{ $ev->action_type }}</td>
                    <td data-label="{{ __('security_ops.audit_col_resource') }}" class="td-muted">{{ $ev->resource_type ?? '—' }}</td>
                    <td data-label="{{ __('security_ops.audit_col_authority') }}">
                        @if($ev->emergency_override)
                            <span class="badge badge-danger badge-sm">{{ __('security_ops.audit_badge_emergency') }}</span>
                        @else
                            <span class="badge badge-neutral badge-sm">{{ __('security_ops.audit_badge_consented') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="td-muted">{{ $ev->created_at?->diffForHumans() }}</td>
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
