@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_heading_controlled', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_controlled', [], app()->getLocale()))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_controlled', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_controlled', [], $l) }}</p>
</div>

<div class="field-grid">

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="lock"></i> {{ __('public.pharmacy_portal.panel_controlled_stock', [], $l) }}</h3></div>
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
                    @php $st = $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status; @endphp
                    <tr>
                        <td data-label="{{ __('public.pharmacy_portal.col_drug', [], $l) }}" class="td-strong">{{ $drug->medicine?->name ?? '—' }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_form', [], $l) }}" class="td-muted">{{ $drug->medicine?->form }} {{ $drug->medicine?->strength }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_qty', [], $l) }}" class="td-strong">{{ $drug->packs_available ?? '—' }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_status', [], $l) }}">
                            <span class="badge badge-{{ match($st) { 'in_stock' => 'success', 'low_stock' => 'warning', 'out_of_stock' => 'danger', default => 'neutral' } }}">
                                @enum($st, 'stock_status')
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="td-muted empty-cell">{{ __('public.pharmacy_portal.no_controlled', [], $l) }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('public.pharmacy_portal.panel_dispensing_log', [], $l) }}</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.pharmacy_portal.col_patient', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_items', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}</th>
                </tr></thead>
                <tbody>
                    @forelse($recentRx as $rx)
                    <tr>
                        <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}" class="td-strong">{{ trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? '')) ?: '—' }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}" class="td-muted">{{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items_count', [], $l) }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_dispensed', [], $l) }}" class="td-muted">{{ $rx->dispensed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="td-muted empty-cell">{{ __('public.pharmacy_portal.no_dispensing_records', [], $l) }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
