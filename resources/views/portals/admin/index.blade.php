@extends('layouts.portal')

@section('title', __('public.admin_governance.page_meta_title', [], app()->getLocale()))

@section('breadcrumb_home', __('public.admin_governance.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.admin_governance.page_title', [], app()->getLocale()) }}</h1>
        <p class="page-subtitle">{{ __('public.admin_governance.page_subtitle', [], app()->getLocale()) }}</p>
    </div>
    <div class="page-actions">
        {{-- Scrolls to the duplicates panel, which is platform tier — so the
             button follows it, rather than scrolling a facility admin to
             nothing. --}}
        @platformadmin
        <button class="btn btn-secondary" data-scroll-to="duplicates">
            <i data-lucide="users"></i>
            {{ __('public.admin_governance.btn_duplicate_review', [], app()->getLocale()) }}
        </button>
        @endplatformadmin
    </div>
</div>

{{-- Platform overview stats.
     Platform-wide totals, and every card links into a platform-tier page.
     A facility admin is 403'd on all six by RequirePlatformAdmin, so for
     them this whole block was six dead links AND a set of cross-tenant
     counts they have no business seeing. --}}
@platformadmin
{{-- Platform overview stats --}}
<div class="stat-grid mb-6" style="--cols:4">
  <a href="{{ route('portals.admin.facilities.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="hospital"></i></div>
    <div class="stat-card__value">{{ $platformStats['facilities'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_total_facilities', [], app()->getLocale()) }}</div>
  </a>
      </div>
<div class="stat-grid mb-6" style="--cols:3">
  <a href="{{ route('portals.admin.users.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="users"></i></div>
    <div class="stat-card__value">{{ $platformStats['total_users'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_total_users', [], app()->getLocale()) }}</div>
  </a>
  </div>
@endplatformadmin

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i data-lucide="id-card"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_total_health_ids', [], app()->getLocale()) }}</div>
            <div class="kpi-value">{{ number_format($stats['total_ids'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_registered_patients', [], app()->getLocale()) }}</div>
        </div>
    </div>
    {{-- null = a facility-tier viewer with no facility context: no honest
         number to show, so no tile (AdminPortalController@index). --}}
    @if(($stats['active_tokens'] ?? null) !== null)
    <div class="kpi-card" data-kpi="active_tokens">
        <div class="kpi-icon teal"><i data-lucide="qr-code"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_active_tokens', [], app()->getLocale()) }}</div>
            <div class="kpi-value" data-kpi-value="active_tokens">{{ number_format($stats['active_tokens']) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_live_tokens', [], app()->getLocale()) }}</div>
        </div>
    </div>
    @endif
    <div class="kpi-card">
        <div class="kpi-icon blue"><i data-lucide="activity"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_total_lookups', [], app()->getLocale()) }}</div>
            <div class="kpi-value">{{ number_format($stats['total_access_logs'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_alltime_events', [], app()->getLocale()) }}</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon danger"><i data-lucide="shield-x"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_denied_lookups', [], app()->getLocale()) }}</div>
            <div class="kpi-value">{{ number_format($stats['denied_access'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_failed_denied', [], app()->getLocale()) }}</div>
        </div>
    </div>
</div>


{{-- The Partner Governance panel that stood here loaded itself from
     `/api/partner-governance/partners`. That API left with the partner
     governance module on 2026-09-02 (see routes/partners.php, now an empty
     stub), so the panel showed its loading spinner and never anything else.
     The panel is gone rather than stubbed: this deployment has no partners to
     govern. --}}

{{-- Pending duplicate reviews.
     This panel used to fetch `/api/v1/connect/admin/merge-cases`, which went
     with the Connect admin portal and does not exist here either — another
     permanent spinner. The work itself is very much alive: the identity desk
     at /admin/mpi raises, merges (reversibly) and rejects candidates. So the
     dashboard reports that desk's own count and sends the administrator to it,
     instead of being a second, worse copy of it. --}}
@platformadmin
<div class="panel mb-6" id="duplicates">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="users"></i>
            {{ __('public.admin_governance.section_duplicate_reviews', [], app()->getLocale()) }}
        </h2>
    </div>
    <div class="panel-body">
        <p class="td-muted">
            @if(($duplicatesPending ?? 0) > 0)
                {{ trans_choice('public.admin_governance.duplicates_pending', $duplicatesPending, ['count' => number_format($duplicatesPending)]) }}
            @else
                {{ __('public.admin_governance.duplicates_none') }}
            @endif
        </p>
        <a href="{{ route('portals.admin.mpi.index') }}" class="btn btn-secondary">
            <i data-lucide="user-search"></i>
            {{ __('public.admin_governance.duplicates_open_desk') }}
        </a>
    </div>
</div>
@endplatformadmin

<!-- Recent Security Events -->
<div class="panel" id="security">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="activity"></i>
            {{ __('public.admin_governance.section_security_events', [], app()->getLocale()) }}
        </h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.admin_governance.section_security_events', [], app()->getLocale()) }}">
            <thead>
                <tr>
                    <th>{{ __('public.admin_governance.col_timestamp', [], app()->getLocale()) }}</th>
                    <th>{{ __('public.admin_governance.col_event_type', [], app()->getLocale()) }}</th>
                    <th>{{ __('public.admin_governance.col_target_health_id', [], app()->getLocale()) }}</th>
                    <th>{{ __('public.admin_governance.col_actor', [], app()->getLocale()) }}</th>
                    <th>{{ __('public.admin_governance.col_result', [], app()->getLocale()) }}</th>
                </tr>
            </thead>
            <tbody>
                @isset($recentLogs)
                @forelse($recentLogs as $log)
                <tr>
                    <td data-label="{{ __('public.admin_governance.col_timestamp', [], app()->getLocale()) }}">
                        <span class="td-muted">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y') }}</span>
                        <div class="td-muted code-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</div>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_event_type', [], app()->getLocale()) }}">
                        <span class="td-strong">{{ $log->access_type }}</span>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_target_health_id', [], app()->getLocale()) }}">
                        <span class="td-mono">{{ $log->health_id ?? __('public.admin_governance.lbl_unknown', [], app()->getLocale()) }}</span>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_actor', [], app()->getLocale()) }}">
                        <span class="td-muted">{{ $log->actor_type ?? '—' }}</span>
                        @if(!empty($log->ip_address))
                        <div class="td-muted code-muted">{{ $log->ip_address }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_result', [], app()->getLocale()) }}">
                        @if(($log->result ?? '') === 'success')
                            <span class="badge badge-allowed">{{ __('public.admin_governance.result_success', [], app()->getLocale()) }}</span>
                        @else
                            <span class="badge badge-denied">{{ __('public.admin_governance.result_denied', [], app()->getLocale()) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="td-muted empty-cell">
                        {{ __('public.admin_governance.no_security_events', [], app()->getLocale()) }}
                    </td>
                </tr>
                @endforelse
                @endisset
            </tbody>
        </table>
    </div>
</div>

{{-- The "Review Suspected Duplicate" modal was removed with the panel that
     opened it: it resolved a case by POSTing to
     `/api/v1/connect/admin/merge-cases/{id}/resolve`, which this fork does not
     serve. Merging is done at /admin/mpi, where it is reversible and audited. --}}

@endsection

