@extends('layouts.portal')

@section('title', __('public.portal.edit_family_link_title', [], app()->getLocale()) ?: 'Edit Family Link')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.edit_family_link_breadcrumb', [], app()->getLocale()) ?: 'Edit Family Link')

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="settings"></i>
            {{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}
        </h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.patient.family.update', $link->id) }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_relationship', [], $l) ?: 'Relationship' }}</label>
                    <select name="relationship" required class="form-control">
                        @foreach(['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other'] as $r)
                        <option value="{{ $r }}" {{ $link->relationship === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_access_level', [], $l) ?: 'Access Level' }}</label>
                    <select name="access_level" required class="form-control">
                        <option value="full" {{ $link->access_level === 'full' ? 'selected' : '' }}>{{ __('public.portal.opt_full_access', [], $l) ?: 'Full Access' }}</option>
                        <option value="read_only" {{ $link->access_level === 'read_only' ? 'selected' : '' }}>{{ __('public.portal.opt_read_only', [], $l) ?: 'Read Only' }}</option>
                    </select>
                </div>
            </div>
            <h3 class="panel-title mb-3"><i data-lucide="bell"></i> {{ __('public.portal.notif_pref_heading', [], $l) ?: 'Notification Preferences' }}</h3>
            <div class="table-wrapper mb-6">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.portal.col_event', [], $l) ?: 'Event' }}</th>
                            <th>{{ __('public.portal.col_in_portal', [], $l) ?: 'In-Portal' }}</th>
                            <th>{{ __('public.portal.col_email', [], $l) ?: 'Email' }}</th>
                            <th>{{ __('public.portal.col_sms', [], $l) ?: 'SMS' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach([
                        'lab_result'      => __('public.portal.event_lab_result', [], $l) ?: 'New Lab Result',
                        'appointment'     => __('public.portal.event_appointment', [], $l) ?: 'Appointment',
                        'consent_request' => __('public.portal.event_consent_request', [], $l) ?: 'Consent Request',
                        'age_transition'  => __('public.portal.event_age_transition', [], $l) ?: 'Age Transition Alert',
                    ] as $key => $label)
                    <tr>
                        <td data-label="{{ __('public.portal.col_event', [], $l) ?: 'Event' }}">{{ $label }}</td>
                        @foreach(['portal','email','sms'] as $ch)
                        <td data-label="{{ ucfirst($ch) }}">
                            <input type="checkbox" name="notification_prefs[{{ $key }}][{{ $ch }}]" value="1"
                                {{ $link->notificationPrefFor($key, $ch) ? 'checked' : '' }}>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row-actions">
                <button type="submit" class="btn btn-primary">{{ __('public.portal.btn_save_changes', [], $l) ?: 'Save Changes' }}</button>
                <a href="{{ route('portals.patient.family') }}" class="btn btn-secondary">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
