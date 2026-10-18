@extends('layouts.portal')
@section('title', __('security_ops.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.index_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.index_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.index_subtitle') }}</p>
    </div>
</div>

{{-- The one thing this desk exists for. Unreviewed overrides are shown before
     anything else, and loudly; a reviewed-clean state says so explicitly
     rather than showing nothing (silence and "all clear" must not look alike). --}}
@if($stats['unreviewed_breakglass'] > 0)
<div class="oda-ga-alert oda-ga-alert--danger">
    <i data-lucide="siren"></i>
    <div class="oda-ga-alert__body">
        <strong>{{ $stats['unreviewed_breakglass'] }} {{ __('security_ops.index_banner_unreviewed') }}</strong>
    </div>
    <a href="{{ route('portals.admin.security.emergency_access', ['review' => 'unreviewed']) }}" class="od-btn od-btn--danger od-btn--sm">
        {{ __('security_ops.index_btn_review_now') }}
    </a>
</div>
@else
<div class="oda-ga-alert oda-ga-alert--ok">
    <i data-lucide="shield-check"></i>
    <div class="oda-ga-alert__body">{{ __('security_ops.index_banner_none') }}</div>
</div>
@endif

{{-- KPI cards --}}
<div class="od-kpis oda-ga-kpis6">
    @php $kpis = [
        [__('security_ops.kpi_unreviewed'),         $stats['unreviewed_breakglass'], 'siren',        route('portals.admin.security.emergency_access', ['review' => 'unreviewed']), 'rose'],
        [__('security_ops.kpi_live_grants'),        $stats['live_grants'],           'unlock',       route('portals.admin.security.emergency_access', ['grant' => 'live']),        'amber'],
        [__('security_ops.kpi_breakglass_7d'),      $stats['breakglass_7d'],         'clock-alert',  route('portals.admin.security.emergency_access'),                            'blue'],
        [__('security_ops.kpi_open_incidents'),     $stats['open_incidents'],        'file-warning', route('portals.admin.security.incidents'),                                   'teal'],
        [__('security_ops.kpi_critical_incidents'), $stats['critical_incidents'],    'shield-alert', route('portals.admin.security.incidents', ['severity' => 'critical']),       'sky'],
        [__('security_ops.kpi_audit_today'),        $stats['audit_events_today'],    'search-code',  route('portals.admin.security.audit_explorer'),                              'green'],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $url, $tint])
    <a href="{{ $url }}" class="od-kpi od-tint--{{ $tint }}">
        <span class="od-kpi__icon"><i data-lucide="{{ $icon }}"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $label }}</span>
            <span class="od-kpi__value">{{ $count }}</span>
        </span>
    </a>
    @endforeach
</div>

{{-- Awaiting review — oldest first --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.index_panel_unreviewed') }} <span class="od-card__count">({{ $unreviewed->count() }})</span></h2>
        <a href="{{ route('portals.admin.security.emergency_access', ['review' => 'unreviewed']) }}" class="od-card__link">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($unreviewed->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <p class="od-empty__text">{{ __('security_ops.index_no_emergency') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
        <table class="od-table">
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
                    <td data-label="{{ __('security_ops.emerg_col_patient') }}">
                        <span class="od-table__name oda-mono">{{ $ev->patient?->health_id ?? ($ev->patient_id ? substr($ev->patient_id, 0, 8).'…' : '—') }}</span>
                    </td>
                    <td data-label="{{ __('security_ops.emerg_col_provider') }}" class="oda-ga-muted">
                        <span class="oda-mono">{{ $ev->provider_id ? substr($ev->provider_id, 0, 8).'…' : '—' }}</span>
                    </td>
                    <td data-label="{{ __('security_ops.emerg_col_reason') }}">{{ Str::limit($ev->reason ?? '—', 60) }}</td>
                    <td data-label="{{ __('security_ops.emerg_col_grant') }}">
                        @if($ev->revoked_at)
                            <span class="od-pill od-pill--neutral">{{ __('security_ops.emerg_grant_revoked') }}</span>
                        @elseif($live)
                            <span class="od-pill od-pill--urgent">{{ __('security_ops.emerg_grant_live') }}</span>
                        @elseif($ev->expires_at)
                            <span class="od-pill od-pill--neutral">{{ __('security_ops.emerg_grant_expired') }}</span>
                        @else
                            <span class="oda-ga-muted">{{ __('security_ops.emerg_grant_none') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="oda-ga-muted">
                        {{ $ev->created_at?->diffForHumans() }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</section>

<div class="od-grid od-grid--2">

{{-- Recent incidents --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.index_panel_incidents') }} <span class="od-card__count">({{ $recentIncidents->count() }})</span></h2>
        <a href="{{ route('portals.admin.security.incidents') }}" class="od-card__link">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($recentIncidents->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-warning"></i></span>
                <p class="od-empty__text">{{ __('security_ops.index_no_incidents') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr>
                <th>{{ __('security_ops.inc_col_type') }}</th>
                <th>{{ __('security_ops.inc_col_severity') }}</th>
                <th>{{ __('security_ops.inc_col_status') }}</th>
                <th>{{ __('security_ops.index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentIncidents as $inc)
                @php $sevBadge = match($inc->severity) { 'critical' => 'od-pill--urgent', 'high' => 'od-pill--pending', 'medium' => 'od-pill--normal', default => 'od-pill--neutral' }; @endphp
                <tr>
                    <td data-label="{{ __('security_ops.inc_col_type') }}"><a class="od-table__name" href="{{ route('portals.admin.security.incidents.show', $inc->id) }}">{{ $inc->incident_type }}</a></td>
                    <td data-label="{{ __('security_ops.inc_col_severity') }}"><span class="od-pill {{ $sevBadge }}">@enum($inc->severity, 'severity')</span></td>
                    <td data-label="{{ __('security_ops.inc_col_status') }}"><span class="od-pill od-pill--neutral">@enum($inc->status)</span></td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="oda-ga-muted">{{ $inc->detected_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</section>

{{-- Recent audit events --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.index_panel_audit') }} <span class="od-card__count">({{ $recentAudit->count() }})</span></h2>
        <a href="{{ route('portals.admin.security.audit_explorer') }}" class="od-card__link">{{ __('security_ops.btn_view_all') }}</a>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($recentAudit->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="scroll-text"></i></span>
                <p class="od-empty__text">{{ __('security_ops.index_no_audit') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr>
                <th>{{ __('security_ops.audit_col_action') }}</th>
                <th>{{ __('security_ops.audit_col_resource') }}</th>
                <th>{{ __('security_ops.audit_col_authority') }}</th>
                <th>{{ __('security_ops.index_col_when') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentAudit as $ev)
                <tr>
                    <td data-label="{{ __('security_ops.audit_col_action') }}"><span class="oda-mono">{{ $ev->action_type }}</span></td>
                    <td data-label="{{ __('security_ops.audit_col_resource') }}" class="oda-ga-muted">{{ $ev->resource_type ?? '—' }}</td>
                    <td data-label="{{ __('security_ops.audit_col_authority') }}">
                        @if($ev->emergency_override)
                            <span class="od-pill od-pill--urgent">{{ __('security_ops.audit_badge_emergency') }}</span>
                        @else
                            <span class="od-pill od-pill--neutral">{{ __('security_ops.audit_badge_consented') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('security_ops.index_col_when') }}" class="oda-ga-muted">{{ $ev->created_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</section>

</div>
</div>
@endsection
