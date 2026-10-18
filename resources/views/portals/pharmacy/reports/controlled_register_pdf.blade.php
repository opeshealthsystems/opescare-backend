{{--
    The month's controlled-substance register on the OpesCare Secure sheet —
    dispense events, witness confirmations and reconciliations, newest first.
    Rendered at download time, never stored. The patient is named by Health ID
    only.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('pharmacy_reports.register_title'))

@php $meta = $register['meta']; @endphp

@section('content')
    <h1>{{ __('pharmacy_reports.register_title') }}</h1>
    <p class="subtitle">{{ __('pharmacy_reports.register_subtitle') }}</p>

    <table class="kv">
        <tr><td class="k">{{ __('pharmacy_reports.pdf_facility') }}</td><td class="v">{{ $facility?->name ?? '—' }}</td></tr>
        <tr><td class="k">{{ __('pharmacy_reports.register_month') }}</td><td class="v id">{{ $register['month'] }}</td></tr>
        <tr><td class="k">{{ __('pharmacy_reports.pdf_period') }}</td><td class="v">{{ $register['from'] }} → {{ $register['to'] }}</td></tr>
        <tr><td class="k">{{ __('pharmacy_reports.pdf_generated') }}</td><td class="v">{{ $issuedAt->translatedFormat('d M Y, H:i') }}</td></tr>
    </table>

    <h2>{{ __('pharmacy_reports.register_title') }}
        <span class="hint">{{ __('pharmacy_reports.register_sum', $meta) }}</span>
    </h2>

    @if($register['rows'] === [])
        <p class="empty">{{ __('pharmacy_reports.register_no_rows') }}</p>
    @else
        <table class="data">
            <thead><tr>
                @foreach($register['headers'] as $h)<th>{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody>
            @foreach($register['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        @if(is_int($cell) || is_float($cell))
                            <td class="num">{{ $cell }}</td>
                        @else
                            <td>{{ $cell ?? '—' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
