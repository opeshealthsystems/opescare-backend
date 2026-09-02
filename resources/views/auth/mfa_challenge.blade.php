@extends('layouts.auth')

@section('title', 'Two-factor authentication')

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--p-bg,#f0f4f8);font-family:var(--p-font,'Inter',system-ui,sans-serif);display:flex;align-items:center;justify-content:center;padding:1.5rem}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(15,76,129,.1);padding:2.5rem 2rem;width:100%;max-width:480px}
.auth-card__icon{width:56px;height:56px;border-radius:14px;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem}
.auth-card__icon i{width:26px;height:26px;color:#0F4C81}
.auth-card__title{font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 .375rem}
.auth-card__sub{font-size:.875rem;color:#64748b;margin:0 0 1.75rem;line-height:1.5}
.form-group{margin-bottom:1.125rem}
.form-label{display:block;font-size:.8125rem;font-weight:600;color:#0f172a;margin-bottom:.375rem}
.form-input{width:100%;padding:.625rem .875rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9375rem;color:#0f172a;background:#fff;outline:none;transition:border-color .15s}
.form-input:focus{border-color:#0F4C81;box-shadow:0 0 0 3px rgba(15,76,129,.12)}
.form-input--code{letter-spacing:.2em;text-align:center;font-size:1.5rem;font-weight:700}
.form-input--error{border-color:#dc2626}
.field-error{font-size:.8125rem;color:#dc2626;margin-top:.3rem}
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;margin-top:.5rem}
.btn-primary-full:hover{background:#0a3560}
.alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.875rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.875rem;display:flex;gap:.5rem}
.auth-link{text-align:center;margin-top:1.25rem;font-size:.875rem;color:#64748b}
.auth-link a{color:#0F4C81;font-weight:600;text-decoration:none}
</style>

<div class="auth-card">
    <div class="auth-card__icon">
        <i data-lucide="shield-check"></i>
    </div>
    <h1 class="auth-card__title">Two-factor authentication</h1>
    <p class="auth-card__sub">
        @if($setupRequired ?? false)
            Multi-factor authentication is required for your role. Contact an administrator to complete enrollment before signing in.
        @else
            Enter the code from your authenticator app
        @endif
    </p>

    @if(session('error'))
        <div class="alert-error">
            <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(! ($setupRequired ?? false))
    <form action="{{ route('mfa.challenge.submit') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="code" class="form-label">Authentication code</label>
            <input
                type="text"
                id="code"
                name="code"
                class="form-input form-input--code{{ $errors->has('code') ? ' form-input--error' : '' }}"
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="000000"
                required
                autofocus
            >
            @error('code')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-primary-full">
            <i data-lucide="shield-check"></i>
            <span>Verify</span>
        </button>
    </form>
    @endif

    <div class="auth-link">
        Lost access to your authenticator?
        <a href="{{ route('public.contact') }}">Contact support</a>
    </div>
</div>
@endsection
