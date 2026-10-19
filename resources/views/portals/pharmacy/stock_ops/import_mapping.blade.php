@extends('layouts.portal')
@section('title', __('pharmacy_stock_import.mapping_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_stock_import.mapping_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_stock_import.mapping_subtitle', ['file' => $import->original_name, 'format' => strtoupper($import->file_format), 'rows' => $import->row_count]) }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<form method="POST" action="{{ route('portals.pharmacy.stock.import.mapping.store', $import->id) }}" class="os-stack">
    @csrf
    <section class="od-card os-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_stock_import.mapping_columns') }}</h2></div>
        <div class="os-card__body">
            <p class="os-cell__sub">{{ __('pharmacy_stock_import.mapping_help') }}</p>
            <div class="os-grid os-grid--3">
            @foreach($fields as $field)
                @php $current = old('map.' . $field, $import->mapping[$field] ?? null); @endphp
                <div class="os-field">
                    <label class="os-label" for="map-{{ $field }}">{{ __('pharmacy_stock_import.col_' . $field) }}@if(in_array($field, $required, true)) *@endif</label>
                    <select name="map[{{ $field }}]" id="map-{{ $field }}" class="os-input">
                        <option value="">{{ __('pharmacy_stock_import.map_none') }}</option>
                        @foreach($import->headers ?? [] as $i => $header)
                            <option value="{{ $i }}" @selected($current !== null && (string) $current === (string) $i)>{{ $header !== '' ? $header : __('pharmacy_stock_import.map_column_n', ['n' => $i + 1]) }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            </div>
        </div>
    </section>

    <section class="od-card os-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_stock_import.mode_title') }}</h2></div>
        <div class="os-card__body os-stack">
            <label><input type="radio" name="mode" value="delivery" @checked(old('mode', $import->mode) === 'delivery')> <strong>{{ __('pharmacy_stock_import.mode_delivery') }}</strong> — {{ __('pharmacy_stock_import.mode_delivery_help') }}</label>
            <label><input type="radio" name="mode" value="stocktake" @checked(old('mode', $import->mode) === 'stocktake')> <strong>{{ __('pharmacy_stock_import.mode_stocktake') }}</strong> — {{ __('pharmacy_stock_import.mode_stocktake_help') }}</label>
        </div>
    </section>

    <section class="od-card os-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_stock_import.sample_title') }}</h2></div>
        <div class="os-table-wrap">
            <table class="od-table os-table">
                <thead><tr>@foreach($import->headers ?? [] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($sample as $row)
                    <tr>@foreach($import->headers ?? [] as $i => $h)<td>{{ $row[$i] ?? '' }}</td>@endforeach</tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="os-head__actions">
        <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_stock_import.btn_check') }}</button>
    </div>
</form>

<form method="POST" action="{{ route('portals.pharmacy.stock.import.cancel', $import->id) }}">
    @csrf
    <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="x"></i> {{ __('pharmacy_stock_import.btn_cancel') }}</button>
</form>

</div>
@endsection
