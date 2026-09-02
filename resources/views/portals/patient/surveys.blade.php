@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_survey_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_survey_breadcrumb', [], $l))

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_survey_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.pat_survey_subtitle', [], $l) }}</p>
    </div>
</div>

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($surveys->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="clipboard-check"></i></div>
        <h3>{{ __('public.pat_survey_empty_title', [], $l) }}</h3>
        <p>{{ __('public.pat_survey_empty_desc', [], $l) }}</p>
    </div>
</div>
@else
<div class="alert alert-info mb-4">
    <i data-lucide="info"></i>
    <div>{{ __('public.pat_survey_status_note', [], $l) }}</div>
</div>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-check"></i> {{ __('public.pat_survey_title', [], $l) }}</h2>
        <span class="badge badge-primary">{{ $surveys->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.pat_survey_col_name', [], $l) }}</th>
                    <th>{{ __('public.pat_survey_col_facility', [], $l) }}</th>
                    <th>{{ __('public.pat_survey_col_sent', [], $l) }}</th>
                    <th>{{ __('public.pat_survey_col_status', [], $l) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($surveys as $survey)
                @php
                    $statusBadge = match($survey->status) {
                        'completed' => 'badge-success',
                        'expired'   => 'badge-danger',
                        'sent'      => 'badge-warning',
                        default     => 'badge-primary',
                    };
                    $name = ucwords(str_replace(['_', '-'], ' ', (string) ($survey->template_key ?? '—')));
                @endphp
                <tr>
                    <td data-label="{{ __('public.pat_survey_col_name', [], $l) }}"><span class="td-strong">{{ $name }}</span></td>
                    <td data-label="{{ __('public.pat_survey_col_facility', [], $l) }}"><span class="td-muted">{{ $survey->facility?->name ?? '—' }}</span></td>
                    <td data-label="{{ __('public.pat_survey_col_sent', [], $l) }}"><span class="td-muted">{{ $survey->sent_at?->isoFormat('LL') ?? '—' }}</span></td>
                    <td data-label="{{ __('public.pat_survey_col_status', [], $l) }}">
                        <span class="badge {{ $statusBadge }}">@enum($survey->status ?? 'sent')</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
