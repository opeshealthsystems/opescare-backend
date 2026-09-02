@extends('layouts.auth')

@section('title', __('onboarding.suspended.title'))

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--p-bg,#f0f4f8);font-family:var(--p-font,'Inter',system-ui,sans-serif);display:flex;align-items:center;justify-content:center;padding:1.5rem}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(15,76,129,.1);padding:2.5rem 2rem;width:100%;max-width:480px;text-align:center}
.status-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem}
.status-icon i{width:32px;height:32px}
.status-icon--danger{background:#fef2f2}.status-icon--danger i{color:#dc2626}
.auth-card__title{font-size:1.375rem;font-weight:700;color:#dc2626;margin:0 0 .5rem}
.auth-card__sub{font-size:.875rem;color:#64748b;margin:0 0 1.75rem;line-height:1.5}
.security-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.125rem;margin-bottom:1.75rem;text-align:left}
.security-box__heading{display:flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem}
.security-box__heading i{width:14px;height:14px;color:#dc2626}
.security-box__body{font-size:.75rem;color:#64748b;line-height:1.5;font-weight:500}
.security-box__body code{background:#f1f5f9;padding:.1em .35em;border-radius:4px;font-size:.75rem;color:#0f172a}
.btn-danger-full{width:100%;padding:.75rem;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;text-decoration:none;margin-bottom:.75rem}
.btn-danger-full:hover{background:#b91c1c}
.btn-secondary-full{width:100%;padding:.75rem;background:#fff;color:#0F4C81;border:1.5px solid #0F4C81;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s,color .15s;text-decoration:none}
.btn-secondary-full:hover{background:rgba(15,76,129,.05)}
</style>

<div class="auth-card">
    <div class="status-icon status-icon--danger">
        <i data-lucide="ban"></i>
    </div>

    <h1 class="auth-card__title">{{ __('onboarding.suspended.title') }}</h1>
    <p class="auth-card__sub">{{ __('onboarding.suspended.desc') }}</p>

    <div class="security-box">
        <div class="security-box__heading">
            <i data-lucide="lock"></i>
            <span>{{ __('onboarding.suspended.security_warning') }}</span>
        </div>
        <p class="security-box__body">
            Access attempt has been logged under audit tag <code>AUDIT-ERR-SUSPEND-{{ rand(1000, 9999) }}</code> along with connection headers. Direct API and clinical system bridges associated with this credential profile are temporarily frozen.
        </p>
    </div>

    <a href="{{ route('public.contact') }}" class="btn-danger-full">
        <i data-lucide="message-square"></i>
        <span>{{ __('onboarding.brand.contact_support') }}</span>
    </a>

    <a href="{{ route('login') }}" class="btn-secondary-full">
        <i data-lucide="arrow-left"></i>
        <span>{{ __('onboarding.selector.signin') }}</span>
    </a>
</div>
@endsection
