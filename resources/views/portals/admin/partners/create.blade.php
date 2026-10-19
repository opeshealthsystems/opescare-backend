@extends('layouts.portal')
@section('title', __('partners.create_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('partners.create_title') }}</h1>
        <p class="od-page__sub">{{ __('partners.create_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.partners.index') }}" class="od-btn od-btn--ghost">
            <i data-lucide="arrow-left"></i> {{ __('partners.back_to_list') }}
        </a>
    </div>
</div>

@if($errors->any())
<div class="oda-gc-flash oda-gc-flash--warn">
    <i data-lucide="triangle-alert"></i>
    <div><ul class="oda-gc-errlist">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
</div>
@endif

<section class="od-card oda-gc-narrow">
    <div class="od-card__body">
<form method="POST" action="{{ route('portals.admin.partners.store') }}" class="oda-form">
    @csrf

    <label class="oda-label" for="name">
        <span class="oda-label__text">{{ __('partners.f_name') }}</span>
        <input type="text" id="name" name="name" class="od-input" maxlength="120" required value="{{ old('name') }}">
        <small class="oda-help">{{ __('partners.f_name_help') }}</small>
    </label>

    <label class="oda-label" for="contact_email">
        <span class="oda-label__text">{{ __('partners.f_contact_email') }}</span>
        <input type="email" id="contact_email" name="contact_email" class="od-input" required value="{{ old('contact_email') }}">
    </label>

    <label class="oda-label oda-form__full" for="description">
        <span class="oda-label__text">{{ __('partners.f_description') }}</span>
        <textarea id="description" name="description" class="oda-textarea" maxlength="500" rows="2">{{ old('description') }}</textarea>
    </label>

    <label class="oda-label" for="environment">
        <span class="oda-label__text">{{ __('partners.f_environment') }}</span>
        <select id="environment" name="environment" class="od-select" required>
            <option value="sandbox" @selected(old('environment', 'sandbox') === 'sandbox')>{{ __('partners.env_sandbox') }}</option>
            <option value="production" @selected(old('environment') === 'production')>{{ __('partners.env_production') }}</option>
        </select>
    </label>

    {{-- The organisation this partner speaks for. The submitted id is used
         only to LOOK UP a facility row; what gets stored is the id read back
         off that row, and it never decides what this administrator may see. --}}
    <label class="oda-label" for="organisation_id">
        <span class="oda-label__text">{{ __('partners.f_organisation') }}</span>
        <select id="organisation_id" name="organisation_id" class="od-select">
            <option value="">{{ __('partners.f_organisation_none') }}</option>
            @foreach($facilities as $facility)
            <option value="{{ $facility->id }}" @selected(old('organisation_id') === $facility->id)>{{ $facility->name }}</option>
            @endforeach
        </select>
        <small class="oda-help">{{ __('partners.f_organisation_help') }}</small>
    </label>

    {{-- Nothing is pre-checked. A client granted a scope nobody deliberately
         ticked is a client granted a scope nobody decided on. --}}
    <fieldset class="oda-form__full oda-gc-fieldset">
        <legend class="oda-label__text">{{ __('partners.f_scopes') }}</legend>
        <small class="oda-help">{{ __('partners.f_scopes_help') }}</small>
        <div class="oda-gc-checks">
            @foreach($scopes as $key => $label)
            <label class="oda-check">
                <input type="checkbox" name="scopes[]" value="{{ $key }}"
                       @checked(in_array($key, (array) old('scopes', []), true))>
                <span><code class="oda-mono">{{ $key }}</code> — {{ $label }}</span>
            </label>
            @endforeach
        </div>
    </fieldset>

    <div class="oda-form__actions">
        <button type="submit" class="od-btn od-btn--primary">
            <i data-lucide="key-round"></i> {{ __('partners.f_submit') }}
        </button>
    </div>
</form>
    </div>
</section>

</div>
@endsection
