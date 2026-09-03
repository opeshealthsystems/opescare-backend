@extends('layouts.auth')

@section('title', __('auth.otp_unavailable.page_title'))

{{--
    This page used to render six code boxes, a live 3:00 countdown and a
    "Resend verification code" link over a controller that verified nothing and
    sent nothing — every code except two hardcoded literals was accepted. The
    form itself was the lie: a countdown implies a code exists somewhere.

    /verify/otp is not connected to a verification channel (see the note above
    PublicPageController::showVerifyOtp). Until it is, the page says so — it
    FAILS CLOSED, and the honest "unavailable" message below stays. The real
    second factor is /mfa/challenge, reached from the sign-in screen.
--}}

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('auth.otp_unavailable.title') }}</h1>

            @if(session('error'))
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <p class="f-auth-sub" style="margin-bottom:12px">{{ __('auth.otp_unavailable.body') }}</p>
            <p class="f-auth-sub">{{ __('auth.otp_unavailable.next') }}</p>

            <a href="{{ route('login') }}" class="f-btn f-btn--primary f-btn--block">
                @include('partials.finder.icon', ['name' => 'log-in', 'size' => 18])
                {{ __('auth.otp_unavailable.cta_signin') }}
            </a>

            <div class="f-auth-note">
                @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
                <p>{{ __('auth.otp_unavailable.security_note') }}</p>
            </div>
        </div>
    </div>
@endsection
