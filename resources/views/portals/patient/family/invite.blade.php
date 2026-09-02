@extends('layouts.portal')

@section('title', __('public.portal.invite_title', [], app()->getLocale()) ?: 'Invite Member')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.invite_breadcrumb', [], app()->getLocale()) ?: 'Invite Member')

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="mail"></i> {{ __('public.portal.panel_invite_patient', [], $l) ?: 'Invite an Existing Patient' }}</h2>
    </div>
    <div class="panel-body">
        <p class="text-sm text-muted mb-6">
            {{ __('public.portal.invite_info', [], $l) ?: 'Enter the Health ID or email of a patient who already has an OpesCare record. They will receive an invite link to approve the connection.' }}
        </p>
        @if($errors->any())
        <div class="alert alert-danger mb-4">
            <i data-lucide="alert-circle"></i>
            <ul class="alert-list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif
        <form method="POST" action="{{ route('portals.patient.family.invite.send') }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.portal.field_health_id_email', [], $l) ?: 'Health ID or Email' }}</label>
                <input type="text" name="health_id_or_email" value="{{ old('health_id_or_email') }}" required class="form-control" placeholder="CM-HID-XXXX-XXXX-XXXX or email@example.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_relationship', [], $l) ?: 'Relationship' }}</label>
                    <select name="relationship" required class="form-control">
                        <option value="">{{ __('public.portal.opt_select', [], $l) ?: '— select —' }}</option>
                        @foreach(['parent','grandparent','spouse','sibling','caregiver','legal_guardian','other'] as $r)
                        <option value="{{ $r }}" {{ old('relationship') === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_access_level', [], $l) ?: 'Access Level' }}</label>
                    <select name="access_level" required class="form-control">
                        <option value="read_only" {{ old('access_level','read_only') === 'read_only' ? 'selected' : '' }}>{{ __('public.portal.opt_read_only', [], $l) ?: 'Read Only' }}</option>
                        <option value="full" {{ old('access_level') === 'full' ? 'selected' : '' }}>{{ __('public.portal.opt_full_access', [], $l) ?: 'Full Access' }}</option>
                    </select>
                </div>
            </div>
            <div class="row-actions mt-6">
                <button type="submit" class="btn btn-primary">{{ __('public.portal.btn_send_invite', [], $l) ?: 'Send Invite' }}</button>
                <a href="{{ route('portals.patient.family') }}" class="btn btn-secondary">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
