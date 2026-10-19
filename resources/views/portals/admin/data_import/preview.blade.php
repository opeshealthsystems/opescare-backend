@extends('layouts.portal')
@section('title', __('public.stf_import_validation_summary'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_import_validation_summary') }}</h1>
        <p class="od-page__sub">{{ $job->original_filename }} · {{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</p>
    </div>
    <div class="od-row">
        <span class="od-pill od-pill--{{ $job->status === 'validated' ? 'ready' : 'urgent' }}">@enum($job->status)</span>
    </div>
</div>

@include('portals.admin.data_import._wizard_steps', ['step' => 3])

@if(session('success'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@php
    $cards = [
        [__('public.stf_import_total_rows'), (int) $job->total_rows,   'blue',                                   'rows-3'],
        [__('public.stf_import_valid'),      (int) $job->valid_rows,   'green',                                  'check-circle'],
        [__('public.stf_import_invalid'),    (int) $job->invalid_rows, $job->invalid_rows > 0 ? 'rose' : 'sky', 'triangle-alert'],
    ];
@endphp
<div class="od-kpis oda-gb-kpis--3">
    @foreach($cards as [$label, $value, $tint, $icon])
    <div class="od-kpi od-tint--{{ $tint }}">
        <span class="od-kpi__icon"><i data-lucide="{{ $icon }}"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $label }}</span>
            <span class="od-kpi__value">{{ number_format($value) }}</span>
        </span>
    </div>
    @endforeach
</div>

@if($job->rowErrors->count() > 0)
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.stf_import_val_errors_title') }}</h2>
        <span class="od-card__count">{{ $job->rowErrors->count() }}</span>
        <span class="od-card__link">{{ __('public.stf_import_showing_errors') }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_import_col_row_no') }}</th>
                        <th>{{ __('public.stf_import_col_field') }}</th>
                        <th>{{ __('public.stf_import_col_error') }}</th>
                        <th>{{ __('public.stf_import_col_message') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($job->rowErrors as $err)
                    <tr>
                        <td data-label="{{ __('public.stf_import_col_row_no') }}">{{ $err->row_number }}</td>
                        <td data-label="{{ __('public.stf_import_col_field') }}"><span class="od-pill od-pill--neutral">{{ $err->field ?? '—' }}</span></td>
                        <td data-label="{{ __('public.stf_import_col_error') }}"><span class="oda-mono">{{ $err->error_code }}</span></td>
                        <td data-label="{{ __('public.stf_import_col_message') }}">{{ $err->message }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif

<section class="od-card">
    <div class="od-card__body od-stack">
        @if($job->canBeApproved())
            <div class="oda-gb-alert oda-gb-alert--ok">
                <i data-lucide="shield-check"></i>
                <div>
                    <strong>{{ __('public.stf_import_ready_title') }}</strong>
                    <p>
                        {{ number_format($job->valid_rows) }} {{ __('public.stf_import_ready_desc') }}
                        @if($job->invalid_rows > 0)
                            {{ number_format($job->invalid_rows) }} {{ __('public.stf_import_invalid_skipped') }}
                        @endif
                        {{ __('public.stf_import_no_undo') }}
                    </p>
                </div>
            </div>
            <div class="od-row">
                <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_edit_mapping') }}</a>
                <form method="POST" action="{{ route('portals.admin.data_import.approve', $job->id) }}" class="oda-inline-form">
                    @csrf
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm"
                        data-confirm="{{ __('staff_data.confirm_approve', ['n' => $job->valid_rows]) }}">
                        <i data-lucide="check-circle"></i>
                        {{ __('public.stf_import_approve_btn') }} {{ number_format($job->valid_rows) }} {{ __('public.stf_import_records_suffix') }}
                    </button>
                </form>
            </div>

        @elseif($job->status === 'validation_failed')
            <div class="oda-gb-alert oda-gb-alert--bad">
                <i data-lucide="triangle-alert"></i>
                <div>
                    <strong>{{ __('public.stf_import_all_failed_title') }}</strong>
                    <p>{{ __('public.stf_import_all_failed_desc') }}</p>
                </div>
            </div>
            <div class="od-row">
                <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_edit_mapping') }}</a>
                <a href="{{ route('portals.admin.data_import.create') }}" class="od-btn od-btn--primary od-btn--sm">{{ __('public.stf_import_reupload') }}</a>
            </div>

        @else
            <div class="od-row">
                <a href="{{ route('portals.admin.data_import.index') }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-left"></i> {{ __('public.stf_import_back_history') }}</a>
            </div>
        @endif
    </div>
</section>
</div>
@endsection
