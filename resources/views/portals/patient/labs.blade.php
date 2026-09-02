@extends('layouts.portal')

@section('title', __('public.portal.labs_title', [], app()->getLocale()) ?: 'My Lab Results')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.labs_breadcrumb', [], app()->getLocale()) ?: 'Lab Results')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.labs_title', [], $l) ?: 'My Lab Results' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.labs_subtitle', [], $l) ?: 'View your laboratory test results from all facilities.' }}</p>
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
@elseif($labs->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="flask-conical"></i></div>
        <h3>{{ __('public.portal.no_labs_title', [], $l) ?: 'No Lab Results' }}</h3>
        <p>{{ __('public.portal.no_labs_desc', [], $l) ?: 'You have no recorded lab results at this time.' }}</p>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('public.portal.panel_lab_results', [], $l) ?: 'Lab Results' }}</h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.panel_lab_results', [], $l) ?: 'Lab results' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_test', [], $l) ?: 'Test' }}</th>
                    <th>{{ __('public.portal.col_result', [], $l) ?: 'Result' }}</th>
                    <th>{{ __('public.portal.col_reference', [], $l) ?: 'Reference' }}</th>
                    <th>{{ __('public.portal.col_flag', [], $l) ?: 'Flag' }}</th>
                    <th>{{ __('public.portal.col_date', [], $l) ?: 'Date' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labs as $lab)
                <tr>
                    <td data-label="{{ __('public.portal.col_test', [], $l) ?: 'Test' }}"><span class="td-strong">{{ $lab->parameter_name }}</span></td>
                    <td data-label="{{ __('public.portal.col_result', [], $l) ?: 'Result' }}">{{ $lab->value }} {{ $lab->unit }}</td>
                    <td data-label="{{ __('public.portal.col_reference', [], $l) ?: 'Reference' }}"><span class="td-muted">{{ $lab->reference_range ?? '—' }}</span></td>
                    <td data-label="{{ __('public.portal.col_flag', [], $l) ?: 'Flag' }}">
                        @if($lab->isAbnormal())
                            <span class="badge badge-danger">{{ $lab->flagLabel() }}</span>
                        @else
                            <span class="badge badge-success">{{ __('public.portal.flag_normal', [], $l) ?: 'Normal' }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.portal.col_date', [], $l) ?: 'Date' }}"><span class="td-muted">{{ $lab->resulted_at?->format('d M Y') ?? '—' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($labs, 'links') && $labs->hasPages())
    <div class="panel-body">
        {{ $labs->links() }}
    </div>
    @endif
</div>
@endif

@endsection
