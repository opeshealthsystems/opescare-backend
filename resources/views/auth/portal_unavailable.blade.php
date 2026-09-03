@extends('layouts.auth')

@section('title', __('auth.portal_unavailable.page_title'))

@section('content')
    {{--
        Reached only by redirect, and only by an authenticated user whose own
        portal is frozen out of the current release. The frozen URL itself still
        answers 404 byte-identically to a nonexistent route — that concealment
        is deliberate and untouched. This page exists because a signed-in user
        who has already been told which portal is theirs deserves a sentence
        explaining why it isn't there, instead of a blank 404.
    --}}
    <div class="f-auth-col">
        <div class="f-auth-card f-auth-card--center">
            <div class="f-auth-badge f-auth-badge--warn f-auth-badge--center">
                @include('partials.finder.icon', ['name' => 'clock', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('auth.portal_unavailable.title') }}</h1>
            <p class="f-auth-sub" style="margin-bottom:12px">{{ __('auth.portal_unavailable.body') }}</p>
            <p class="f-auth-sub">{{ __('auth.portal_unavailable.next') }}</p>

            <a href="{{ route('public.contact') }}" class="f-btn f-btn--primary f-btn--block" style="margin-bottom:10px">
                {{ __('auth.portal_unavailable.cta_contact') }}
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="f-btn f-btn--block">
                    {{ __('auth.portal_unavailable.cta_signout') }}
                </button>
            </form>

            <p class="f-auth-fine" style="margin-top:20px">
                {{ __('auth.portal_unavailable.signed_in_as', ['email' => auth()->user()?->email ?? '—']) }}
            </p>
        </div>
    </div>
@endsection
