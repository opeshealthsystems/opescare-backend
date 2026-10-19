{{-- Group D import: web page over the platform's ComplianceExportService.
     CSVs are rendered on demand and never stored; they carry metadata and counts only — no patient or clinical content. --}}
@extends('layouts.portal')
@section('title', __('security_ops.ce_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.ce_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.ce_subtitle') }}</p>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('security_ops.ce_request') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.security.compliance_export.download') }}" class="oda-form oda-form--3">
            @csrf
            <label class="oda-label">
                <span class="oda-label__text">{{ __('security_ops.ce_lbl_type') }}</span>
                <select name="export_type" class="od-select" aria-label="{{ __('security_ops.ce_lbl_type') }}">
                    @foreach($types as $t)<option value="{{ $t }}">{{ __('security_ops.ce_type_'.$t) }}</option>@endforeach
                </select>
            </label>
            <label class="oda-label" for="ce_from">
                <span class="oda-label__text">{{ __('security_ops.ce_lbl_from') }}</span>
                <input id="ce_from" type="date" name="from" class="od-input">
            </label>
            <label class="oda-label" for="ce_to">
                <span class="oda-label__text">{{ __('security_ops.ce_lbl_to') }}</span>
                <input id="ce_to" type="date" name="to" class="od-input">
            </label>
            <p class="oda-help oda-form__full"><i data-lucide="lock"></i> {{ __('security_ops.ce_no_clinical') }}</p>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="download"></i> {{ __('security_ops.ce_btn_download') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.ce_recent') }} <span class="od-card__count">({{ $exports->count() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($exports->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="history"></i></span>
                <p class="od-empty__text">{{ __('security_ops.ce_empty') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.ce_lbl_type') }}</th>
                    <th>{{ __('security_ops.ce_col_filters') }}</th>
                    <th>{{ __('security_ops.ce_col_when') }}</th>
                </tr></thead>
                <tbody>
                @foreach($exports as $export)
                    <tr>
                        <td data-label="{{ __('security_ops.ce_lbl_type') }}"><span class="od-table__name">{{ in_array($export->export_type, $types) ? __('security_ops.ce_type_'.$export->export_type) : $export->export_type }}</span></td>
                        <td data-label="{{ __('security_ops.ce_col_filters') }}" class="oda-ga-muted">{{ collect($export->filters ?? [])->map(fn ($v, $k) => $k.': '.$v)->implode(', ') ?: '—' }}</td>
                        <td data-label="{{ __('security_ops.ce_col_when') }}" class="oda-ga-muted">{{ $export->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
</div>
@endsection
