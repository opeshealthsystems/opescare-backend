@extends('layouts.portal')

@section('title', __('public.portal.invite_title', [], app()->getLocale()) ?: 'Invite Member')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.invite_breadcrumb', [], app()->getLocale()) ?: 'Invite Member')

@section('head')
    @include('portals.patient._finder_head')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.panel_invite_patient', [], $l) ?: 'Invite an Existing Patient' }}</h1>
            <p class="pf-sub">{{ __('public.portal.invite_info', [], $l) ?: 'Enter the Health ID or email of a patient who already has an OpesCare record. They will receive an invite link to approve the connection.' }}</p>
        </div>
        <a href="{{ route('portals.patient.family') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.family_breadcrumb', [], $l) ?: 'My Family' }}
        </a>
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
            <label class="f-label" for="health_id_or_email">{{ __('public.portal.field_health_id_email', [], $l) ?: 'Health ID or Email' }}</label>
            <input type="text" id="health_id_or_email" name="health_id_or_email" value="{{ old('health_id_or_email') }}" required class="f-input"
                   placeholder="CM-HID-XXXX-XXXX-XXXX or email@example.com">
        </div>
        <div class="f-field">
            <label class="f-label" for="relationship">{{ __('public.portal.field_relationship', [], $l) ?: 'Relationship' }}</label>
            <select id="relationship" name="relationship" required class="f-input">
                <option value="">{{ __('public.portal.opt_select', [], $l) ?: '— select —' }}</option>
                @foreach(['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other'] as $r)
                <option value="{{ $r }}" {{ old('relationship') === $r ? 'selected' : '' }}>@enum($r)</option>
                @endforeach
            </select>
        </div>
        <div class="f-field">
            <label class="f-label" for="access_level">{{ __('public.portal.field_access_level', [], $l) ?: 'Access Level' }}</label>
            <select id="access_level" name="access_level" required class="f-input">
                <option value="read_only" {{ old('access_level','read_only') === 'read_only' ? 'selected' : '' }}>{{ __('public.portal.opt_read_only', [], $l) ?: 'Read Only' }}</option>
                <option value="full" {{ old('access_level') === 'full' ? 'selected' : '' }}>{{ __('public.portal.opt_full_access', [], $l) ?: 'Full Access' }}</option>
            </select>
        </div>
        <div class="pf-actions" style="margin-top:8px;">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                {{ __('public.portal.btn_send_invite', [], $l) ?: 'Send Invite' }}
                @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
            </button>
            <a href="{{ route('portals.patient.family') }}" class="f-btn">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
        </div>
    </form>
</div>
@endsection
