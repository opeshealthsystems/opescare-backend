@extends('layouts.portal')

@section('title', __('public.portal.add_dependent_title', [], app()->getLocale()) ?: 'Add Dependent')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.add_dependent_breadcrumb', [], app()->getLocale()) ?: 'Add Dependent')

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="user-plus"></i> {{ __('public.portal.panel_register_dependent', [], $l) ?: 'Register a Dependent' }}</h2>
    </div>
    <div class="panel-body">
        <p class="text-sm text-muted mb-6">
            {{ __('public.portal.add_dependent_info', [], $l) ?: 'This creates a new patient record for your dependent. No login account is created — you manage their records.' }}
        </p>
        @if($errors->any())
        <div class="alert alert-danger mb-4">
            <i data-lucide="alert-circle"></i>
            <ul class="alert-list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif
        <form method="POST" action="{{ route('portals.patient.family.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_first_name', [], $l) ?: 'First Name' }}</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_last_name', [], $l) ?: 'Last Name' }}</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_dob', [], $l) ?: 'Date of Birth' }}</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_sex', [], $l) ?: 'Sex' }}</label>
                    <select name="sex" required class="form-control">
                        <option value="">{{ __('public.portal.opt_select', [], $l) ?: '— select —' }}</option>
                        <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>{{ __('public.portal.opt_male', [], $l) ?: 'Male' }}</option>
                        <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>{{ __('public.portal.opt_female', [], $l) ?: 'Female' }}</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.portal.field_relationship', [], $l) ?: 'Your Relationship' }}</label>
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
                        <option value="full" {{ old('access_level','full') === 'full' ? 'selected' : '' }}>{{ __('public.portal.opt_full_access', [], $l) ?: 'Full Access' }}</option>
                        <option value="read_only" {{ old('access_level') === 'read_only' ? 'selected' : '' }}>{{ __('public.portal.opt_read_only', [], $l) ?: 'Read Only' }}</option>
                    </select>
                </div>
            </div>
            <div class="row-actions mt-6">
                <button type="submit" class="btn btn-primary">{{ __('public.portal.btn_register_dependent', [], $l) ?: 'Register Dependent' }}</button>
                <a href="{{ route('portals.patient.family') }}" class="btn btn-secondary">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
