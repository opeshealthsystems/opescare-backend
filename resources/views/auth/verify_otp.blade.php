@extends('layouts.auth')

@section('title', __('auth.otp_unavailable.page_title'))

{{--
    This page used to render six code boxes, a live 3:00 countdown and a
    "Resend verification code" link over a controller that verified nothing and
    sent nothing — every code except two hardcoded literals was accepted. The
    form itself was the lie: a countdown implies a code exists somewhere.

    /verify/otp is not connected to a verification channel (see the note above
    PublicPageController::showVerifyOtp). Until it is, the page says so. The
    real second factor is /mfa/challenge, reached from the sign-in screen.
--}}

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--p-bg,#f0f4f8);font-family:var(--p-font,'Inter',system-ui,sans-serif);display:flex;align-items:center;justify-content:center;padding:1.5rem}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(15,76,129,.1);padding:2.5rem 2rem;width:100%;max-width:480px}
.auth-card__icon{width:56px;height:56px;border-radius:14px;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem}
.auth-card__icon i{width:26px;height:26px;color:#0F4C81}
.auth-card__title{font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 .75rem;line-height:1.35}
.auth-card__body{font-size:.9375rem;color:#475569;margin:0 0 1rem;line-height:1.6}
.auth-card__next{font-size:.875rem;color:#64748b;margin:0 0 1.75rem;line-height:1.6}
.alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.875rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.875rem;display:flex;gap:.5rem}
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;transition:background .15s}
.btn-primary-full:hover{background:#0a3560}
.security-note{display:flex;align-items:flex-start;gap:.5rem;margin-top:1.75rem;font-size:.8125rem;color:#64748b;line-height:1.5}
.security-note i{width:14px;height:14px;color:#0F4C81;flex-shrink:0;margin-top:2px}
</style>

<div class="auth-card">
    <div class="auth-card__icon">
        <i data-lucide="shield-alert"></i>
    </div>

    <h1 class="auth-card__title">{{ __('auth.otp_unavailable.title') }}</h1>

    @if(session('error'))
        <div class="alert-error">
            <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <p class="auth-card__body">{{ __('auth.otp_unavailable.body') }}</p>
    <p class="auth-card__next">{{ __('auth.otp_unavailable.next') }}</p>

    <a href="{{ route('login') }}" class="btn-primary-full">
        <i data-lucide="log-in"></i>
        <span>{{ __('auth.otp_unavailable.cta_signin') }}</span>
    </a>

    <div class="security-note">
        <i data-lucide="shield-check"></i>
        <p style="margin:0">{{ __('auth.otp_unavailable.security_note') }}</p>
    </div>
</div>
@endsection
