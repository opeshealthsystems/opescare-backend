@extends('layouts.portal')
@section('title', __('public.adm_cert_idx_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_cert_idx_breadcrumb_dir') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_cert_idx_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <form method="POST" action="{{ route('portals.admin.certifications.seed') }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="list-checks"></i> {{ __('public.adm_cert_idx_btn_seed') }}
        </button>
    </form>
    <a href="{{ route('portals.admin.certifications.create') }}" class="btn btn-primary btn-sm">
        <i data-lucide="plus"></i> {{ __('public.adm_cert_idx_btn_new') }}
    </a>
</div>

<p class="td-muted mb-6">{{ __('public.adm_cert_idx_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="layers"></i><span class="stat-card__label">{{ __('public.adm_cert_idx_stat_total') }}</span></div>
        <div class="stat-card__value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle-2"></i><span class="stat-card__label">{{ __('public.adm_cert_idx_stat_certified') }}</span></div>
        <div class="stat-card__value">{{ $stats['passed'] }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i><span class="stat-card__label">{{ __('public.adm_cert_idx_stat_in_progress') }}</span></div>
        <div class="stat-card__value">{{ $stats['in_progress'] }}</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="award"></i><span class="stat-card__label">{{ __('public.adm_cert_idx_stat_badges') }}</span></div>
        <div class="stat-card__value">{{ $stats['badges'] }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_cert_idx_filter_all_statuses') }}</option>
        @foreach($statuses as $s)
        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>@enum($s)</option>
        @endforeach
    </select>
    <select name="type" class="filter-select" aria-label="{{ __('admin_extra.aria_type', [], app()->getLocale()) ?: 'Type' }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_cert_idx_filter_all_types') }}</option>
        @foreach($types as $t)
        <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ strtoupper($t) }}</option>
        @endforeach
    </select>
    @if($status || $type)
    <a href="{{ route('portals.admin.certifications.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_cert_idx_filter_clear') }}</a>
    @endif
</form>

{{-- Table --}}
<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_cert_idx_col_integration') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_type') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_vendor') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_status') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_level') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_last_run') }}</th>
                    <th>{{ __('public.adm_cert_idx_col_badge') }}</th>
                    <th class="row-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($certifications as $cert)
                <tr>
                    <td data-label="{{ __('public.adm_cert_idx_col_integration') }}" class="td-strong">
                        {{ $cert->integration_name }}
                        @if($cert->version)
                        <span class="td-muted">v{{ $cert->version }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_type') }}">
                        <span class="badge badge--info badge-sm">{{ strtoupper($cert->integration_type) }}</span>
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_vendor') }}" class="td-muted">{{ $cert->vendor_name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_cert_idx_col_status') }}">
                        <span class="badge {{ $cert->statusBadgeClass() }} badge-sm">
                            @enum($cert->status)
                        </span>
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_level') }}">
                        @if($cert->certification_level)
                        <span class="badge {{ $cert->levelBadgeClass() }} badge-sm">{{ ucfirst($cert->certification_level) }}</span>
                        @else
                        <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_last_run') }}">
                        @if($cert->latestTestRun)
                            <span class="badge {{ $cert->latestTestRun->isPassed() ? 'badge-success' : 'badge-danger' }} badge-sm">
                                {{ $cert->latestTestRun->passRate() }}%
                            </span>
                            <span class="td-muted code-muted">({{ $cert->latestTestRun->started_at?->format('d M Y') }})</span>
                        @else
                            <span class="td-muted">{{ __('public.adm_cert_idx_no_runs') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_cert_idx_col_badge') }}">
                        @if($cert->badge)
                            <span class="mono code-token">{{ $cert->badge->badge_code }}</span>
                        @else
                            <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('portals.admin.certifications.show', $cert) }}" class="btn btn-secondary btn-sm">{{ __('public.adm_cert_idx_btn_manage') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="td-muted empty-cell">
                        {{ __('public.adm_cert_idx_empty') }} <a href="{{ route('portals.admin.certifications.create') }}">{{ __('public.adm_cert_idx_empty_link') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($certifications->hasPages())
        <div class="panel-footer">{{ $certifications->links() }}</div>
        @endif
    </div>
</div>

@endsection
