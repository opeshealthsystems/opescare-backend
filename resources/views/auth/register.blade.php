@extends('layouts.auth')

@section('title', __('onboarding.selector.title'))

@section('content')
    <div class="f-auth-col f-auth-col--wide">
        <div class="f-auth-card">
            <div class="f-auth-card--center">
                <h1 class="f-auth-title">{{ __('onboarding.selector.title') }}</h1>
                <p class="f-auth-sub">{{ __('onboarding.selector.subtitle') }}</p>
            </div>

            <div class="f-auth-grid">

                {{-- Patient --}}
                <a href="{{ route('register.patient') }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.patient_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.patient_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Guardian / Caregiver --}}
                <a href="{{ route('register.guardian') }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'users', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.guardian_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.guardian_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Hospital / Clinic --}}
                <a href="{{ route('register.organization', ['type' => 'hospital']) }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'building-2', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.hospital_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.hospital_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Pharmacy --}}
                <a href="{{ route('register.organization', ['type' => 'pharmacy']) }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'pill', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.pharmacy_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.pharmacy_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Laboratory --}}
                <a href="{{ route('register.organization', ['type' => 'laboratory']) }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'search', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.laboratory_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.laboratory_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Insurer --}}
                <a href="{{ route('register.organization', ['type' => 'insurance_company']) }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.insurer_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.insurer_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Developer API Access --}}
                <a href="{{ route('register.developer') }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'external-link', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.developer_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.developer_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

                {{-- Public Health / Research --}}
                <a href="{{ route('register.organization', ['type' => 'public_health']) }}" class="f-auth-choice">
                    <span class="f-auth-choice__icon">@include('partials.finder.icon', ['name' => 'globe', 'size' => 20])</span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title">{{ __('onboarding.selector.cards.public_health_title') }}</span>
                        <span class="f-auth-choice__desc">{{ __('onboarding.selector.cards.public_health_desc') }}</span>
                    </span>
                    <span class="f-auth-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                </a>

            </div>

            <div class="f-auth-alt f-auth-alt--divide">
                <p>{{ __('onboarding.selector.already_have') }}
                    <a href="{{ route('login') }}">{{ __('onboarding.selector.signin') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection
