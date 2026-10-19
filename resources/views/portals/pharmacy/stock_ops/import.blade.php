@extends('layouts.portal')
@section('title', __('pharmacy_stock_import.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_stock_import.title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_stock_import.subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_import.step1_title') }}</h2>
    </div>
    <div class="os-card__body os-stack">
        <p>{{ __('pharmacy_stock_import.template_intro') }}</p>
        <div class="os-head__actions">
            <a href="{{ route('portals.pharmacy.stock.import.template', 'xlsx') }}" class="od-btn od-btn--secondary"><i data-lucide="file-spreadsheet"></i> {{ __('pharmacy_stock_import.btn_template_xlsx') }}</a>
            <a href="{{ route('portals.pharmacy.stock.import.template', 'csv') }}" class="od-btn od-btn--secondary"><i data-lucide="file-text"></i> {{ __('pharmacy_stock_import.btn_template_csv') }}</a>
        </div>
    </div>
</section>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_import.step2_title') }}</h2>
    </div>
    <form method="POST" action="{{ route('portals.pharmacy.stock.import.store') }}" enctype="multipart/form-data" class="os-card__body os-stack">
        @csrf
        <div class="os-field">
            <label class="os-label" for="si-file">{{ __('pharmacy_stock_import.lbl_file') }}</label>
            <input type="file" name="file" id="si-file" class="os-input" required accept=".{{ implode(',.', $extensions) }}">
            <p class="os-cell__sub">{{ __('pharmacy_stock_import.hint_formats', ['max' => $maxMb, 'rows' => \App\Services\Pharmacy\StockImport\SpreadsheetReader::MAX_ROWS]) }}</p>
            <p class="os-cell__sub"><i data-lucide="info"></i> {{ __('pharmacy_stock_import.hint_other_documents') }}</p>
        </div>
        <div>
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="upload"></i> {{ __('pharmacy_stock_import.btn_upload') }}</button>
        </div>
    </form>
</section>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_import.recent_title') }}</h2>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th>{{ __('pharmacy_stock_import.col_file') }}</th>
                <th>{{ __('pharmacy_stock_import.col_date') }}</th>
                <th>{{ __('pharmacy_stock_import.col_mode') }}</th>
                <th>{{ __('pharmacy_stock_import.col_status') }}</th>
                <th class="os-num">{{ __('pharmacy_stock_import.col_imported') }}</th>
                <th class="os-num">{{ __('pharmacy_stock_import.col_errors') }}</th>
            </tr></thead>
            <tbody>
            @forelse($recent as $imp)
                <tr>
                    <td data-label="{{ __('pharmacy_stock_import.col_file') }}">
                        @if($imp->status === 'mapping')
                            <a href="{{ route('portals.pharmacy.stock.import.mapping', $imp->id) }}">{{ $imp->original_name }}</a>
                        @elseif($imp->status !== 'cancelled')
                            <a href="{{ route('portals.pharmacy.stock.import.preview', $imp->id) }}">{{ $imp->original_name }}</a>
                        @else
                            {{ $imp->original_name }}
                        @endif
                        <span class="os-cell__sub">{{ strtoupper($imp->file_format) }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_import.col_date') }}">{{ $imp->created_at?->translatedFormat('d M Y H:i') }}</td>
                    <td data-label="{{ __('pharmacy_stock_import.col_mode') }}">{{ __('pharmacy_stock_import.mode_' . $imp->mode) }}</td>
                    <td data-label="{{ __('pharmacy_stock_import.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => $imp->status === 'committed' ? 'ok' : ($imp->status === 'failed' ? 'expired' : 'neutral'), 'label' => __('pharmacy_stock_import.import_status_' . $imp->status)])</td>
                    <td class="os-num">{{ $imp->imported_count }}</td>
                    <td class="os-num">{{ $imp->error_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('pharmacy_stock_import.recent_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

</div>
@endsection
