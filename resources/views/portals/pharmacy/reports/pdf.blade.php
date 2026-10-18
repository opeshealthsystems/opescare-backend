{{--
    One pharmacy report on the OpesCare Secure sheet — rendered at download
    time, never stored. The rows are exactly the ones the reports page
    previews and the CSV carries; nothing is added for print.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('pharmacy_reports.pdf_title_' . $report))

@php
    $money = fn ($v) => $v === null ? __('pharmacy_reports.unpriced') : number_format((int) $v, 0, ',', ' ');
    $meta  = $table['meta'];
@endphp

@section('content')
    <h1>{{ __('pharmacy_reports.pdf_title_' . $report) }}</h1>
    <p class="subtitle">{{ __('pharmacy_reports.card_' . $report) }}</p>

    <table class="kv">
        <tr><td class="k">{{ __('pharmacy_reports.pdf_facility') }}</td><td class="v">{{ $facility?->name ?? '—' }}</td></tr>
        @if($report === 'expiry')
            <tr><td class="k">{{ __('pharmacy_reports.pdf_window') }}</td><td class="v">{{ __('pharmacy_reports.window_days', ['days' => $params['days']]) }}</td></tr>
        @elseif($report === 'dispensing')
            <tr><td class="k">{{ __('pharmacy_reports.pdf_period') }}</td><td class="v">{{ $params['from'] }} → {{ $params['to'] }}</td></tr>
        @endif
        <tr><td class="k">{{ __('pharmacy_reports.pdf_generated') }}</td><td class="v">{{ $issuedAt->translatedFormat('d M Y, H:i') }}</td></tr>
    </table>

    <h2>{{ __('pharmacy_reports.card_' . $report) }} <span class="hint">{{ __('pharmacy_reports.rows_total', ['count' => count($table['rows'])]) }}</span></h2>

    @if($table['rows'] === [])
        <p class="empty">{{ __('pharmacy_reports.pdf_no_rows') }}</p>
    @else
        <table class="data">
            <thead><tr>
                @foreach($table['headers'] as $h)<th>{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody>
            @foreach($table['rows'] as $row)
                <tr>
                    @foreach($row as $i => $cell)
                        @if($report === 'valuation' && $i >= 2)
                            <td class="num">{{ $money($cell) }}</td>
                        @elseif(is_int($cell) || is_float($cell))
                            <td class="num">{{ $cell }}</td>
                        @else
                            <td>{{ $cell ?? '—' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            @if($report === 'valuation')
                <tr>
                    <td class="group" colspan="3">{{ __('pharmacy_reports.pdf_total') }}</td>
                    <td class="group num">{{ number_format((int) $meta['total_xaf'], 0, ',', ' ') }} {{ __('pharmacy_reports.currency') }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    @endif
@endsection
