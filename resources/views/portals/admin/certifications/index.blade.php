@extends('layouts.portal')
@section('title', __('public.adm_cert_idx_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    // The models still speak the legacy badge-* vocabulary; translate by meaning.
    $gcPill = fn ($cls) => match (true) {
        str_contains((string) $cls, 'success') => 'ready',
        str_contains((string) $cls, 'danger')  => 'urgent',
        str_contains((string) $cls, 'warning') => 'pending',
        str_contains((string) $cls, 'teal')    => 'teal',
        str_contains((string) $cls, 'info'), str_contains((string) $cls, 'primary') => 'normal',
        default => 'neutral',
    };
@endphp

@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_cert_idx_breadcrumb_dir') }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cert_idx_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cert_idx_subtitle') }}</p>
    </div>
    <div class="od-row">
        <form method="POST" action="{{ route('portals.admin.certifications.seed') }}" class="oda-inline-form">
            @csrf
            <button type="submit" class="od-btn od-btn--secondary">
                <i data-lucide="list-checks"></i> {{ __('public.adm_cert_idx_btn_seed') }}
            </button>
        </form>
        <a href="{{ route('portals.admin.certifications.create') }}" class="od-btn od-btn--primary">
            <i data-lucide="plus"></i> {{ __('public.adm_cert_idx_btn_new') }}
        </a>
    </div>
</div>

<p class="oda-gc-note">{{ __('public.adm_cert_idx_desc') }}</p>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-kpis">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--blue"><i data-lucide="layers"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_cert_idx_stat_total') }}</span><span class="od-kpi__value">{{ $stats['total'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--green"><i data-lucide="check-circle-2"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_cert_idx_stat_certified') }}</span><span class="od-kpi__value">{{ $stats['passed'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="clock"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_cert_idx_stat_in_progress') }}</span><span class="od-kpi__value">{{ $stats['in_progress'] }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--teal"><i data-lucide="award"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_cert_idx_stat_badges') }}</span><span class="od-kpi__value">{{ $stats['badges'] }}</span></div>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cert_idx_breadcrumb_dir') }} <span class="od-card__count">({{ $certifications->total() }})</span></h2>
    </div>

    <form method="GET" class="oda-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('public.aria_status') }}" data-autosubmit>
            <option value="">{{ __('public.adm_cert_idx_filter_all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>@enum($s)</option>
            @endforeach
        </select>
        <select name="type" class="od-select" aria-label="{{ __('admin_extra.aria_type', [], app()->getLocale()) }}" data-autosubmit>
            <option value="">{{ __('public.adm_cert_idx_filter_all_types') }}</option>
            @foreach($types as $t)
            <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ strtoupper($t) }}</option>
            @endforeach
        </select>
        @if($status || $type)
        <a href="{{ route('portals.admin.certifications.index') }}" class="od-btn od-btn--ghost">{{ __('public.adm_cert_idx_filter_clear') }}</a>
        @endif
    </form>

    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_cert_idx_col_integration') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_type') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_vendor') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_status') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_level') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_last_run') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_badge') }}</th>
                    <th class="od-table__actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($certifications as $cert)
                <tr>
                    <td data-label="{{ __('public.adm_cert_idx_col_integration') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($cert->integration_name) }}" aria-hidden="true"><i data-lucide="shield-check"></i></span>
                            <div>
                                <a href="{{ route('portals.admin.certifications.show', $cert) }}" class="od-table__name">{{ $cert->integration_name }}</a>
                                @if($cert->version)
                                <span class="od-table__sub">v{{ $cert->version }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_type') }}">
                        <span class="od-pill od-pill--normal">{{ strtoupper($cert->integration_type) }}</span>
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_vendor') }}" class="oda-gc-muted">{{ $cert->vendor_name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_cert_idx_col_status') }}">
                        <span class="od-pill od-pill--dot od-pill--{{ $gcPill($cert->statusBadgeClass()) }}">@enum($cert->status)</span>
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_level') }}">
                        @if($cert->certification_level)
                        <span class="od-pill od-pill--{{ $gcPill($cert->levelBadgeClass()) }}">{{ ucfirst($cert->certification_level) }}</span>
                        @else
                        <span class="oda-gc-muted">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_last_run') }}">
                        @if($cert->latestTestRun)
                            <span class="od-pill {{ $cert->latestTestRun->isPassed() ? 'od-pill--ready' : 'od-pill--urgent' }}">{{ $cert->latestTestRun->passRate() }}%</span>
                            <span class="od-table__sub">({{ $cert->latestTestRun->started_at?->translatedFormat('d M Y') }})</span>
                        @else
                            <span class="oda-gc-muted">{{ __('public.adm_cert_idx_no_runs') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_badge') }}">
                        @if($cert->badge)
                            <span class="oda-mono">{{ $cert->badge->badge_code }}</span>
                        @else
                            <span class="oda-gc-muted">—</span>
                        @endif
                    </td>
                    <td class="od-table__actions">
                        <a href="{{ route('portals.admin.certifications.show', $cert) }}" class="od-btn od-btn--row">{{ __('public.adm_cert_idx_btn_manage') }}</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                        <p class="od-empty__text">{{ __('public.adm_cert_idx_empty') }} <a href="{{ route('portals.admin.certifications.create') }}">{{ __('public.adm_cert_idx_empty_link') }}</a></p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($certifications->hasPages())
    <div class="od-card__foot">{{ $certifications->links() }}</div>
    @endif
</section>

</div>
@endsection
