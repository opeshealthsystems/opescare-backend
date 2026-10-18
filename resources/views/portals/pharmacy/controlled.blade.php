@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_heading_controlled', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_controlled', [], app()->getLocale()))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection
@php
    $l = app()->getLocale();
    $statusOf = fn ($drug) => $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status;
    $alerts   = $controlled->filter(fn ($d) => in_array($statusOf($d), ['low_stock', 'out_of_stock'], true))->count();
    $registerMonth = request()->query('month') ?: now()->format('Y-m');
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('public.pharmacy_portal.page_heading_controlled', [], $l) }}</h1>
        <p class="os-head__sub">{{ __('public.pharmacy_portal.page_subtitle_controlled', [], $l) }}</p>
    </div>
</header>

<div class="os-kpis">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'lock',
        'label' => __('pharmacy_stock_desk.controlled_kpi_items'), 'value' => $controlled->count(),
        'sub' => __('public.pharmacy_portal.panel_controlled_stock', [], $l)])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => $alerts > 0 ? 'red' : 'green', 'icon' => $alerts > 0 ? 'alert-triangle' : 'check-circle-2',
        'label' => __('pharmacy_stock_desk.controlled_kpi_alerts'), 'value' => $alerts,
        'sub' => __('public.pharmacy_portal.col_status', [], $l)])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'history',
        'label' => __('pharmacy_stock_desk.controlled_kpi_log'), 'value' => $recentRx->count(),
        'sub' => __('public.pharmacy_portal.panel_dispensing_log', [], $l)])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'amber', 'icon' => 'file-text', 'href' => '#register-export',
        'label' => __('pharmacy_stock_desk.controlled_export_title'), 'value' => $registerMonth,
        'sub' => __('pharmacy_reports.register_month', [], $l)])
</div>

<div class="os-split">
<div class="os-stack">

    <section class="od-card os-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('public.pharmacy_portal.panel_controlled_stock', [], $l) }} <span class="od-card__count">({{ $controlled->count() }})</span></h2>
        </div>
        <div class="os-table-wrap">
            <table class="od-table os-table">
                <thead><tr>
                    <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                    <th>{{ __('public.pharmacy_portal.col_drug', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_qty', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_status', [], $l) }}</th>
                </tr></thead>
                <tbody>
                    @forelse($controlled as $drug)
                    @php
                        $st   = $statusOf($drug);
                        $tone = match($st) { 'in_stock' => 'green', 'low_stock', 'out_of_stock' => 'red', default => 'grey' };
                        $pill = match($st) { 'in_stock' => 'ok', 'low_stock' => 'low', 'out_of_stock' => 'out', default => 'neutral' };
                    @endphp
                    <tr>
                        <td class="os-col-num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_drug', [], $l) }}">
                            <div class="os-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $tone])
                                <div class="os-cell__text">
                                    <span class="os-cell__title">{{ $drug->medicine?->name ?? '—' }}</span>
                                    <span class="os-cell__sub">{{ $drug->medicine?->form }} {{ $drug->medicine?->strength }}</span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_qty', [], $l) }}" class="os-num">{{ $drug->packs_available ?? '—' }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_status', [], $l) }}">
                            <span class="os-pill os-pill--{{ $pill }}">@enum($st, 'stock_status')</span>
                        </td>
                    </tr>
                    @empty
                        @include('portals.pharmacy.stock_ops._empty', ['cols' => 4, 'icon' => 'lock', 'text' => __('public.pharmacy_portal.no_controlled', [], $l)])
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="od-card os-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('public.pharmacy_portal.panel_dispensing_log', [], $l) }} <span class="od-card__count">({{ $recentRx->count() }})</span></h2>
        </div>
        <div class="os-table-wrap">
            <table class="od-table os-table">
                <thead><tr>
                    <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                    <th>{{ __('public.pharmacy_portal.col_patient', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_items', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}</th>
                </tr></thead>
                <tbody>
                    @forelse($recentRx as $rx)
                    @php $pName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? '')) ?: '—'; @endphp
                    <tr>
                        <td class="os-col-num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}">
                            <div class="os-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['name' => $pName, 'tone' => ['blue', 'amber', 'teal', 'rose', 'green'][abs(crc32($pName)) % 5]])
                                <div class="os-cell__text"><span class="os-cell__title">{{ $pName }}</span></div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}">{{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items_count', [], $l) }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}">
                            <span class="os-cell__main">{{ $rx->dispensed_at?->translatedFormat('H:i') ?? '—' }}</span>
                            <span class="os-cell__sub">{{ $rx->dispensed_at?->translatedFormat('d M Y') }}</span>
                        </td>
                    </tr>
                    @empty
                        @include('portals.pharmacy.stock_ops._empty', ['cols' => 4, 'icon' => 'history', 'text' => __('public.pharmacy_portal.no_dispensing_records', [], $l)])
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

{{-- Monthly register export: secure-sheet PDF or CSV. A plain GET form — no script. --}}
<aside class="od-card os-card os-detail od-detail" id="register-export" aria-label="{{ __('pharmacy_stock_desk.controlled_export_title') }}">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.controlled_export_title') }}</h2>
    </div>
    <div class="os-info">
        <span class="os-info__icon"><i data-lucide="shield-check"></i></span>
        <div>
            <div class="os-info__k">{{ __('pharmacy_reports.register_title', [], $l) }}</div>
            <div class="os-info__note">{{ __('pharmacy_stock_desk.controlled_export_hint') }}</div>
        </div>
    </div>
    <form method="GET" action="{{ route('portals.pharmacy.controlled.register') }}" class="os-form">
        <div class="os-field">
            <label class="os-label" for="cs-month">{{ __('pharmacy_reports.register_month', [], $l) }}</label>
            <input type="month" name="month" id="cs-month" class="os-input" value="{{ $registerMonth }}">
        </div>
        <div class="os-actions">
            <button type="submit" name="format" value="pdf" class="od-btn od-btn--primary"><i data-lucide="file-text"></i> {{ __('pharmacy_reports.register_export_pdf', [], $l) }}</button>
            <button type="submit" name="format" value="csv" class="od-btn od-btn--secondary"><i data-lucide="download"></i> {{ __('pharmacy_reports.register_export_csv', [], $l) }}</button>
        </div>
    </form>
</aside>

</div>

</div>
@endsection
