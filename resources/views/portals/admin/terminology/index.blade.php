@extends('layouts.portal')
@section('title', __('terminology.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.index_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gbStatusPill = fn ($s) => match ($s) {
        'pending' => 'pending', 'approved' => 'ready', 'rejected' => 'urgent', default => 'neutral',
    };
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('terminology.index_title') }}</h1>
        <p class="od-page__sub">{{ __('terminology.index_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.terminology.log') }}" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="scroll-text"></i> {{ __('terminology.nav_log') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- The standing state of the queue. "0 approved" is a fact worth showing
     plainly: a mapping table nobody has ever signed off on is not the same
     thing as an empty one. --}}
<div class="od-kpis">
    @php $kpis = [
        [__('terminology.kpi_pending'),    $counts['pending']    ?? 0, 'inbox',       'pending',    'amber'],
        [__('terminology.kpi_approved'),   $counts['approved']   ?? 0, 'check-check', 'approved',   'green'],
        [__('terminology.kpi_rejected'),   $counts['rejected']   ?? 0, 'x-circle',    'rejected',   'rose'],
        [__('terminology.kpi_deprecated'), $counts['deprecated'] ?? 0, 'archive',     'deprecated', 'sky'],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $filter, $tint])
    <a href="{{ route('portals.admin.terminology.index', ['status' => $filter]) }}" class="od-kpi od-tint--{{ $tint }}">
        <span class="od-kpi__icon"><i data-lucide="{{ $icon }}"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $label }}</span>
            <span class="od-kpi__value">{{ $count }}</span>
        </span>
    </a>
    @endforeach
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('terminology.index_title') }}</h2>
        <span class="od-card__count">{{ method_exists($mappings, 'total') ? $mappings->total() : $mappings->count() }}</span>
    </div>
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.terminology.index') }}" class="oda-toolbar">
            <select name="status" class="od-select" aria-label="{{ __('terminology.filter_status') }}">
                <option value="">{{ __('terminology.filter_all_statuses') }}</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ __('terminology.status_' . $s) }}</option>
                @endforeach
            </select>
            <select name="system" class="od-select" aria-label="{{ __('terminology.filter_system') }}">
                <option value="">{{ __('terminology.filter_all_systems') }}</option>
                {{-- Code system names (LOINC, ICD-10) are terminology identifiers, not
                     UI copy. They are printed, never translated. --}}
                @foreach($systems as $sys)
                    <option value="{{ $sys }}" {{ $system === $sys ? 'selected' : '' }}>{{ $sys }}</option>
                @endforeach
            </select>
            <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('terminology.btn_filter') }}</button>
            <a href="{{ route('portals.admin.terminology.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('terminology.btn_clear') }}</a>
        </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($mappings->isEmpty())
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="book-open-check"></i></div>
                <h3 class="od-empty__title">{{ __('terminology.empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('terminology.empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('terminology.col_local_term') }}</th>
                    <th>{{ __('terminology.col_resource_type') }}</th>
                    <th>{{ __('terminology.col_standard') }}</th>
                    <th>{{ __('terminology.col_confidence') }}</th>
                    <th>{{ __('terminology.col_radius') }}</th>
                    <th>{{ __('terminology.col_status') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($mappings as $m)
                    @php
                        $r = $radius[$m->id] ?? ['total' => 0, 'write' => 0, 'conflict' => 0, 'unit_mismatch' => 0, 'already' => 0];
                        $needsJudgement = $m->mapping_confidence === 'review' || filled($m->notes);
                    @endphp
                    <tr class="{{ $needsJudgement && $m->isPending() ? 'oda-gb-attention' : '' }}">
                        <td data-label="{{ __('terminology.col_local_term') }}">
                            {{-- Analyte and test names are clinical vocabulary. Printed as stored. --}}
                            <div class="od-table__name">{{ $m->local_name ?: $m->local_code }}</div>
                            <div class="od-table__sub">
                                <code class="oda-mono">{{ $m->local_code }}</code>
                                @if($m->local_unit)
                                    {{-- The unit is part of the mapping, not decoration: the same
                                         analyte in a different unit is a different code. --}}
                                    <span class="od-pill od-pill--normal">{{ $m->local_unit }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="{{ __('terminology.col_resource_type') }}">
                            {{ __('terminology.resource_' . $m->resource_type) }}
                        </td>
                        <td data-label="{{ __('terminology.col_standard') }}">
                            <code class="oda-mono">{{ $m->standard_code }}</code>
                            <span class="od-pill od-pill--neutral">{{ $m->standard_system }}</span>
                            {{-- LOINC display names are the official terminology strings. --}}
                            <div class="od-table__sub">{{ $m->standard_display }}</div>
                        </td>
                        <td data-label="{{ __('terminology.col_confidence') }}">
                            @if($m->mapping_confidence === 'review')
                                <span class="od-pill od-pill--pending">
                                    <i data-lucide="alert-triangle"></i> {{ __('terminology.confidence_review') }}
                                </span>
                            @else
                                <span class="od-pill od-pill--ready">{{ __('terminology.confidence_high') }}</span>
                            @endif
                            @if(filled($m->notes))
                                <div class="od-table__sub">{{ $m->notes }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('terminology.col_radius') }}">
                            <strong>{{ $r['total'] }}</strong>
                            <div class="od-table__sub">
                                {{ __('terminology.radius_write', ['n' => $r['write']]) }}
                                @if($r['conflict'] > 0)
                                    · <span class="oda-gb-danger">{{ __('terminology.radius_conflict', ['n' => $r['conflict']]) }}</span>
                                @endif
                                @if($r['unit_mismatch'] > 0)
                                    · {{ __('terminology.radius_unit_mismatch', ['n' => $r['unit_mismatch']]) }}
                                @endif
                            </div>
                        </td>
                        <td data-label="{{ __('terminology.col_status') }}">
                            <span class="od-pill od-pill--{{ $gbStatusPill($m->status) }}">{{ __('terminology.status_' . $m->status) }}</span>
                        </td>
                        <td class="od-table__actions">
                            <a href="{{ route('portals.admin.terminology.show', $m->id) }}" class="od-btn od-btn--row">
                                {{ __('terminology.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<div class="oda-gb-pager">{{ $mappings->links() }}</div>
</div>
@endsection
