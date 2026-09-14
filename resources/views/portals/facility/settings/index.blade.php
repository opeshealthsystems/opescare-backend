@extends('layouts.portal')
@section('title', $title)
@section('breadcrumb_home', __('facility_settings.breadcrumb_home'))
@section('breadcrumb_home_url', $homeUrl)
@section('breadcrumb_section', $title)

{{--
    "My facility" — the shared settings page for every non-pharmacy facility
    (FacilitySettingsController). The sections are the partials the pharmacy
    page also renders; only the namespace, the routes and the wording differ.
--}}

@section('head')
@include('portals.facility.settings._styles')
@endsection

@section('content')

<div class="page-head">
    <h2 class="ps-title-icon"><i data-lucide="{{ $typeIcon }}"></i> {{ $title }}</h2>
    <p class="page-subtitle">{{ __('facility_settings.subtitle') }}</p>
</div>

@include('portals.facility.settings._status', [
    'ns'        => 'facility_settings',
    'typeLabel' => __('finder_directory.types.' . $type) !== 'finder_directory.types.' . $type
        ? __('finder_directory.types.' . $type)
        : __('finder_directory.types.other'),
])

@include('portals.facility.settings._identity', [
    'ns'          => 'facility_settings',
    'action'      => route('portals.facility.settings.identity'),
    'sectionIcon' => $typeIcon,
])

@include('portals.facility.settings._photo', [
    'ns'     => 'facility_settings',
    'action' => route('portals.facility.settings.photo'),
])

@include('portals.facility.settings._location', [
    'ns'       => 'facility_settings',
    'action'   => route('portals.facility.settings.location'),
    'mapId'    => 'facility-settings-map',
    'pickType' => $type,
])

@include('portals.facility.settings._hours', [
    'ns'     => 'facility_settings',
    'action' => route('portals.facility.settings.hours'),
])

@endsection
