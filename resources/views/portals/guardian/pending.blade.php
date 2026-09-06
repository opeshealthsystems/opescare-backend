@extends('layouts.auth')

@section('title', __('onboarding.guardian.pending_title'))

{{--
    Where a caregiver waits — and, now, leaves.

    A facility may still verify the relationship, but the guardian does not
    have to wait for that to start: registering a child (who has no Health
    ID yet) or inviting an adult (who accepts from their own account) creates
    the family link that opens the portal. RequireCompleteGuardianProfile
    lets a guardian with a submitted profile reach those two pages.
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

        <p class="auth-fineprint" style="margin-top:1.25rem;font-weight:600;">{{ __('family.pending_next_steps') }}</p>

        <div style="display:grid;gap:.6rem;margin:.5rem 0 1rem;" data-section="guardian-next-steps">
            <a href="{{ route('portals.patient.family.add') }}" class="auth-btn auth-btn-primary" style="display:inline-flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;">
                <i data-lucide="baby" style="width:1.05rem;height:1.05rem;"></i>
                {{ __('family.dash_add_child') }}
            </a>
            <a href="{{ route('portals.patient.family.invite') }}" class="auth-btn auth-btn-secondary" style="display:inline-flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;">
                <i data-lucide="send" style="width:1.05rem;height:1.05rem;"></i>
                {{ __('family.dash_invite_adult') }}
            </a>
        </div>

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
