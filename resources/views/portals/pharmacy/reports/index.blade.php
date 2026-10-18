@extends('layouts.portal')
@section('title', __('pharmacy_reports.index_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_reports.nav_reports'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $cards = [
        'expiry'     => ['icon' => 'calendar-clock', 'tone' => 'amber', 'title' => 'pharmacy_reports.card_expiry',     'sub' => 'pharmacy_reports.card_expiry_sub'],
        'valuation'  => ['icon' => 'coins',          'tone' => 'green', 'title' => 'pharmacy_reports.card_valuation',  'sub' => 'pharmacy_reports.card_valuation_sub'],
        'dispensing' => ['icon' => 'bar-chart-3',    'tone' => 'blue',  'title' => 'pharmacy_reports.card_dispensing', 'sub' => 'pharmacy_reports.card_dispensing_sub'],
        'low_stock'  => ['icon' => 'trending-down',  'tone' => 'red',   'title' => 'pharmacy_reports.card_low_stock',  'sub' => 'pharmacy_reports.card_low_stock_sub'],
    ];
    $money = fn ($v) => $v === null ? null : number_format((int) $v, 0, ',', ' ');
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_reports.index_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_reports.index_subtitle') }}</p>
    </div>
</header>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.reports_window_title') }}</h2>
    </div>
    <form method="GET" action="{{ route('portals.pharmacy.reports') }}" class="os-window">
        <div class="os-field">
            <label class="os-label" for="rp-days">{{ __('pharmacy_reports.lbl_window') }}</label>
            <select name="days" id="rp-days" class="os-input">
                @foreach($windows as $w)
                    <option value="{{ $w }}" @selected($w === $days)>{{ __('pharmacy_reports.window_days', ['days' => $w]) }}</option>
                @endforeach
            </select>
        </div>
        <div class="os-field">
            <label class="os-label" for="rp-from">{{ __('pharmacy_reports.lbl_from') }}</label>
            <input type="date" name="from" id="rp-from" class="os-input" value="{{ $from }}">
        </div>
        <div class="os-field">
            <label class="os-label" for="rp-to">{{ __('pharmacy_reports.lbl_to') }}</label>
            <input type="date" name="to" id="rp-to" class="os-input" value="{{ $to }}">
        </div>
        <button type="submit" class="od-btn od-btn--primary"><i data-lucide="filter"></i> {{ __('pharmacy_reports.btn_apply') }}</button>
    </form>
    <p class="os-note"><i data-lucide="info"></i> {{ __('pharmacy_stock_desk.reports_hint') }}</p>
</section>

<div class="os-reports">
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
<section class="od-card os-card" id="report-{{ $key }}">
    <div class="od-card__head">
        <h2 class="od-card__title"><span class="os-report__icon os-report__icon--{{ $card['tone'] }}"><i data-lucide="{{ $card['icon'] }}"></i></span>{{ __($card['title']) }} <span class="od-card__count">({{ $total }})</span></h2>
        <div class="os-head-actions">
            <a class="od-btn od-btn--secondary" href="{{ route('portals.pharmacy.reports.csv', ['report' => $key] + $query) }}"><i data-lucide="download"></i> {{ __('pharmacy_reports.btn_csv') }}</a>
            <a class="od-btn od-btn--primary" href="{{ route('portals.pharmacy.reports.pdf', ['report' => $key] + $query) }}"><i data-lucide="file-text"></i> {{ __('pharmacy_reports.btn_pdf') }}</a>
        </div>
    </div>
    <p class="os-card__sub">{{ __($card['sub']) }}</p>
    <p class="os-report__sum">{{ $summary }}</p>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                @foreach($table['headers'] as $h)<th>{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody>
            @forelse($preview as $row)
                <tr>
                    @foreach($row as $i => $cell)
                        @php $h = $table['headers'][$i]; @endphp
                        <td data-label="{{ $h }}" class="{{ $i === 0 ? 'os-cell__title' : 'os-num' }}">
                            @if($key === 'valuation' && $i >= 2)
                                {{ $cell === null ? __('pharmacy_reports.unpriced') : $money($cell) }}
                            @elseif($key === 'low_stock' && $i === 4)
                                @include('portals.pharmacy.stock_ops._pill', ['tone' => $row[1] === 0 ? 'low' : 'reorder', 'label' => $cell])
                            @else
                                {{ $cell ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => count($table['headers']), 'icon' => $card['icon'], 'text' => __('pharmacy_reports.empty')])
            @endforelse
            </tbody>
        </table>
    </div>
    @if($total > $previewRows)
        <div class="os-card__foot">{{ __('pharmacy_reports.preview_hint', ['count' => $previewRows]) }} {{ __('pharmacy_reports.rows_total', ['count' => $total]) }}</div>
    @endif
</section>
@endforeach
</div>

</div>
@endsection
