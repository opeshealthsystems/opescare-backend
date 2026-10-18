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
<link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
    <div class="auth-card">
        <div class="auth-status-icon" aria-hidden="true">
            <i class="ods-2f607f0" data-lucide="clock"></i>
        </div>

        <h1 class="auth-headline">{{ __('onboarding.guardian.pending_headline') }}</h1>
        <p class="auth-subheadline">{{ __('onboarding.guardian.pending_body') }}</p>

        @if (session('success'))
            <div class="auth-alert auth-alert--success" role="status">
                <i class="ods-fe2db25" data-lucide="check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <p class="auth-fineprint ods-5262216">{{ __('family.pending_next_steps') }}</p>

        <div class="ods-4c8faeb" data-section="guardian-next-steps">
            <a href="{{ route('portals.patient.family.add') }}" class="auth-btn auth-btn-primary ods-7850b4d">
                <i class="ods-f0b6e6f" data-lucide="baby"></i>
                {{ __('family.dash_add_child') }}
            </a>
            <a href="{{ route('portals.patient.family.invite') }}" class="auth-btn auth-btn-secondary ods-7850b4d">
                <i class="ods-f0b6e6f" data-lucide="send"></i>
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
