@extends('layouts.auth')

@section('title', __('onboarding.guardian.pending_title'))

{{--
    Where a caregiver waits.

    Guardian access is granted by a facility after it verifies the
    relationship, so there is genuinely nothing in a portal for them yet. This
    says so plainly rather than dropping them into an empty patient dashboard.
--}}

@section('content')
    <div class="auth-card">
        <div class="auth-status-icon" aria-hidden="true">
            <i data-lucide="clock" style="width:2.5rem;height:2.5rem;"></i>
        </div>

        <h1 class="auth-headline">{{ __('onboarding.guardian.pending_headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.guardian.pending_body') }}</p>

        @if (session('success'))
            <div class="auth-alert auth-alert--success" role="status">
                <i data-lucide="check-circle" style="width:1.05rem;height:1.05rem;flex-shrink:0;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <p class="auth-fineprint">{{ __('onboarding.guardian.pending_next') }}</p>

        <p class="auth-alt-action">
            <a href="{{ route('public.contact') }}">{{ __('onboarding.guardian.pending_contact') }}</a>
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-btn auth-btn-secondary auth-btn--mt">
                {{ __('onboarding.guardian.pending_signout') }}
            </button>
        </form>
    </div>
@endsection
