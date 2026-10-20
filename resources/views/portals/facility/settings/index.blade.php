@extends('layouts.portal')
@section('title', $title)

{{--
    "My facility" — the shared settings page for every non-pharmacy facility
    (FacilitySettingsController). The sections are the partials the pharmacy
    page also renders; only the namespace, the routes and the wording differ.
--}}

@section('head')
@include('portals.facility.settings._styles')
@endsection

@section('content')

<div class="od-page">

<header class="od-page__head">
    <div class="odc-titlerow">
        <span class="od-avatar od-avatar--lg odc-ico-avatar od-tint--blue" aria-hidden="true"><i data-lucide="{{ $typeIcon }}"></i></span>
        <div>
            <h1 class="od-page__title">{{ $title }}</h1>
            <p class="od-page__sub">{{ __('facility_settings.subtitle') }}</p>
        </div>
    </div>
</header>

@include('portals.facility.settings._status', [
    'ns'        => 'facility_settings',
    'typeLabel' => \App\Support\FacilityTypeLabel::label($type),
])

<div class="od-grid od-grid--split">
    <div class="od-stack">
        @include('portals.facility.settings._identity', [
            'ns'          => 'facility_settings',
            'action'      => route('portals.facility.settings.identity'),
            'sectionIcon' => $typeIcon,
        ])

        @include('portals.facility.settings._location', [
            'ns'       => 'facility_settings',
            'action'   => route('portals.facility.settings.location'),
            'mapId'    => 'facility-settings-map',
            'pickType' => $type,
        ])
    </div>
    <div class="od-stack">
        @include('portals.facility.settings._photo', [
            'ns'     => 'facility_settings',
            'action' => route('portals.facility.settings.photo'),
        ])

        @include('portals.facility.settings._hours', [
            'ns'     => 'facility_settings',
            'action' => route('portals.facility.settings.hours'),
        ])
    </div>
</div>

@include('portals.facility.settings._services')

</div>
@endsection
