@extends('layouts.finder')

@section('title', __('solutions.request.title'))
@section('description', __('solutions.request.meta'))

@push('head')
<link rel="stylesheet" href="{{ asset('css/solutions.css') }}?v={{ @filemtime(public_path('css/solutions.css')) ?: '1' }}">
@endpush

@section('content')
<section class="sol-hero">
    <div class="f-container">
        <nav class="sol-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
            <a href="{{ route('public.solutions.index') }}">{{ __('solutions.detail.breadcrumb') }}</a>
            @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 14])
            <span aria-current="page">{{ __('solutions.request.heading') }}</span>
        </nav>

        @if(session('solutions_success'))
        <div class="sol-band sol-thanks" role="status">
            <span class="sol-hero__icon">@include('partials.finder.icon', ['name' => 'circle-check', 'size' => 30])</span>
            <h1 class="f-h1">{{ __('solutions.request.thanks_title') }}</h1>
            <p class="f-lead">{{ __('solutions.request.thanks_body') }}</p>
            <div class="sol-actions">
                <a class="f-btn f-btn--primary" href="{{ route('public.solutions.index') }}">@include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 18]){{ __('solutions.request.thanks_back') }}</a>
            </div>
        </div>
        @else
        <h1 class="f-h1">{{ __('solutions.request.heading') }}</h1>
        <p class="f-lead">{{ __('solutions.request.lead') }}</p>
        @endif
    </div>
</section>

@unless(session('solutions_success'))
<div class="f-container">
    <form method="POST" action="{{ route('public.solutions.request.store') }}" class="sol-form sol-band" novalidate>
        @csrf

        @if($errors->any())
        <div class="sol-errors" role="alert">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- Honeypot: hidden from people, filled by bots. --}}
        <div class="sol-hp" aria-hidden="true">
            <label for="sol-website">{{ __('solutions.request.hp_label') }}</label>
            <input type="text" id="sol-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="f-field">
            <label class="f-label" for="sol-system">{{ __('solutions.request.system') }}</label>
            <select class="f-input" id="sol-system" name="system">
                <option value="">{{ __('solutions.request.system_any') }}</option>
                @foreach($systems as $s => $sys)
                <option value="{{ $s }}" @selected(old('system', $preselectedSystem) === $s)>{{ __("solutions.systems.{$s}.name") }} — {{ __("solutions.systems.{$s}.tagline") }}</option>
                @endforeach
            </select>
            @error('system')<p class="sol-error">{{ $message }}</p>@enderror
        </div>

        <fieldset>
            <legend>{{ __('solutions.request.kind') }}</legend>
            @foreach(\App\Models\Lead::REQUEST_KINDS as $k)
            <label class="sol-radio"><input type="radio" name="request_kind" value="{{ $k }}" @checked(old('request_kind', $preselectedKind) === $k) required> {{ __("solutions.request.kind_{$k}") }}</label>
            @endforeach
            @error('request_kind')<p class="sol-error">{{ $message }}</p>@enderror
        </fieldset>

        <div class="sol-form-grid">
            <div class="f-field">
                <label class="f-label" for="sol-facility-name">{{ __('solutions.request.facility_name') }}</label>
                <input class="f-input" id="sol-facility-name" name="facility_name" value="{{ old('facility_name') }}" maxlength="255" required>
                @error('facility_name')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="sol-facility-type">{{ __('solutions.request.facility_type') }}</label>
                <select class="f-input" id="sol-facility-type" name="facility_type" required>
                    <option value=""></option>
                    @foreach($facilityTypes as $t)
                    <option value="{{ $t }}" @selected(old('facility_type') === $t)>{{ __("solutions.request.types.{$t}") }}</option>
                    @endforeach
                </select>
                @error('facility_type')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="sol-city">{{ __('solutions.request.city') }}</label>
                <input class="f-input" id="sol-city" name="city" value="{{ old('city') }}" maxlength="120" required>
                @error('city')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="sol-contact">{{ __('solutions.request.contact_name') }}</label>
                <input class="f-input" id="sol-contact" name="contact_name" value="{{ old('contact_name') }}" maxlength="255" autocomplete="name" required>
                @error('contact_name')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="sol-phone">{{ __('solutions.request.phone') }}</label>
                <input class="f-input" id="sol-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="50" autocomplete="tel" required>
                @error('phone')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="sol-email">{{ __('solutions.request.email') }}</label>
                <input class="f-input" id="sol-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                @error('email')<p class="sol-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <fieldset>
            <legend>{{ __('solutions.request.has_system') }}</legend>
            <label class="sol-radio"><input type="radio" name="has_system" value="yes" @checked(old('has_system') === 'yes') required> {{ __('solutions.request.has_system_yes') }}</label>
            <label class="sol-radio"><input type="radio" name="has_system" value="no" @checked(old('has_system') === 'no')> {{ __('solutions.request.has_system_no') }}</label>
            @error('has_system')<p class="sol-error">{{ $message }}</p>@enderror
        </fieldset>

        <div class="f-field">
            <label class="f-label" for="sol-message">{{ __('solutions.request.message') }}</label>
            <textarea class="f-input" id="sol-message" name="message" maxlength="5000" rows="4">{{ old('message') }}</textarea>
            @error('message')<p class="sol-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="f-btn f-btn--primary">@include('partials.finder.icon', ['name' => 'send', 'size' => 18]){{ __('solutions.request.submit') }}</button>
    </form>
</div>
@endunless
@endsection
