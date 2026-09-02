@extends('layouts.portal')

@section('title', __('public.portal.conditions_title', [], app()->getLocale()) ?: 'My Conditions')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.conditions_breadcrumb', [], app()->getLocale()) ?: 'Conditions')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.conditions_title', [], $l) ?: 'My Conditions' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.conditions_subtitle', [], $l) ?: 'Diagnoses and clinical conditions recorded by your healthcare providers.' }}</p>
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
@elseif($conditions->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
        <h3>{{ __('public.portal.no_conditions_title', [], $l) ?: 'No Conditions on Record' }}</h3>
        <p>{{ __('public.portal.no_conditions_desc', [], $l) ?: 'No diagnoses or clinical conditions have been recorded for your profile.' }}</p>
    </div>
</div>
@else

@php
    $active  = $conditions->whereIn('status', ['active', 'chronic']);
    $resolved = $conditions->where('status', 'resolved');
@endphp

@if($active->isNotEmpty())
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="stethoscope"></i> {{ __('public.portal.panel_active_conditions', [], $l) ?: 'Active & Chronic Conditions' }} ({{ $active->count() }})</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.panel_active_conditions', [], $l) ?: 'Active and chronic conditions' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_condition', [], $l) ?: 'Condition' }}</th>
                    <th>{{ __('public.portal.col_code', [], $l) ?: 'Code' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($active as $condition)
                <tr>
                    <td data-label="{{ __('public.portal.col_condition', [], $l) ?: 'Condition' }}"><span class="td-strong">{{ $condition->display_name ?? $condition->code ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_code', [], $l) ?: 'Code' }}"><span class="td-mono">{{ $condition->code ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge {{ $condition->status === 'chronic' ? 'badge-teal' : 'badge-primary' }}">@enum($condition->status)</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}"><span class="td-muted">{{ $condition->created_at?->format('d M Y') ?? '—' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($resolved->isNotEmpty())
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="check-circle"></i> {{ __('public.portal.panel_resolved_conditions', [], $l) ?: 'Resolved Conditions' }} ({{ $resolved->count() }})</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.panel_resolved_conditions', [], $l) ?: 'Resolved conditions' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_condition', [], $l) ?: 'Condition' }}</th>
                    <th>{{ __('public.portal.col_code', [], $l) ?: 'Code' }}</th>
                    <th>{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resolved as $condition)
                <tr>
                    <td data-label="{{ __('public.portal.col_condition', [], $l) ?: 'Condition' }}"><span class="td-muted">{{ $condition->display_name ?? $condition->code ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_code', [], $l) ?: 'Code' }}"><span class="td-mono">{{ $condition->code ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_recorded', [], $l) ?: 'Recorded' }}"><span class="td-muted">{{ $condition->created_at?->format('d M Y') ?? '—' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

@endsection
