@extends('layouts.portal')
@section('title', __('partners.create_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('partners.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('partners.create_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('partners.create_title') }}</h2>
    <a href="{{ route('portals.admin.partners.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('partners.back_to_list') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('partners.create_subtitle') }}</p>

@if($errors->any())
<div class="alert alert-warning mb-6">
    <i data-lucide="triangle-alert"></i>
    <div><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
</div>
@endif

<form method="POST" action="{{ route('portals.admin.partners.store') }}" class="form-stack">
    @csrf

    <div class="form-group">
        <label class="form-label" for="name">{{ __('partners.f_name') }}</label>
        <input type="text" id="name" name="name" class="form-control" maxlength="120" required value="{{ old('name') }}">
        <small class="form-hint">{{ __('partners.f_name_help') }}</small>
    </div>

    <div class="form-group">
        <label class="form-label" for="description">{{ __('partners.f_description') }}</label>
        <textarea id="description" name="description" class="form-control" maxlength="500" rows="2">{{ old('description') }}</textarea>
    </div>

    <div class="form-group">
        <label class="form-label" for="contact_email">{{ __('partners.f_contact_email') }}</label>
        <input type="email" id="contact_email" name="contact_email" class="form-control" required value="{{ old('contact_email') }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="environment">{{ __('partners.f_environment') }}</label>
        <select id="environment" name="environment" class="form-control" required>
            <option value="sandbox" @selected(old('environment', 'sandbox') === 'sandbox')>{{ __('partners.env_sandbox') }}</option>
            <option value="production" @selected(old('environment') === 'production')>{{ __('partners.env_production') }}</option>
        </select>
    </div>

    {{-- The organisation this partner speaks for. The submitted id is used
         only to LOOK UP a facility row; what gets stored is the id read back
         off that row, and it never decides what this administrator may see. --}}
    <div class="form-group">
        <label class="form-label" for="organisation_id">{{ __('partners.f_organisation') }}</label>
        <select id="organisation_id" name="organisation_id" class="form-control">
            <option value="">{{ __('partners.f_organisation_none') }}</option>
            @foreach($facilities as $facility)
            <option value="{{ $facility->id }}" @selected(old('organisation_id') === $facility->id)>{{ $facility->name }}</option>
            @endforeach
        </select>
        <small class="form-hint">{{ __('partners.f_organisation_help') }}</small>
    </div>

    {{-- Nothing is pre-checked. A client granted a scope nobody deliberately
         ticked is a client granted a scope nobody decided on. --}}
    <div class="form-group">
        <label class="form-label">{{ __('partners.f_scopes') }}</label>
        <small class="form-hint">{{ __('partners.f_scopes_help') }}</small>
        <div class="checkbox-grid mt-2">
            @foreach($scopes as $key => $label)
            <label class="checkbox">
                <input type="checkbox" name="scopes[]" value="{{ $key }}"
                       @checked(in_array($key, (array) old('scopes', []), true))>
                <span><code>{{ $key }}</code> — {{ $label }}</span>
            </label>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i data-lucide="key-round"></i> {{ __('partners.f_submit') }}
    </button>
</form>
@endsection
