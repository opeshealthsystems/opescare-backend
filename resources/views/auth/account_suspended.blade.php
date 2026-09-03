@extends('layouts.auth')

@section('title', __('onboarding.suspended.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--danger f-auth-badge--center">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('onboarding.suspended.title') }}</h1>
            <p class="f-auth-sub">{{ __('onboarding.suspended.desc') }}</p>

            {{-- The note this replaced printed a fabricated audit tag built from
                 rand() — a fake reference number nobody could look up. The honest
                 version says only what is true: attempts are logged, access stays
                 paused pending review. --}}
            <div class="f-auth-meta">
                <p class="f-auth-meta__title">{{ __('onboarding.suspended.security_warning') }}</p>
                <p class="f-auth-fine" style="margin:0">{{ __('finder_auth.suspended.note') }}</p>
            </div>

            <a href="{{ route('public.contact') }}" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                {{ __('onboarding.brand.contact_support') }}
            </a>

            <a href="{{ route('login') }}" class="f-btn f-btn--block">
                @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
                {{ __('onboarding.selector.signin') }}
            </a>
        </div>
    </div>
@endsection
