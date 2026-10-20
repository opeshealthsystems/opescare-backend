{{-- Imported from the platform (portals/admin/onboarding/index). Finder: /admin/onboarding,
     paginated, keys moved to lang/{en,fr}/onboarding.php (admin_portal.*), automatic readiness score column. --}}
@extends('layouts.portal')
@section('title', __('onboarding.admin_portal.idx_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('onboarding.admin_portal.idx_title') }}</h1>
        <p class="od-page__sub">{{ __('onboarding.admin_portal.idx_desc') }}</p>
    </div>
</div>

<div class="od-kpis">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--blue"><i data-lucide="building-2"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('onboarding.admin_portal.stat_facilities') }}</span><span class="od-kpi__value">{{ $stats['total'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--green"><i data-lucide="check-circle-2"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('onboarding.admin_portal.stat_approved') }}</span><span class="od-kpi__value">{{ $stats['approved'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--sky"><i data-lucide="clock"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('onboarding.admin_portal.stat_ready') }}</span><span class="od-kpi__value">{{ $stats['ready_for_approval'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="alert-circle"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('onboarding.admin_portal.stat_in_progress') }}</span><span class="od-kpi__value">{{ $stats['pending'] }}</span></div>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="rocket"></i> {{ __('onboarding.admin_portal.panel_title') }} <span class="od-card__count">({{ $facilities->total() }})</span></h2>
    </div>
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('onboarding.admin_portal.col_facility') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_type') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_progress') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_score') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_status') }}</th>
                    <th>{{ __('onboarding.admin_portal.col_approved') }}</th>
                    <th class="od-table__actions"></th>
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
                            'approved' => 'ready',
                            'ready_for_approval' => 'normal',
                            default => 'pending',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('onboarding.admin_portal.col_facility') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $gcTone($facility->name) }}" aria-hidden="true"><i data-lucide="building-2"></i></span>
                                <a href="{{ route('portals.admin.onboarding.show', $facility->id) }}" class="od-table__name">{{ $facility->name }}</a>
                            </div>
                        </td>
                        <td data-label="{{ __('onboarding.admin_portal.col_type') }}">{{ $facility->type ? \App\Support\FacilityTypeLabel::label($facility->type) : '—' }}</td>
                        <td data-label="{{ __('onboarding.admin_portal.col_progress') }}">
                            <div class="oda-gc-progress-row">
                                <progress class="oda-gc-progress{{ $pct == 100 ? ' is-done' : '' }}" value="{{ $pct }}" max="100">{{ $pct }}%</progress>
                                <span class="oda-gc-muted">{{ $done }}/{{ $total }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('onboarding.admin_portal.col_score') }}"><span data-score="{{ $facility->id }}">{{ $scores[$facility->id] ?? 0 }}%</span></td>
                        <td data-label="{{ __('onboarding.admin_portal.col_status') }}">
                            <span class="od-pill od-pill--dot od-pill--{{ $statusColor }}">{{ __('onboarding.admin_portal.status_'.$status) }}</span>
                        </td>
                        <td data-label="{{ __('onboarding.admin_portal.col_approved') }}" class="oda-gc-muted">{{ $r?->approved_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="od-table__actions">
                            <div class="oda-gc-actions">
                            <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="gauge"></i> {{ __('onboarding.admin_portal.btn_readiness') }}</a>
                            <a href="{{ route('portals.admin.onboarding.show', $facility->id) }}" class="od-btn od-btn--row">{{ __('onboarding.admin_portal.btn_manage') }}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="od-empty">
                            <span class="od-empty__icon"><i data-lucide="rocket"></i></span>
                            <p class="od-empty__text">{{ __('onboarding.admin_portal.idx_empty') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="od-card__foot">{{ $facilities->links() }}</div>
</section>

</div>
@endsection
