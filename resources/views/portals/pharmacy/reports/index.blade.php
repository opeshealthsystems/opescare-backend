@extends('layouts.portal')
@section('title', __('pharmacy_reports.index_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_reports.nav_reports'))

@php
    $cards = [
        'expiry'     => ['icon' => 'calendar-clock', 'title' => 'pharmacy_reports.card_expiry',     'sub' => 'pharmacy_reports.card_expiry_sub'],
        'valuation'  => ['icon' => 'coins',          'title' => 'pharmacy_reports.card_valuation',  'sub' => 'pharmacy_reports.card_valuation_sub'],
        'dispensing' => ['icon' => 'bar-chart-3',    'title' => 'pharmacy_reports.card_dispensing', 'sub' => 'pharmacy_reports.card_dispensing_sub'],
        'low_stock'  => ['icon' => 'trending-down',  'title' => 'pharmacy_reports.card_low_stock',  'sub' => 'pharmacy_reports.card_low_stock_sub'],
    ];
    $money = fn ($v) => $v === null ? null : number_format((int) $v, 0, ',', ' ');
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_reports.index_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_reports.index_subtitle') }}</p>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sliders-horizontal"></i> {{ __('pharmacy_stock_desk.reports_window_title') }}</h3>
    </div>
    <form method="GET" action="{{ route('portals.pharmacy.reports') }}" class="ph-filter-form">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="rp-days">{{ __('pharmacy_reports.lbl_window') }}</label>
                <select name="days" id="rp-days" class="form-control">
                    @foreach($windows as $w)
                        <option value="{{ $w }}" @selected($w === $days)>{{ __('pharmacy_reports.window_days', ['days' => $w]) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="rp-from">{{ __('pharmacy_reports.lbl_from') }}</label>
                <input type="date" name="from" id="rp-from" class="form-control" value="{{ $from }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="rp-to">{{ __('pharmacy_reports.lbl_to') }}</label>
                <input type="date" name="to" id="rp-to" class="form-control" value="{{ $to }}">
            </div>
            <div class="form-group form-group--actions">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="filter"></i> {{ __('pharmacy_reports.btn_apply') }}</button>
            </div>
        </div>
    </form>
</div>

<div class="ph-reports">
@foreach($cards as $key => $card)
@php
    $table = $tables[$key];
    $meta  = $table['meta'];
    $summary = match ($key) {
        'expiry'     => __('pharmacy_reports.sum_lots', ['count' => $meta['lots'], 'packs' => $meta['packs']]),
        'valuation'  => __('pharmacy_reports.sum_valuation', ['items' => $meta['items'], 'packs' => $meta['packs'], 'total' => $money($meta['total_xaf'])])
                        . ($meta['unpriced'] > 0 ? ' · ' . __('pharmacy_reports.sum_unpriced', ['count' => $meta['unpriced']]) : ''),
        'dispensing' => __('pharmacy_reports.sum_dispensing', ['scripts' => $meta['scripts'], 'lines' => $meta['lines'], 'days' => $meta['days']]),
        'low_stock'  => __('pharmacy_reports.sum_low', ['count' => $meta['items'], 'out' => $meta['out']]),
    };
    $preview = array_slice($table['rows'], 0, $previewRows);
    $total   = count($table['rows']);
@endphp
<div class="panel" id="report-{{ $key }}">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="{{ $card['icon'] }}"></i> {{ __($card['title']) }} <span class="ph-count-inline">({{ $total }})</span></h3>
        <div class="panel-actions">
            <a class="btn btn-secondary btn-sm fs-tap" href="{{ route('portals.pharmacy.reports.csv', ['report' => $key] + $query) }}"><i data-lucide="download"></i> {{ __('pharmacy_reports.btn_csv') }}</a>
            <a class="btn btn-primary btn-sm fs-tap" href="{{ route('portals.pharmacy.reports.pdf', ['report' => $key] + $query) }}"><i data-lucide="file-text"></i> {{ __('pharmacy_reports.btn_pdf') }}</a>
        </div>
    </div>
    <p class="panel-sub">{{ __($card['sub']) }}</p>
    <div class="fs-note"><i data-lucide="info"></i><div>{{ $summary }}</div></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                @foreach($table['headers'] as $h)<th>{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody>
            @forelse($preview as $row)
                <tr>
                    @foreach($row as $i => $cell)
                        @php $h = $table['headers'][$i]; @endphp
                        <td data-label="{{ $h }}" class="{{ $i === 0 ? 'td-strong' : 'fs-mono' }}">
                            @if($key === 'valuation' && $i >= 2)
                                {{ $cell === null ? __('pharmacy_reports.unpriced') : $money($cell) }}
                            @elseif($key === 'low_stock' && $i === 4)
                                <span class="badge {{ $row[1] === 0 ? 'badge-danger' : 'badge-warning' }}">{{ $cell }}</span>
                            @else
                                {{ $cell ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($table['headers']) }}" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="{{ $card['icon'] }}"></i>
                        <p>{{ __('pharmacy_reports.empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($total > $previewRows)
        <div class="panel-foot">{{ __('pharmacy_reports.preview_hint', ['count' => $previewRows]) }} {{ __('pharmacy_reports.rows_total', ['count' => $total]) }}</div>
    @endif
</div>
@endforeach
</div>

@endsection
