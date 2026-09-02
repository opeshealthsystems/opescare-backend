@extends('layouts.auth')

@section('title', __('onboarding.invite.title'))

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
.form-input--error{border-color:#dc2626}
.field-error{font-size:.8125rem;color:#dc2626;margin-top:.3rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;margin-top:.5rem}
.btn-primary-full:hover{background:#0a3560}
.btn-secondary-full{width:100%;padding:.75rem;background:#fff;color:#0F4C81;border:1.5px solid #0F4C81;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s,color .15s;text-decoration:none}
.btn-secondary-full:hover{background:rgba(15,76,129,.05)}
.alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.875rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.875rem;display:flex;gap:.5rem}
.status-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem}
.status-icon i{width:32px;height:32px}
.status-icon--danger{background:#fef2f2}.status-icon--danger i{color:#dc2626}
.invite-meta{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.125rem;margin-bottom:1.5rem}
.invite-meta__heading{font-size:.75rem;font-weight:800;color:#0F4C81;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .875rem}
.invite-meta__row{display:flex;justify-content:space-between;align-items:center;padding:.375rem 0;border-bottom:1px solid #f1f5f9}
.invite-meta__row:last-child{border-bottom:none}
.invite-meta__label{font-size:.75rem;color:#64748b;font-weight:500}
.invite-meta__value{font-size:.8125rem;color:#0f172a;font-weight:600;text-align:right}
.invite-meta__value--role{color:#0F4C81;font-weight:800}
.invite-meta__value--expiry{color:#d97706}
.section-heading{font-size:.75rem;font-weight:800;color:#0F4C81;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .875rem}
.checkbox-group{display:flex;align-items:flex-start;gap:.5rem;margin-top:.875rem}
.checkbox-group input[type="checkbox"]{margin-top:2px;accent-color:#0F4C81;width:16px;height:16px;flex-shrink:0}
.checkbox-group label{font-size:.875rem;color:#0f172a;line-height:1.5}
.error-center{text-align:center;padding:1rem 0}
</style>

<div class="auth-card">
    @if(isset($error))
        <div class="error-center">
            <div class="status-icon status-icon--danger" style="margin-bottom:1.25rem">
                <i data-lucide="shield-alert"></i>
            </div>

            @if($error === 'expired')
                <h1 class="auth-card__title" style="text-align:center">{{ __('onboarding.invite.errors.expired') }}</h1>
            @elseif($error === 'used')
                <h1 class="auth-card__title" style="text-align:center">{{ __('onboarding.invite.errors.used') }}</h1>
            @elseif($error === 'revoked')
                <h1 class="auth-card__title" style="text-align:center">{{ __('onboarding.invite.errors.revoked') }}</h1>
            @endif

            <p class="auth-card__sub" style="text-align:center">
                {{ __('team.invite_error_help') }}
            </p>

            <a href="{{ route('login') }}" class="btn-secondary-full">
                <i data-lucide="arrow-left"></i>
                <span>{{ __('onboarding.selector.signin') }}</span>
            </a>
        </div>
    @else
        <div class="auth-card__icon">
            <i data-lucide="mail-open"></i>
        </div>
        <h1 class="auth-card__title">{{ __('onboarding.invite.title') }}</h1>
        <p class="auth-card__sub">{{ __('onboarding.invite.subtitle') }}</p>

        <div class="invite-meta">
            <p class="invite-meta__heading">{{ __('onboarding.invite.sec_details') }}</p>
            <div class="invite-meta__row">
                <span class="invite-meta__label">{{ __('team.invite_email_lbl') }}</span>
                <span class="invite-meta__value">{{ $email }}</span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label">{{ __('onboarding.invite.facility_lbl') }}</span>
                <span class="invite-meta__value">{{ $facility_name }}</span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label">{{ __('onboarding.invite.role_lbl') }}</span>
                <span class="invite-meta__value invite-meta__value--role">{{ $role_name }}</span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label">{{ __('onboarding.invite.invited_by_lbl') }}</span>
                <span class="invite-meta__value">{{ $invited_by }}</span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label">{{ __('onboarding.invite.expiry_lbl') }}</span>
                <span class="invite-meta__value invite-meta__value--expiry">{{ $expiry }}</span>
            </div>
        </div>

        @if(session('error'))
            <div class="alert-error">
                <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('invite.accept.submit', $token) }}" method="POST">
            @csrf

            <p class="section-heading">{{ __('onboarding.invite.sec_profile') }}</p>

            <div class="form-group">
                <label for="name" class="form-label">{{ __('onboarding.common.full_name') }} *</label>
                <input type="text" id="name" name="name" class="form-input{{ $errors->has('name') ? ' form-input--error' : '' }}" required value="{{ old('name') }}">
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">{{ __('onboarding.common.phone') }} *</label>
                <input type="tel" id="phone" name="phone" class="form-input{{ $errors->has('phone') ? ' form-input--error' : '' }}" required value="{{ old('phone') }}">
                @error('phone')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">{{ __('onboarding.common.password') }} *</label>
                    <input type="password" id="password" name="password" class="form-input{{ $errors->has('password') ? ' form-input--error' : '' }}" required minlength="8" placeholder="{{ __('team.password_hint') }}">
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">{{ __('onboarding.common.confirm_password') }} *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input{{ $errors->has('confirm_password') ? ' form-input--error' : '' }}" required placeholder="••••••••">
                    @error('confirm_password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="accept_terms" name="accept_terms" required>
                <label for="accept_terms">{{ __('onboarding.invite.terms_label') }}</label>
            </div>
            @error('accept_terms')<div class="field-error">{{ $message }}</div>@enderror

            <button type="submit" class="btn-primary-full" style="margin-top:1.25rem">
                <i data-lucide="user-check"></i>
                <span>{{ __('onboarding.invite.cta_btn') }}</span>
            </button>
        </form>
    @endif
</div>
@endsection
