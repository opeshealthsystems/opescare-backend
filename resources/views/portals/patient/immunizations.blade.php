@extends('layouts.portal')

@section('title', __('public.portal.immunizations_title', [], app()->getLocale()) ?: 'My Immunizations')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.immunizations_breadcrumb', [], app()->getLocale()) ?: 'Immunizations')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.immunizations_title', [], $l) ?: 'My Immunizations' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.immunizations_subtitle', [], $l) ?: 'Vaccination history recorded by your healthcare providers.' }}</p>
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
@elseif($immunizations->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="syringe"></i></div>
        <h3>{{ __('public.portal.no_immunizations_title', [], $l) ?: 'No Immunizations on Record' }}</h3>
        <p>{{ __('public.portal.no_immunizations_desc', [], $l) ?: 'No vaccinations have been recorded for your profile. Ask your healthcare provider to record your vaccination history.' }}</p>
    </div>
</div>
@else

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="syringe"></i> {{ __('public.portal.panel_vaccination_history', [], $l) ?: 'Vaccination History' }} ({{ $immunizations->count() }})</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.panel_vaccination_history', [], $l) ?: 'Vaccination history' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_vaccine', [], $l) ?: 'Vaccine' }}</th>
                    <th>{{ __('public.portal.col_lot_number', [], $l) ?: 'Lot Number' }}</th>
                    <th>{{ __('public.portal.col_dose', [], $l) ?: 'Dose' }}</th>
                    <th>{{ __('public.portal.col_administered', [], $l) ?: 'Administered' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($immunizations as $imm)
                <tr>
                    <td data-label="{{ __('public.portal.col_vaccine', [], $l) ?: 'Vaccine' }}"><span class="td-strong">{{ $imm->vaccine_name }}</span></td>
                    <td data-label="{{ __('public.portal.col_lot_number', [], $l) ?: 'Lot Number' }}"><span class="td-mono">{{ $imm->lot_number ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_dose', [], $l) ?: 'Dose' }}"><span class="td-muted">{{ $imm->dose_number ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_administered', [], $l) ?: 'Administered' }}"><span class="td-muted">{{ $imm->administered_at?->format('d M Y') ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge badge-success">@enum($imm->status ?? 'completed')</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection
