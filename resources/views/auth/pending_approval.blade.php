@extends('layouts.auth')

@section('title', __('onboarding.pending.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--warn f-auth-badge--center">
                @include('partials.finder.icon', ['name' => 'clock', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('onboarding.pending.title') }}</h1>
            <p class="f-auth-sub">{{ __('onboarding.pending.desc') }}</p>

            <div class="f-auth-meta">
                <p class="f-auth-meta__title">{{ __('onboarding.pending.card_header') }}</p>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label">{{ __('onboarding.pending.ref_number') }}</span>
                    <span class="f-auth-meta__value f-auth-meta__value--mono">{{ $ref_code }}</span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label">{{ __('onboarding.pending.org_name') }}</span>
                    <span class="f-auth-meta__value">{{ $org_name }}</span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label">{{ __('onboarding.pending.submitted_date') }}</span>
                    <span class="f-auth-meta__value">{{ $submitted_date }}</span>
                </div>

                <div class="f-auth-meta__row">
                    <span class="f-auth-meta__label">{{ __('onboarding.pending.status_label') }}</span>
                    <span class="f-badge f-badge--warn">{{ __('onboarding.pending.status_under_review') }}</span>
                </div>

                <div class="f-auth-meta__row f-auth-meta__row--block">
                    <span class="f-auth-meta__label">{{ __('onboarding.pending.admin_notes') }}</span>
                    <div class="f-auth-meta__note">{{ $admin_note ?? __('onboarding.pending.default_admin_note') }}</div>
                </div>
            </div>

            <a href="{{ route('public.contact') }}" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                {{ __('onboarding.pending.cta_support') }}
            </a>

            <a href="{{ route('public.landing') }}" class="f-btn f-btn--block">
                @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
                {{ __('onboarding.common.back_to_home') }}
            </a>
        </div>
    </div>
@endsection
