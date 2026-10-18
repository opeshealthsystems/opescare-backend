@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_heading_controlled', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_controlled', [], app()->getLocale()))
@php
    $l = app()->getLocale();
    $statusOf = fn ($drug) => $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status;
    $alerts   = $controlled->filter(fn ($d) => in_array($statusOf($d), ['low_stock', 'out_of_stock'], true))->count();
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_controlled', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_controlled', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    <a href="#register-export" class="btn btn-primary btn-sm"><i data-lucide="file-text"></i> {{ __('pharmacy_reports.register_export_pdf', [], $l) }}</a>
</div>

<div class="ph-kpis">
    <div class="stat-card">
        <div class="stat-card__icon"><i data-lucide="lock"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.controlled_kpi_items') }}</div>
        <div class="stat-card__value">{{ $controlled->count() }}</div>
        <div class="stat-card__hint">{{ __('public.pharmacy_portal.panel_controlled_stock', [], $l) }}</div>
    </div>
    <div class="stat-card {{ $alerts > 0 ? 'stat-card--danger' : 'stat-card--success' }}">
        <div class="stat-card__icon"><i data-lucide="{{ $alerts > 0 ? 'alert-triangle' : 'check-circle' }}"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.controlled_kpi_alerts') }}</div>
        <div class="stat-card__value">{{ $alerts }}</div>
        <div class="stat-card__hint">{{ __('public.pharmacy_portal.col_status', [], $l) }}</div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="history"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.controlled_kpi_log') }}</div>
        <div class="stat-card__value">{{ $recentRx->count() }}</div>
        <div class="stat-card__hint">{{ __('public.pharmacy_portal.panel_dispensing_log', [], $l) }}</div>
    </div>
    <a class="stat-card stat-card--warning" href="#register-export">
        <div class="stat-card__icon"><i data-lucide="file-text"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.controlled_export_title') }}</div>
        <div class="stat-card__value">{{ request()->query('month') ?: now()->format('Y-m') }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_reports.register_month', [], $l) }}</div>
    </a>
</div>

<div class="ph-split">
<div class="ph-stack">

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="lock"></i> {{ __('public.pharmacy_portal.panel_controlled_stock', [], $l) }} <span class="ph-count-inline">({{ $controlled->count() }})</span></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.pharmacy_portal.col_drug', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_form', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_qty', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_status', [], $l) }}</th>
                </tr></thead>
                <tbody>
                    @forelse($controlled as $drug)
                    @php
                        $st   = $statusOf($drug);
                        $tone = match($st) { 'in_stock' => 'green', 'low_stock' => 'amber', 'out_of_stock' => 'red', default => 'grey' };
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.pharmacy_portal.col_drug', [], $l) }}">
                            <div class="ph-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'lock', 'tone' => $tone])
                                <div class="ph-cell__title">{{ $drug->medicine?->name ?? '—' }}</div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_form', [], $l) }}" class="td-muted">{{ $drug->medicine?->form }} {{ $drug->medicine?->strength }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_qty', [], $l) }}" class="td-num">{{ $drug->packs_available ?? '—' }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_status', [], $l) }}">
                            <span class="badge badge-{{ match($st) { 'in_stock' => 'success', 'low_stock' => 'warning', 'out_of_stock' => 'danger', default => 'neutral' } }}">
                                @enum($st, 'stock_status')
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-cell">
                        <div class="empty-state">
                            <i data-lucide="lock"></i>
                            <p>{{ __('public.pharmacy_portal.no_controlled', [], $l) }}</p>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('public.pharmacy_portal.panel_dispensing_log', [], $l) }} <span class="ph-count-inline">({{ $recentRx->count() }})</span></h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.pharmacy_portal.col_patient', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_items', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}</th>
                </tr></thead>
                <tbody>
                    @forelse($recentRx as $rx)
                    @php $pName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? '')) ?: '—'; @endphp
                    <tr>
                        <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}">
                            <div class="ph-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['name' => $pName, 'tone' => 'teal'])
                                <div class="ph-cell__title">{{ $pName }}</div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}" class="td-muted">{{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items_count', [], $l) }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}" class="td-muted">{{ $rx->dispensed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-cell">
                        <div class="empty-state">
                            <i data-lucide="history"></i>
                            <p>{{ __('public.pharmacy_portal.no_dispensing_records', [], $l) }}</p>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Monthly register export: the month's dispense, witness and reconciliation
     entries as the secure-sheet PDF or a CSV. A plain GET form — no script. --}}
@php $registerMonth = request()->query('month') ?: now()->format('Y-m'); @endphp
<div class="panel ph-detail" id="register-export">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('pharmacy_stock_desk.controlled_export_title') }}</h3>
    </div>
    <div class="panel-body">
        <div class="ph-infocard ph-infocard--warning mb-6">
            <div class="ph-infocard__icon"><i data-lucide="shield-check"></i></div>
            <div>
                <div class="ph-infocard__k">{{ __('pharmacy_reports.register_title', [], $l) }}</div>
                <div class="ph-infocard__note">{{ __('pharmacy_stock_desk.controlled_export_hint') }}</div>
            </div>
        </div>
        <form method="GET" action="{{ route('portals.pharmacy.controlled.register') }}">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="cs-month">{{ __('pharmacy_reports.register_month', [], $l) }}</label>
                    <input type="month" name="month" id="cs-month" class="form-control" value="{{ $registerMonth }}">
                </div>
            </div>
            <div class="form-group--actions mt-6">
                <button type="submit" name="format" value="pdf" class="btn btn-primary btn-sm fs-tap"><i data-lucide="file-text"></i> {{ __('pharmacy_reports.register_export_pdf', [], $l) }}</button>
                <button type="submit" name="format" value="csv" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="download"></i> {{ __('pharmacy_reports.register_export_csv', [], $l) }}</button>
            </div>
        </form>
    </div>
</div>

</div>

@endsection
