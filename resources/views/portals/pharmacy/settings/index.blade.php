@extends('layouts.portal')
@section('title', __('pharmacy_settings.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))

{{--
    "My pharmacy" — the pharmacy's settings page (PharmacySettingsController).
    Every section is a partial under portals/facility/settings/, shared with
    the page every other facility type uses (/facility/settings), so the two
    stay in step; only the namespace, the routes and the wording differ.
--}}

@section('head')
@include('portals.facility.settings._styles')
@include('portals.pharmacy.stock_ops._styles')
@endsection

@section('content')

<div class="os-page os-settings">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_settings.title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_settings.subtitle') }}</p>
    </div>
</header>

{{-- The shared /facility/settings partials, wrapped (not restyled) so they sit
     on the desk canvas as flat cards; see od-stock.css .os-settings. --}}

@include('portals.facility.settings._status', [
    'ns'        => 'pharmacy_settings',
    'typeLabel' => __('finder_directory.types.' . $facility->type) !== 'finder_directory.types.' . $facility->type
        ? __('finder_directory.types.' . $facility->type)
        : __('finder_directory.types.other'),
])

@include('portals.facility.settings._identity', [
    'ns'          => 'pharmacy_settings',
    'action'      => route('portals.pharmacy.settings.identity'),
    'sectionIcon' => 'store',
])

@include('portals.facility.settings._photo', [
    'ns'     => 'pharmacy_settings',
    'action' => route('portals.pharmacy.settings.photo'),
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

</div>
@endsection
