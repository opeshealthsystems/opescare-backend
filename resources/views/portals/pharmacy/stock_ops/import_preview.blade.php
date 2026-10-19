@extends('layouts.portal')
@section('title', __('pharmacy_stock_import.preview_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_stock_import.preview_title') }}</h1>
        <p class="os-head__sub">{{ $import->original_name }} · {{ __('pharmacy_stock_import.mode_' . $import->mode) }} · {{ __('pharmacy_stock_import.import_status_' . $import->status) }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.import') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_import.back') }}</a>
        @if($results->where('status', '!=', 'ok')->isNotEmpty())
            <a href="{{ route('portals.pharmacy.stock.import.errors', $import->id) }}" class="od-btn od-btn--secondary"><i data-lucide="download"></i> {{ __('pharmacy_stock_import.btn_error_report') }}</a>
        @endif
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

@if($import->status === 'committed')
    <div class="os-banner os-banner--ok" role="status"><i data-lucide="check-circle-2"></i><div>
        {{ __('pharmacy_stock_import.committed_summary', ['imported' => $import->imported_count, 'errors' => $import->error_count]) }}
        @if($import->goods_receipt_id)
            <a href="{{ route('portals.pharmacy.stock.receipts.show', $import->goods_receipt_id) }}">{{ __('pharmacy_stock_import.view_receipt') }}</a>
        @endif
    </div></div>
@elseif($import->status === 'queued')
    <div class="os-banner" role="status"><i data-lucide="loader"></i><div>{{ __('pharmacy_stock_import.flash_queued') }}</div></div>
@elseif($import->status === 'failed')
    <div class="os-banner os-banner--danger" role="alert"><i data-lucide="alert-octagon"></i><div>{{ __('pharmacy_stock_import.flash_failed') }}</div></div>
@endif

@if($import->status === 'preview')
<section class="od-card os-card">
    <div class="os-card__body os-stack">
        <p>{{ __('pharmacy_stock_import.preview_counts', $counts) }}</p>
        <form method="POST" action="{{ route('portals.pharmacy.stock.import.commit', $import->id) }}" class="os-stack">
            @csrf
            @if($counts['review'] > 0)
                <label><input type="checkbox" name="accept_fuzzy" value="1"> {{ __('pharmacy_stock_import.accept_fuzzy', ['count' => $counts['review']]) }}</label>
            @endif
            <label><input type="checkbox" name="confirm" value="1" required> {{ __('pharmacy_stock_import.confirm_publish') }}</label>
            <div class="os-head__actions">
                <button type="submit" class="od-btn od-btn--primary" @disabled($counts['ok'] + $counts['review'] === 0)><i data-lucide="check-circle-2"></i> {{ __('pharmacy_stock_import.btn_commit') }}</button>
                <a href="{{ route('portals.pharmacy.stock.import.mapping', $import->id) }}" class="od-btn od-btn--secondary"><i data-lucide="columns-3"></i> {{ __('pharmacy_stock_import.btn_change_columns') }}</a>
            </div>
        </form>
    </div>
</section>
@endif

<section class="od-card os-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ $import->status === 'preview' ? __('pharmacy_stock_import.lines_title') : __('pharmacy_stock_import.not_imported_title') }} <span class="od-card__count">({{ $results->count() }})</span></h2></div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-num">{{ __('pharmacy_stock_import.report_line') }}</th>
                <th>{{ __('pharmacy_stock_import.report_medicine') }}</th>
                <th>{{ __('pharmacy_stock_import.col_match') }}</th>
                <th class="os-num">{{ __('pharmacy_stock_import.col_quantity') }}</th>
                <th>{{ __('pharmacy_stock_import.col_batch') }}</th>
                <th>{{ __('pharmacy_stock_import.col_expiry') }}</th>
                <th class="os-num">{{ __('pharmacy_stock_import.col_unit_price') }}</th>
                <th>{{ __('pharmacy_stock_import.report_status') }}</th>
            </tr></thead>
            <tbody>
            @forelse($results->take(500) as $r)
                <tr>
                    <td class="os-num">{{ $r['line'] }}</td>
                    <td data-label="{{ __('pharmacy_stock_import.report_medicine') }}">{{ $r['medicine_text'] }} <span class="os-cell__sub">{{ trim($r['strength'] . ' ' . $r['form']) }}</span></td>
                    <td data-label="{{ __('pharmacy_stock_import.col_match') }}">
                        @if($r['medicine_name'])
                            {{ $r['medicine_name'] }} <span class="os-cell__sub">{{ __('pharmacy_stock_import.method_' . $r['method']) }} · {{ $r['confidence'] }}%</span>
                        @else
                            <span class="os-cell__sub">{{ __('pharmacy_stock_import.method_' . $r['method']) }}</span>
                        @endif
                    </td>
                    <td class="os-num">{{ $r['quantity'] ?? '—' }}</td>
                    <td>{{ $r['batch'] }}</td>
                    <td>{{ $r['expiry'] ?? '—' }}</td>
                    <td class="os-num">{{ $r['unit_price'] !== null ? \App\Services\Billing\Xaf::format(\App\Services\Billing\Xaf::int($r['unit_price'])) : '—' }}</td>
                    <td>
                        @include('portals.pharmacy.stock_ops._pill', ['tone' => $r['status'] === 'ok' ? 'ok' : ($r['status'] === 'review' ? 'low' : 'expired'), 'label' => __('pharmacy_stock_import.status_' . $r['status'])])
                        @foreach($r['errors'] as [$key, $params])
                            <div class="os-cell__sub">{{ __('pharmacy_stock_import.' . $key, $params) }}</div>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('pharmacy_stock_import.lines_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($results->count() > 500)
        <p class="os-cell__sub">{{ __('pharmacy_stock_import.preview_truncated', ['count' => $results->count()]) }}</p>
    @endif
</section>

</div>
@endsection
