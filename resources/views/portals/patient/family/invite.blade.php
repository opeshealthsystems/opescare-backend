@extends('layouts.portal')

@section('title', __('family.dash_invite_adult') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.dash_invite_adult'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@php
    $l             = app()->getLocale();
    $relationships = $relationships ?? \App\Http\Controllers\MedicalId\FamilyController::RELATIONSHIPS;
    $majorityAge   = $majorityAge ?? 18;
@endphp

@section('content')
<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.dash_invite_adult') }}</h1>
            <p class="pf-sub">{{ __('family.invite_how_it_works') }}</p>
        </div>
        <a href="{{ route('portals.patient.family') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.family_breadcrumb', [], $l) }}
        </a>
    </div>

    <div class="pf-alert" data-section="invite-note">
        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
        <p class="f-small">{{ __('family.invite_needs_account') }}
            <a href="{{ route('portals.patient.family.add') }}">{{ __('family.dash_add_child') }}</a></p>
    </div>

    @if($errors->any())
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <div>@foreach($errors->all() as $e)<p class="f-small" style="margin:0;">{{ $e }}</p>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('portals.patient.family.invite.send') }}" class="f-card">
        @csrf
        <div class="f-field">
            <label class="f-label" for="health_id_or_email">{{ __('family.invite_contact_label') }}</label>
            <input type="text" id="health_id_or_email" name="health_id_or_email" value="{{ old('health_id_or_email') }}" required class="f-input"
                   autocomplete="off" spellcheck="false" placeholder="{{ __('family.invite_contact_placeholder') }}">
            <p class="f-hint">{{ __('family.invite_contact_hint') }}</p>
        </div>
        <div class="f-field">
            <label class="f-label" for="relationship">{{ __('public.portal.field_relationship', [], $l) }}</label>
            <select id="relationship" name="relationship" required class="f-input">
                <option value="">{{ __('public.portal.opt_select', [], $l) }}</option>
                @foreach($relationships as $r)
                <option value="{{ $r }}" {{ old('relationship') === $r ? 'selected' : '' }}>{{ __('family.relationship.' . $r) }}</option>
                @endforeach
            </select>
            <p class="f-hint">{{ __('family.relationship_hint') }}</p>
        </div>
        <div class="f-field">
            <label class="f-label" for="access_level">{{ __('public.portal.field_access_level', [], $l) }}</label>
            <select id="access_level" name="access_level" required class="f-input">
                <option value="read_only" {{ old('access_level', 'read_only') === 'read_only' ? 'selected' : '' }}>{{ __('family.access.read_only') }}</option>
                <option value="full" {{ old('access_level') === 'full' ? 'selected' : '' }}>{{ __('family.access.full') }}</option>
            </select>
            <p class="f-hint">{{ __('family.access_read_only_explained') }}</p>
            <p class="f-hint">{{ __('family.access_full_explained') }}</p>
        </div>
        <div class="pf-actions" style="margin-top:8px;">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                {{ __('public.portal.btn_send_invite', [], $l) }}
                @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
            </button>
            <a href="{{ route('portals.patient.family') }}" class="f-btn">{{ __('public.portal.btn_cancel', [], $l) }}</a>
        </div>
    </form>
</div>
@endsection
