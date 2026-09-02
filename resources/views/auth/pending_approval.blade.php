@extends('layouts.auth')

@section('title', __('onboarding.pending.title'))

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--p-bg,#f0f4f8);font-family:var(--p-font,'Inter',system-ui,sans-serif);display:flex;align-items:center;justify-content:center;padding:1.5rem}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(15,76,129,.1);padding:2.5rem 2rem;width:100%;max-width:480px;text-align:center}
.status-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem}
.status-icon i{width:32px;height:32px}
.status-icon--warning{background:#fffbeb}.status-icon--warning i{color:#d97706}
.auth-card__title{font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 .5rem}
.auth-card__sub{font-size:.875rem;color:#64748b;margin:0 0 1.75rem;line-height:1.5}
.meta-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.125rem;margin-bottom:1.75rem;text-align:left}
.meta-box__heading{font-size:.75rem;font-weight:800;color:#0F4C81;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .875rem}
.meta-row{display:flex;justify-content:space-between;align-items:center;padding:.375rem 0;border-bottom:1px solid #f1f5f9}
.meta-row:last-child{border-bottom:none}
.meta-row--block{flex-direction:column;align-items:flex-start;gap:.5rem}
.meta-label{font-size:.75rem;color:#64748b;font-weight:500}
.meta-value{font-size:.8125rem;color:#0f172a;font-weight:600;text-align:right}
.meta-value--mono{font-family:monospace;font-weight:800}
.badge-review{display:inline-flex;align-items:center;font-size:.75rem;font-weight:700;padding:.25rem .625rem;border-radius:999px;background:#fef9c3;color:#854d0e}
.admin-note{font-size:.8125rem;line-height:1.4;color:#64748b;background:#fff;border:1px dashed #e2e8f0;border-radius:8px;padding:.75rem;width:100%;font-weight:500}
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;text-decoration:none;margin-bottom:.75rem}
.btn-primary-full:hover{background:#0a3560}
.btn-secondary-full{width:100%;padding:.75rem;background:#fff;color:#0F4C81;border:1.5px solid #0F4C81;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;text-decoration:none}
.btn-secondary-full:hover{background:rgba(15,76,129,.05)}
</style>

<div class="auth-card">
    <div class="status-icon status-icon--warning">
        <i data-lucide="clock"></i>
    </div>

    <h1 class="auth-card__title">{{ __('onboarding.pending.title') }}</h1>
    <p class="auth-card__sub">{{ __('onboarding.pending.desc') }}</p>

    <div class="meta-box">
        <p class="meta-box__heading">{{ __('onboarding.pending.card_header') }}</p>

        <div class="meta-row">
            <span class="meta-label">{{ __('onboarding.pending.ref_number') }}</span>
            <span class="meta-value meta-value--mono">{{ $ref_code }}</span>
        </div>

        <div class="meta-row">
            <span class="meta-label">{{ __('onboarding.pending.org_name') }}</span>
            <span class="meta-value">{{ $org_name }}</span>
        </div>

        <div class="meta-row">
            <span class="meta-label">{{ __('onboarding.pending.submitted_date') }}</span>
            <span class="meta-value">{{ $submitted_date }}</span>
        </div>

        <div class="meta-row">
            <span class="meta-label">{{ __('onboarding.pending.status_label') }}</span>
            <span class="badge-review">{{ __('onboarding.pending.status_under_review') }}</span>
        </div>

        <div class="meta-row meta-row--block">
            <span class="meta-label">{{ __('onboarding.pending.admin_notes') }}</span>
            <div class="admin-note">{{ $admin_note ?? __('onboarding.pending.default_admin_note') }}</div>
        </div>
    </div>

    <a href="{{ route('public.contact') }}" class="btn-primary-full">
        <i data-lucide="help-circle"></i>
        <span>{{ __('onboarding.pending.cta_support') }}</span>
    </a>

    <a href="{{ route('public.landing') }}" class="btn-secondary-full">
        <i data-lucide="home"></i>
        <span>{{ __('onboarding.common.back_to_home') }}</span>
    </a>
</div>
@endsection
