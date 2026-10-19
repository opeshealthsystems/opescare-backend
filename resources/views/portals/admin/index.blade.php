@extends('layouts.portal')

{{-- Facility tier sees its own facility's admin home, never OpesCare's
     governance back office; platform admins keep the governance title. --}}
@php $facilityTier = isset($isPlatform) && ! $isPlatform; @endphp
@section('title', $facilityTier
    ? ($facilityName ? __('facility_admin_home.page_meta_title', ['facility' => $facilityName]) : __('facility_admin_home.page_title_plain'))
    : __('public.admin_governance.page_meta_title', [], app()->getLocale()))

@section('breadcrumb_home', $facilityTier
    ? __('facility_admin_home.breadcrumb_home')
    : __('public.admin_governance.breadcrumb_home', [], app()->getLocale()))

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">
@endsection

@php
    $l = app()->getLocale();
    $me = auth()->user();
    $myName = trim((string) ($me->name ?? ''));
    // Each tile links to the desk its number comes from. Those desks are
    // platform tier (RequirePlatformAdmin), so for a facility-tier viewer the
    // tiles point at the events table further down this page instead of at a
    // 403 — SidebarLinksAreReachableTest sweeps every link on this page.
    $kpiTier = \App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($me);
    $kpiHref = fn (string $route) => $kpiTier ? route($route) : '#security';
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

@section('content')

{{-- Welcome --}}
<section class="od-greeting oda-welcome">
    <div>
        <div class="od-greeting__hello">{{ __('admin_desk.home_hello', [], $l) }}</div>
        @if($facilityTier)
        <h1 class="od-greeting__name" data-testid="facility-admin-title">{{ $facilityName ? __('facility_admin_home.page_title', ['facility' => $facilityName]) : __('facility_admin_home.page_title_plain') }}</h1>
        <div class="od-greeting__meta">{{ __('facility_admin_home.page_subtitle') }} · {{ now()->translatedFormat('l j F Y') }}</div>
        @else
        <h1 class="od-greeting__name">{{ $myName !== '' ? $myName : __('public.admin_governance.page_title', [], $l) }}</h1>
        <div class="od-greeting__meta">{{ __('public.admin_governance.page_subtitle', [], $l) }} · {{ now()->translatedFormat('l j F Y') }}</div>
        @endif
    </div>
    <div class="od-row">
        {{-- Scrolls to the duplicates panel, which is platform tier — so the
             button follows it, rather than scrolling a facility admin to nothing. --}}
        @platformadmin
        <button type="button" class="od-btn od-btn--secondary" data-scroll-to="duplicates">
            <i data-lucide="users"></i>
            {{ __('public.admin_governance.btn_duplicate_review', [], $l) }}
        </button>
        @endplatformadmin
    </div>
</section>

@if($facilityTier && is_array($checklist ?? null) && in_array(false, $checklist, true))
@php
    $steps = [
        'team'    => ['icon' => 'user-plus',   'url' => route('portals.facility.team')],
        'profile' => ['icon' => 'building-2',  'url' => \App\Support\StaffCapabilities::can($me, 'service_catalogue') ? route('portals.facility.settings.index') : null],
        'verify'  => ['icon' => 'scan-line',   'url' => route('verify.health-id')],
    ];
    $doneCount = count(array_filter($checklist));
@endphp
<section class="od-card" id="get-started" data-testid="get-started" aria-labelledby="oda-start-title">
    <div class="od-card__head">
        <h2 class="od-card__title" id="oda-start-title">{{ __('facility_admin_home.checklist_title') }}
            <span class="od-card__count">({{ __('facility_admin_home.checklist_progress', ['done' => $doneCount, 'total' => count($checklist)]) }})</span></h2>
    </div>
    <p class="oda-start__intro">{{ __('facility_admin_home.checklist_intro') }}</p>
    <ul class="oda-start__list">
        @foreach($steps as $key => $step)
        @php $done = (bool) ($checklist[$key] ?? false); @endphp
        <li class="oda-start__item {{ $done ? 'is-done' : '' }}" data-step="{{ $key }}" data-done="{{ $done ? '1' : '0' }}">
            <i data-lucide="{{ $done ? 'check-circle-2' : $step['icon'] }}" aria-hidden="true"></i>
            <div class="oda-start__text">
                <strong>{{ __('facility_admin_home.step_' . $key . '_title') }}</strong>
                <span class="oda-start__status">{{ $done ? __('facility_admin_home.step_done') : __('facility_admin_home.step_todo') }}</span>
                <div>{{ __('facility_admin_home.step_' . $key . '_body') }}</div>
            </div>
            @if(! $done && $step['url'])
            <a href="{{ $step['url'] }}" class="od-btn od-btn--secondary">{{ __('facility_admin_home.step_' . $key . '_action') }}</a>
            @endif
        </li>
        @endforeach
    </ul>
</section>
<style @cspNonce>
    .oda-start__intro { margin: 0 0 12px; color: #334155; }
    .oda-start__list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .oda-start__item { display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; }
    .oda-start__item > i { color: #0F4C81; flex: 0 0 auto; margin-top: 2px; }
    .oda-start__item.is-done > i { color: #15803d; }
    .oda-start__text { flex: 1 1 auto; min-width: 0; }
    .oda-start__status { margin-left: 8px; font-size: 12px; color: #64748b; }
    .oda-start__item .od-btn { flex: 0 0 auto; }
    @media (max-width: 600px) { .oda-start__item { flex-wrap: wrap; } }
</style>
@endif

{{-- KPI row. Platform tier: the platform's four headline figures. Every number
     comes from AdminPortalController@index; nothing here is computed. --}}
<div class="od-kpis">
    @platformadmin
    <a href="{{ route('portals.admin.facilities.index') }}" class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="hospital"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.admin_governance.stat_total_facilities', [], $l) }}</span>
            <span class="od-kpi__value">{{ isset($platformStats['facilities']) ? number_format($platformStats['facilities']) : '—' }}</span>
            <span class="od-kpi__sub">{{ __('admin_desk.home_kpi_facilities_sub', [], $l) }}</span>
        </span>
    </a>
    <a href="{{ route('portals.admin.users.index') }}" class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="users"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.admin_governance.stat_total_users', [], $l) }}</span>
            <span class="od-kpi__value">{{ isset($platformStats['total_users']) ? number_format($platformStats['total_users']) : '—' }}</span>
            <span class="od-kpi__sub">{{ __('admin_desk.home_kpi_users_sub', [], $l) }}</span>
        </span>
    </a>
    @endplatformadmin
    <a href="{{ $kpiHref('portals.admin.mpi.index') }}" class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="id-card"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $kpiTier ? __('public.admin_governance.kpi_total_health_ids', [], $l) : __('facility_admin_home.kpi_ids', [], $l) }}</span>
            <span class="od-kpi__value">{{ number_format($stats['total_ids'] ?? 0) }}</span>
            <span class="od-kpi__sub">{{ $kpiTier ? __('public.admin_governance.kpi_registered_patients', [], $l) : __('facility_admin_home.kpi_ids_sub', [], $l) }}</span>
        </span>
    </a>
    <a href="#security" class="od-kpi od-tint--red">
        <span class="od-kpi__icon"><i data-lucide="shield-x"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.admin_governance.kpi_denied_lookups', [], $l) }}</span>
            <span class="od-kpi__value">{{ number_format($stats['denied_access'] ?? 0) }}</span>
            <span class="od-kpi__sub @if(($stats['denied_access'] ?? 0) > 0) od-kpi__sub--alert @endif">{{ __('public.admin_governance.kpi_failed_denied', [], $l) }}</span>
        </span>
    </a>
    {{-- Facility tier has no platform figures: its row is completed by the
         token and lookup tiles, which below otherwise sit in the activity card. --}}
    @unless($kpiTier)
        @if(($stats['active_tokens'] ?? null) !== null)
        <a href="#security" class="od-kpi od-tint--sky" data-kpi="active_tokens">
            <span class="od-kpi__icon"><i data-lucide="qr-code"></i></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ $kpiTier ? __('public.admin_governance.kpi_active_tokens', [], $l) : __('facility_admin_home.kpi_cards', [], $l) }}</span>
                <span class="od-kpi__value" data-kpi-value="active_tokens">{{ number_format($stats['active_tokens']) }}</span>
                <span class="od-kpi__sub">{{ $kpiTier ? __('public.admin_governance.kpi_live_tokens', [], $l) : __('facility_admin_home.kpi_cards_sub', [], $l) }}</span>
            </span>
        </a>
        @endif
        <a href="#security" class="od-kpi od-tint--blue">
            <span class="od-kpi__icon"><i data-lucide="activity"></i></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ $kpiTier ? __('public.admin_governance.kpi_total_lookups', [], $l) : __('facility_admin_home.kpi_lookups', [], $l) }}</span>
                <span class="od-kpi__value">{{ number_format($stats['total_access_logs'] ?? 0) }}</span>
                <span class="od-kpi__sub">{{ $kpiTier ? __('public.admin_governance.kpi_alltime_events', [], $l) : __('facility_admin_home.kpi_lookups_sub', [], $l) }}</span>
            </span>
        </a>
    @endunless
</div>

@platformadmin
{{-- Quick actions: platform-tier desks only (a facility admin would get 403s). --}}
<section class="od-card" aria-labelledby="oda-quick-title">
    <header class="od-card__head">
        <h2 class="od-card__title" id="oda-quick-title">{{ __('admin_desk.home_quick_title', [], $l) }}</h2>
    </header>
    <div class="od-card__body">
    <div class="od-quick">
        <a href="{{ route('portals.admin.facilities.index') }}" class="od-quick__tile"><i data-lucide="hospital" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_facilities', [], $l) }}</span></a>
        <a href="{{ route('portals.admin.users.index') }}" class="od-quick__tile"><i data-lucide="user-cog" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_users', [], $l) }}</span></a>
        <a href="{{ route('portals.admin.mpi.index') }}" class="od-quick__tile"><i data-lucide="user-search" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_mpi', [], $l) }}</span></a>
        <a href="{{ route('portals.admin.security.index') }}" class="od-quick__tile"><i data-lucide="shield-check" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_security', [], $l) }}</span></a>
        <a href="{{ route('portals.admin.verifications.index') }}" class="od-quick__tile"><i data-lucide="badge-check" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_verifications', [], $l) }}</span></a>
        <a href="{{ route('portals.admin.cc') }}" class="od-quick__tile"><i data-lucide="sliders-horizontal" class="od-quick__glyph"></i><span class="od-quick__label">{{ __('admin_desk.home_quick_control', [], $l) }}</span></a>
    </div>
    </div>
</section>
@endplatformadmin

<div class="od-grid od-grid--split">

    {{-- Recent security events (the activity feed) --}}
    <section class="od-card" id="security" aria-labelledby="oda-security-title">
        <header class="od-card__head">
            <h2 class="od-card__title" id="oda-security-title">{{ $kpiTier ? __('public.admin_governance.section_security_events', [], $l) : __('facility_admin_home.section_access', [], $l) }} <span class="od-card__count">({{ isset($recentLogs) ? $recentLogs->count() : 0 }})</span></h2>
            @platformadmin
            <a href="{{ route('portals.admin.security.audit_explorer') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
            @endplatformadmin
        </header>
        @if(!isset($recentLogs) || $recentLogs->isEmpty())
            <div class="od-card__body"><div class="od-empty">
                <span class="od-empty__icon od-tint--green"><i data-lucide="shield-check"></i></span>
                <div class="od-empty__title">{{ __('admin_desk.home_events_empty_title', [], $l) }}</div>
                <p class="od-empty__text">{{ $kpiTier ? __('public.admin_governance.no_security_events', [], $l) : __('facility_admin_home.no_access', [], $l) }}</p>
            </div></div>
        @else
        <div class="od-table-wrap">
            <table class="od-table" aria-label="{{ $kpiTier ? __('public.admin_governance.section_security_events', [], $l) : __('facility_admin_home.section_access', [], $l) }}">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('public.admin_governance.col_timestamp', [], $l) }}</th>
                        <th>{{ __('public.admin_governance.col_event_type', [], $l) }}</th>
                        <th>{{ __('public.admin_governance.col_target_health_id', [], $l) }}</th>
                        <th>{{ __('public.admin_governance.col_actor', [], $l) }}</th>
                        <th>{{ __('public.admin_governance.col_result', [], $l) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('public.admin_governance.col_timestamp', [], $l) }}">
                            <div class="od-table__name">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y') }}</div>
                            <div class="od-table__sub">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</div>
                        </td>
                        <td data-label="{{ __('public.admin_governance.col_event_type', [], $l) }}">
                            <span class="od-strong">{{ $log->access_type }}</span>
                        </td>
                        <td data-label="{{ __('public.admin_governance.col_target_health_id', [], $l) }}">
                            <span class="oda-mono">{{ $log->health_id ?? __('public.admin_governance.lbl_unknown', [], $l) }}</span>
                        </td>
                        <td data-label="{{ __('public.admin_governance.col_actor', [], $l) }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $tone((string) ($log->actor_type ?? '-')) }}">{{ mb_strtoupper(mb_substr((string) ($log->actor_type ?? '—'), 0, 1)) }}</span>
                                <div>
                                    <div class="od-table__name">{{ $log->actor_type ?? '—' }}</div>
                                    @if(!empty($log->ip_address))<div class="od-table__sub">{{ $log->ip_address }}</div>@endif
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.admin_governance.col_result', [], $l) }}">
                            @if(($log->result ?? '') === 'success')
                                <span class="od-pill od-pill--ready od-pill--dot">{{ __('public.admin_governance.result_success', [], $l) }}</span>
                            @else
                                <span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.admin_governance.result_denied', [], $l) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    <div class="od-stack">
        {{-- Pending duplicate reviews: the MPI desk's own count, and a link to it.
             Platform tier, like /admin/mpi itself. --}}
        @platformadmin
        <section class="od-card" id="duplicates" aria-labelledby="oda-dup-title">
            <header class="od-card__head">
                <h2 class="od-card__title" id="oda-dup-title">{{ __('public.admin_governance.section_duplicate_reviews', [], $l) }}</h2>
                <a href="{{ route('portals.admin.mpi.index') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
            </header>
            <div class="od-card__body">
            <div class="od-list">
                <div class="od-list__row">
                    <span class="od-list__icon {{ ($duplicatesPending ?? 0) > 0 ? 'od-tint--amber' : 'od-tint--green' }}"><i data-lucide="users"></i></span>
                    <div class="od-list__text">
                        <div class="od-list__title">
                            @if(($duplicatesPending ?? 0) > 0)
                                {{ trans_choice('public.admin_governance.duplicates_pending', $duplicatesPending, ['count' => number_format($duplicatesPending)]) }}
                            @else
                                {{ __('public.admin_governance.duplicates_none') }}
                            @endif
                        </div>
                    </div>
                    <span class="od-list__value">{{ number_format($duplicatesPending ?? 0) }}</span>
                </div>
            </div></div>
            <div class="od-card__foot">
                <a href="{{ route('portals.admin.mpi.index') }}" class="od-btn od-btn--secondary od-btn--block">
                    <i data-lucide="user-search"></i>
                    {{ __('public.admin_governance.duplicates_open_desk') }}
                </a>
            </div>
        </section>
        @endplatformadmin

        {{-- Security alerts: the access-control figures that need watching. --}}
        <section class="od-card" aria-labelledby="oda-alerts-title">
            <header class="od-card__head">
                <h2 class="od-card__title" id="oda-alerts-title">{{ __('admin_desk.home_alerts_title', [], $l) }}</h2>
                @platformadmin
                <a href="{{ route('portals.admin.security.index') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
                @endplatformadmin
            </header>
            <div class="od-card__body">
            <div class="od-list">
                <a href="#security" class="od-list__row">
                    <span class="od-list__icon {{ ($stats['denied_access'] ?? 0) > 0 ? 'od-tint--red' : 'od-tint--green' }}"><i data-lucide="shield-x"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ __('public.admin_governance.kpi_denied_lookups', [], $l) }}</span>
                        <span class="od-list__sub">{{ __('public.admin_governance.kpi_failed_denied', [], $l) }}</span>
                    </span>
                    <span class="od-list__value">{{ number_format($stats['denied_access'] ?? 0) }}</span>
                </a>
                <a href="{{ $kpiHref('portals.admin.security.audit_explorer') }}" class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="activity"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ $kpiTier ? __('public.admin_governance.kpi_total_lookups', [], $l) : __('facility_admin_home.kpi_lookups', [], $l) }}</span>
                        <span class="od-list__sub">{{ $kpiTier ? __('public.admin_governance.kpi_alltime_events', [], $l) : __('facility_admin_home.kpi_lookups_sub', [], $l) }}</span>
                    </span>
                    <span class="od-list__value">{{ number_format($stats['total_access_logs'] ?? 0) }}</span>
                </a>
                {{-- null = a facility-tier viewer with no facility context: no
                     honest number to show, so no row (AdminPortalController@index). --}}
                @if($kpiTier && ($stats['active_tokens'] ?? null) !== null)
                <a href="{{ route('portals.admin.security.index') }}" class="od-list__row" data-kpi="active_tokens">
                    <span class="od-list__icon od-tint--teal"><i data-lucide="qr-code"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ $kpiTier ? __('public.admin_governance.kpi_active_tokens', [], $l) : __('facility_admin_home.kpi_cards', [], $l) }}</span>
                        <span class="od-list__sub">{{ $kpiTier ? __('public.admin_governance.kpi_live_tokens', [], $l) : __('facility_admin_home.kpi_cards_sub', [], $l) }}</span>
                    </span>
                    <span class="od-list__value" data-kpi-value="active_tokens">{{ number_format($stats['active_tokens']) }}</span>
                </a>
                @endif
                @if(isset($platformStats['open_tickets']))
                <a href="{{ route('portals.admin.support.index') }}" class="od-list__row">
                    <span class="od-list__icon {{ $platformStats['open_tickets'] > 0 ? 'od-tint--amber' : 'od-tint--green' }}"><i data-lucide="life-buoy"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ __('public.admin_governance.stat_open_tickets', [], $l) }}</span>
                    </span>
                    <span class="od-list__value">{{ number_format($platformStats['open_tickets']) }}</span>
                </a>
                @endif
            </div></div>
        </section>
    </div>
</div>

@endsection
