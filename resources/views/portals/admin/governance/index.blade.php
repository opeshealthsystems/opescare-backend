@extends('layouts.portal')
@section('title', __('governance_desk.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('governance_desk.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('governance_desk.index_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdPill = fn ($s) => match ($s) { 'pending' => 'od-pill--pending', 'approved', 'completed', 'applied', 'fulfilled' => 'od-pill--ready', 'rejected', 'refused' => 'od-pill--urgent', default => 'od-pill--neutral' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('governance_desk.index_title') }}</h1>
        <p class="od-page__sub">{{ __('governance_desk.index_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.governance.log') }}" class="od-btn od-btn--secondary">
            <i data-lucide="scroll-text"></i> {{ __('governance_desk.nav_log') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- The age of the oldest untouched request. A statutory clock starts when the
     patient files, not when somebody notices, so the wait is stated plainly. --}}
@if(!is_null($oldestDays))
<div class="oda-gd-flash oda-gd-flash--warn">
    <i data-lucide="clock"></i>
    <div>{{ __('governance_desk.oldest_pending', ['days' => $oldestDays]) }}</div>
</div>
@endif

<div class="od-kpis">
    @php $kpis = [
        [__('governance_desk.kpi_export_pending'),     $exportCounts['pending']      ?? 0, 'inbox', 'amber'],
        [__('governance_desk.kpi_export_approved'),    $exportCounts['approved']     ?? 0, 'check-check', 'green'],
        [__('governance_desk.kpi_correction_pending'), $correctionCounts['pending']  ?? 0, 'file-pen-line', 'blue'],
        [__('governance_desk.kpi_correction_refused'), $correctionCounts['rejected'] ?? 0, 'x-circle', 'rose'],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $tint])
    <div class="od-kpi od-tint--{{ $tint }}">
        <span class="od-kpi__icon"><i data-lucide="{{ $icon }}"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $label }}</span>
            <span class="od-kpi__value">{{ $count }}</span>
        </span>
    </div>
    @endforeach
</div>

{{-- Status only. There is deliberately no patient name or Health-ID box: this
     desk lists requests, and must not double as a way to find out whether a
     given person is a patient here. --}}
<form method="GET" action="{{ route('portals.admin.governance.index') }}" class="oda-toolbar">
    <select name="status" class="od-select" aria-label="{{ __('governance_desk.filter_status') }}">
        <option value="">{{ __('governance_desk.filter_all') }}</option>
        @foreach($statuses as $s)
            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ __('governance_desk.status_' . $s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('governance_desk.btn_filter') }}</button>
    <a href="{{ route('portals.admin.governance.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('governance_desk.btn_clear') }}</a>
</form>

<div class="od-grid od-grid--2">
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="download"></i> {{ __('governance_desk.section_exports') }}</h2>
        <span class="od-card__count">{{ method_exists($exports, 'total') ? $exports->total() : $exports->count() }}</span>
    </div>
    @if($exports->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="inbox"></i></span>
                <h3 class="od-empty__title">{{ __('governance_desk.empty_exports_heading') }}</h3>
                <p class="od-empty__text">{{ __('governance_desk.empty_exports_body') }}</p>
            </div>
        </div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_filed') }}</th>
                    <th>{{ __('governance_desk.col_type') }}</th>
                    <th>{{ __('governance_desk.col_status') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($exports as $export)
                    <tr>
                        <td>{{ optional($export->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code class="oda-mono">{{ $export->export_type }}</code></td>
                        <td><span class="od-pill {{ $gdPill($export->status) }}">{{ __('governance_desk.status_' . $export->status) }}</span></td>
                        <td>
                            <a href="{{ route('portals.admin.governance.exports.show', $export->id) }}" class="od-btn od-btn--row">
                                <i data-lucide="arrow-right"></i> {{ __('governance_desk.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $exports->links() }}</div>
    @endif
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="file-pen-line"></i> {{ __('governance_desk.section_corrections') }}</h2>
        <span class="od-card__count">{{ method_exists($corrections, 'total') ? $corrections->total() : $corrections->count() }}</span>
    </div>
    @if($corrections->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-check-2"></i></span>
                <h3 class="od-empty__title">{{ __('governance_desk.empty_corrections_heading') }}</h3>
                <p class="od-empty__text">{{ __('governance_desk.empty_corrections_body') }}</p>
            </div>
        </div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_filed') }}</th>
                    <th>{{ __('governance_desk.col_resource') }}</th>
                    <th>{{ __('governance_desk.col_status') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($corrections as $correction)
                    <tr>
                        <td>{{ optional($correction->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <code class="oda-mono">{{ $correction->resource_type }}</code>
                            {{-- A request this desk cannot honour is marked HERE, in the
                                 queue, not discovered after a reviewer clicks approve. --}}
                            @if(($refusals[$correction->id] ?? null) === 'governance_desk.refusal_type_unsupported')
                                <span class="od-pill od-pill--pending">{{ __('governance_desk.refusal_heading') }}</span>
                            @endif
                        </td>
                        <td><span class="od-pill {{ $gdPill($correction->status) }}">{{ __('governance_desk.status_' . $correction->status) }}</span></td>
                        <td>
                            <a href="{{ route('portals.admin.governance.corrections.show', $correction->id) }}" class="od-btn od-btn--row">
                                <i data-lucide="arrow-right"></i> {{ __('governance_desk.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $corrections->links() }}</div>
    @endif
</div>
</div>

</div>
@endsection
