@extends('layouts.portal')
@section('title', __('pharmacy_settings.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_settings.title'))

{{--
    "My pharmacy" — the pharmacy's settings page (PharmacySettingsController).
    Every section is a partial under portals/facility/settings/, shared with
    the page every other facility type uses (/facility/settings), so the two
    stay in step; only the namespace, the routes and the wording differ.
--}}

@section('head')
@include('portals.facility.settings._styles')
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_settings.title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_settings.subtitle') }}</p>
</div>

@include('portals.facility.settings._status', [
    'ns'        => 'pharmacy_settings',
    'typeLabel' => $facility->type,
])

@include('portals.facility.settings._identity', [
    'ns'          => 'pharmacy_settings',
    'action'      => route('portals.pharmacy.settings.identity'),
    'sectionIcon' => 'store',
])

@include('portals.facility.settings._location', [
    'ns'       => 'pharmacy_settings',
    'action'   => route('portals.pharmacy.settings.location'),
    'mapId'    => 'pharmacy-settings-map',
    'pickType' => 'pharmacy',
])

@include('portals.facility.settings._hours', [
    'ns'     => 'pharmacy_settings',
    'action' => route('portals.pharmacy.settings.hours'),
])

@endsection
