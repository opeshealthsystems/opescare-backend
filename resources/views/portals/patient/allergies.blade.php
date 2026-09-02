@extends('layouts.portal')

@section('title', __('public.portal.allergies_title', [], app()->getLocale()) ?: 'My Allergies')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.allergies_breadcrumb', [], app()->getLocale()) ?: 'Allergies')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.allergies_title', [], $l) ?: 'My Allergies' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.allergies_subtitle', [], $l) ?: 'All known allergies and adverse reactions on your health record.' }}</p>
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
@elseif($allergies->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
        <h3>{{ __('public.portal.no_allergies_title', [], $l) ?: 'No Allergies on Record' }}</h3>
        <p>{{ __('public.portal.no_allergies_desc', [], $l) ?: 'No known allergies have been recorded for your profile. If you have allergies, please inform your healthcare provider.' }}</p>
    </div>
</div>
@else

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="zap"></i> {{ __('public.portal.allergy_list_panel', [], $l) ?: 'Allergy List' }} ({{ $allergies->count() }})</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.allergy_list_panel', [], $l) ?: 'Allergy list' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_substance', [], $l) ?: 'Substance' }}</th>
                    <th>{{ __('public.portal.col_severity', [], $l) ?: 'Severity' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allergies as $allergy)
                @php
                    $sev = strtolower($allergy->severity ?? '');
                    $sevCls = match($sev) {
                        'life-threatening', 'severe', 'high' => 'badge-danger',
                        'moderate', 'medium'                 => 'badge-warning',
                        default                              => 'badge-neutral',
                    };
                    $isCritical = in_array($sev, ['life-threatening', 'severe', 'high']);
                @endphp
                <tr>
                    <td data-label="{{ __('public.portal.col_substance', [], $l) ?: 'Substance' }}">
                        <span class="cell-with-icon">
                            @if($isCritical)
                                <i data-lucide="alert-triangle"></i>
                            @endif
                            <span class="td-strong">{{ $allergy->substance }}</span>
                        </span>
                    </td>
                    <td data-label="{{ __('public.portal.col_severity', [], $l) ?: 'Severity' }}">
                        <span class="badge {{ $sevCls }}">@enum($allergy->severity ?? 'unknown', 'severity')</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge {{ $allergy->status === 'active' ? 'badge-success' : 'badge-neutral' }}">@enum($allergy->status ?? 'active')</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}">
                        <span class="td-muted">{{ $allergy->created_at?->format('d M Y') ?? '—' }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-warning mt-6">
    <i data-lucide="info"></i>
    <div>{{ __('public.portal.allergy_readonly_hint', [], $l) ?: 'Allergy records are maintained by your healthcare providers. To add or update an allergy, please contact the facility that manages your record.' }}</div>
</div>

@endif

@endsection
