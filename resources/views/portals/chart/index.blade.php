@extends('layouts.portal')

@section('title', __('chart.lookup_title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.index'))
@section('breadcrumb_section', __('chart.lookup_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('chart.lookup_title') }}</h1>
        <p class="page-subtitle">{{ __('chart.lookup_subtitle') }}</p>
    </div>
</div>

{{-- Exact Health ID only. There is no name search, no partial match and no
     list on this desk: a professional must not be able to enumerate this
     platform's patients. A Health ID nobody holds and one this clinician has
     no basis to open produce the same message, so the form never confirms
     that a record exists. --}}
<div class="panel form-panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="search"></i> {{ __('chart.health_id') }}</h2>
    </div>
    <div class="panel-body">

        @if($notFound)
        <div class="alert alert-danger mb-4" role="alert">
            <i data-lucide="user-x"></i>
            <div>{{ __('chart.not_found') }}</div>
        </div>
        @endif

        <form method="GET" action="{{ route('portals.chart.index') }}">
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="chart-health-id">{{ __('chart.health_id') }}</label>
                <input type="text" id="chart-health-id" name="health_id" class="form-control"
                       value="{{ $healthId }}" required autocomplete="off" autofocus maxlength="64">
                <div class="form-hint">{{ __('chart.health_id_hint') }}</div>
            </div>
            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="folder-open"></i> {{ __('chart.lookup') }}
                </button>
            </div>
        </form>

        <p class="form-hint mt-4">
            <a href="{{ route('portals.staff') }}">{{ __('chart.landing_hint') }}</a>
        </p>
    </div>
</div>

@endsection
