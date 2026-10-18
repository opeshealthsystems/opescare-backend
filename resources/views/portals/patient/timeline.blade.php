@extends('layouts.portal')

@section('title', __('family.tl_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.tl_title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $viewing = request()->attributes->get('viewing_patient');
    $icons   = ['visit' => 'stethoscope', 'lab_result' => 'droplet', 'prescription' => 'pill'];
@endphp

@section('content')
<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.tl_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.tl_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $events->isEmpty())
    <div class="f-card ods-99e8a36" data-section="timeline-empty">
        <div class="pf-qa__icon ods-4ef0061">
            <i class="ods-f70f93d" data-lucide="activity" aria-hidden="true"></i>
        </div>
        <p class="f-muted ods-1386d50">{{ __('family.tl_empty') }}</p>
    </div>
    @else
    <div class="pf-stack" data-section="timeline">
        @foreach($events as $ev)
        <div class="f-card">
            <div class="pf-appt">
                <div class="pf-appt__date" aria-hidden="true">
                    <div class="d">{{ $ev->occurred_at?->format('d') ?? '—' }}</div>
                    <div class="m">{{ $ev->occurred_at?->isoFormat('MMM YY') ?? '' }}</div>
                </div>
                <div class="pf-appt__body">
                    <p class="pf-appt__title">
                        @include('partials.finder.icon', ['name' => $icons[$ev->event_type] ?? 'clock', 'size' => 16])
                        {{ __('family.tl_' . $ev->event_type) }}
                        <span class="f-badge pf-badge--muted">
                            @if($ev->event_type === 'prescription')
                                {{ __('family.tl_items', ['count' => (int) $ev->summary]) }}
                            @else
                                {{ $ev->summary ?? '—' }}
                            @endif
                        </span>
                    </p>
                    <div class="pf-appt__meta">
                        @if($ev->facility_name)
                        <span>
                            @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                            {{ $ev->facility_name }}
                        </span>
                        @endif
                        @if($ev->occurred_at)
                        <span>
                            @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                            {{ $ev->occurred_at->translatedFormat('d M Y') }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
