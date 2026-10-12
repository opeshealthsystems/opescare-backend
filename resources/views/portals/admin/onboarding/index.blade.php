{{-- Imported from the platform (portals/admin/onboarding/index). Finder: /admin/onboarding,
     paginated, keys moved to lang/{en,fr}/onboarding.php (admin_portal.*), automatic readiness score column. --}}
@extends('layouts.portal')
@section('title', __('onboarding.admin_portal.idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('onboarding.admin_portal.idx_title'))

@section('content')

<div class="page-head">
    <h2>{{ __('onboarding.admin_portal.idx_title') }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('onboarding.admin_portal.idx_desc') }}</p>

{{-- KPI Strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="building-2"></i><span class="stat-card__label">{{ __('onboarding.admin_portal.stat_facilities') }}</span></div>
        <div class="stat-card__value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i><span class="stat-card__label">{{ __('onboarding.admin_portal.stat_approved') }}</span></div>
        <div class="stat-card__value">{{ $stats['approved'] }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="clock"></i><span class="stat-card__label">{{ __('onboarding.admin_portal.stat_ready') }}</span></div>
        <div class="stat-card__value">{{ $stats['ready_for_approval'] }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="alert-circle"></i><span class="stat-card__label">{{ __('onboarding.admin_portal.stat_in_progress') }}</span></div>
        <div class="stat-card__value">{{ $stats['pending'] }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="rocket"></i> {{ __('onboarding.admin_portal.panel_title') }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('onboarding.admin_portal.col_facility') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_type') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_progress') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_score') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_status') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_approved') }}</th>
                    <th class="row-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($facilities as $facility)
                    @php
                        $r = $readinessMap[$facility->id] ?? null;
                        $checklist = $r ? app(\App\Modules\FacilityReadiness\Services\FacilityGoLiveService::class)->checklistOf($r) : [];
                        $done = collect($checklist)->filter(fn($v) => $v === true)->count();
                        $total = count(\App\Modules\FacilityReadiness\Services\FacilityGoLiveService::CHECKLIST);
                        $pct = $total > 0 ? round($done / $total * 100) : 0;
                        $status = $r->status ?? 'pending';
                        $statusColor = match($status) {
                            'approved' => 'success',
                            'ready_for_approval' => 'info',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('onboarding.admin_portal.col_facility') }}" class="td-strong">{{ $facility->name }}</td>
                        <td data-label="{{ __('onboarding.admin_portal.col_type') }}">{{ $facility->type ?? '—' }}</td>
                        <td data-label="{{ __('onboarding.admin_portal.col_progress') }}">
                            <div class="breakdown__row breakdown__row--2col">
                                <div class="breakdown__track">
                                    <div class="breakdown__fill {{ $pct == 100 ? 'breakdown__fill--teal' : '' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="td-muted">{{ $done }}/{{ $total }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('onboarding.admin_portal.col_score') }}"><span data-score="{{ $facility->id }}">{{ $scores[$facility->id] ?? 0 }}%</span></td>
                        <td data-label="{{ __('onboarding.admin_portal.col_status') }}">
                            <span class="badge badge--{{ $statusColor }} badge-sm">{{ __('onboarding.admin_portal.status_'.$status) }}</span>
                        </td>
                        <td data-label="{{ __('onboarding.admin_portal.col_approved') }}" class="td-muted">{{ $r?->approved_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="row-actions">
                            <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="gauge"></i> {{ __('onboarding.admin_portal.btn_readiness') }}</a>
                            <a href="{{ route('portals.admin.onboarding.show', $facility->id) }}" class="btn btn-secondary btn-sm">{{ __('onboarding.admin_portal.btn_manage') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">{{ __('onboarding.admin_portal.idx_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-4">{{ $facilities->links() }}</div>

@endsection
