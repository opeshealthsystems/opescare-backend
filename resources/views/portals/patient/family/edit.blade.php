@extends('layouts.portal')

@section('title', __('public.portal.edit_family_link_title', [], app()->getLocale()) ?: 'Edit Family Link')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.edit_family_link_breadcrumb', [], app()->getLocale()) ?: 'Edit Family Link')

@section('head')
    @include('portals.patient._finder_head')
@endsection

@php
    $l             = app()->getLocale();
    $relationships = $relationships ?? \App\Http\Controllers\MedicalId\FamilyController::RELATIONSHIPS;
    $isLegacyLevel = $isLegacyLevel ?? !in_array($link->access_level, ['full', 'read_only'], true);
    $dep           = $link->dependentPatient;
    // A legacy value is shown as the level it actually grants — read-only —
    // so saving the form can never quietly promote it to full.
    $currentLevel  = $link->access_level === 'full' ? 'full' : 'read_only';
@endphp

@section('content')
<div class="pf pf--form">

    <div class="pf-head">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="pf-avatar">{{ strtoupper(substr($dep?->first_name ?? 'D', 0, 1)) }}</div>
            <h1>{{ $dep ? trim(($dep->first_name ?? '') . ' ' . ($dep->last_name ?? '')) : (__('public.portal.unknown_patient', [], $l) ?: 'Unknown patient') }}</h1>
        </div>
        <a href="{{ route('portals.patient.family') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.family_breadcrumb', [], $l) ?: 'My Family' }}
        </a>
    </div>

    @if($isLegacyLevel)
    <div class="pf-alert pf-alert--warn" data-section="legacy-access-note">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <p class="f-small">{{ __('family.access_legacy_note', ['value' => $link->access_level]) }}</p>
    </div>
    @endif

    <form method="POST" action="{{ route('portals.patient.family.update', $link->id) }}">
        @csrf
        <div class="f-card" style="margin-bottom:12px;">
            <div class="f-field">
                <label class="f-label" for="relationship">{{ __('public.portal.field_relationship', [], $l) ?: 'Relationship' }}</label>
                <select id="relationship" name="relationship" required class="f-input">
                    @if(!in_array($link->relationship, $relationships, true))
                    <option value="{{ $link->relationship }}" selected>{{ ucfirst(str_replace('_', ' ', $link->relationship)) }}</option>
                    @endif
                    @foreach($relationships as $r)
                    <option value="{{ $r }}" {{ $link->relationship === $r ? 'selected' : '' }}>{{ __('family.relationship.' . $r) }}</option>
                    @endforeach
                </select>
                <p class="f-hint">{{ __('family.relationship_hint') }}</p>
            </div>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="access_level">{{ __('public.portal.field_access_level', [], $l) ?: 'Access Level' }}</label>
                <select id="access_level" name="access_level" required class="f-input">
                    <option value="full" {{ $currentLevel === 'full' ? 'selected' : '' }}>{{ __('family.access.full') }}</option>
                    <option value="read_only" {{ $currentLevel === 'read_only' ? 'selected' : '' }}>{{ __('family.access.read_only') }}</option>
                </select>
                <p class="f-hint">{{ __('family.access_full_explained') }}</p>
                <p class="f-hint">{{ __('family.access_read_only_explained') }}</p>
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'filter', 'size' => 18])
                {{ __('public.portal.notif_pref_heading', [], $l) ?: 'Notification Preferences' }}
            </h2>
            <div class="pf-table-wrap">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.portal.col_event', [], $l) ?: 'Event' }}</th>
                            <th style="text-align:center;">{{ __('public.portal.col_in_portal', [], $l) ?: 'In-Portal' }}</th>
                            <th style="text-align:center;">{{ __('public.portal.col_email', [], $l) ?: 'Email' }}</th>
                            <th style="text-align:center;">{{ __('public.portal.col_sms', [], $l) ?: 'SMS' }}</th>
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
                        <td>{{ $label }}</td>
                        @foreach(['portal','email','sms'] as $ch)
                        <td style="text-align:center;">
                            <input type="checkbox" name="notification_prefs[{{ $key }}][{{ $ch }}]" value="1"
                                aria-label="{{ $label }} — {{ ucfirst($ch) }}"
                                {{ $link->notificationPrefFor($key, $ch) ? 'checked' : '' }}>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pf-actions">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">{{ __('public.portal.btn_save_changes', [], $l) ?: 'Save Changes' }}</button>
            <a href="{{ route('portals.patient.family') }}" class="f-btn">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
        </div>
    </form>
</div>
@endsection
