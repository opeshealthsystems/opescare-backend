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
    <div style="max-width:34rem;margin:0 auto;text-align:center;">

        <div style="width:3.5rem;height:3.5rem;margin:0 auto 1.5rem;display:flex;align-items:center;justify-content:center;background:#FEF3C7;border-radius:1rem;">
            <i data-lucide="clock" style="width:1.75rem;height:1.75rem;color:#B45309;"></i>
        </div>

        <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:0.75rem;color:var(--color-text-primary,#0F172A);">
            {{ __('auth.portal_unavailable.title') }}
        </h1>

        <p style="color:var(--color-text-secondary,#475569);line-height:1.6;margin-bottom:1.5rem;">
            {{ __('auth.portal_unavailable.body') }}
        </p>

        <p style="color:var(--color-text-secondary,#475569);line-height:1.6;margin-bottom:2rem;font-size:0.9375rem;">
            {{ __('auth.portal_unavailable.next') }}
        </p>

        {{-- auth.css is the only stylesheet this layout loads, and it names
             its buttons .auth-btn — not the .btn of the public site. --}}
        <div style="display:flex;justify-content:center;gap:0.75rem;flex-wrap:wrap;">
            <a href="{{ route('public.contact') }}" class="auth-btn auth-btn-primary" style="text-decoration:none;">
                {{ __('auth.portal_unavailable.cta_contact') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="auth-btn auth-btn-secondary">
                    {{ __('auth.portal_unavailable.cta_signout') }}
                </button>
            </form>
        </div>

        <p style="margin-top:2rem;font-size:0.8125rem;color:var(--color-text-muted,#94A3B8);">
            {{ __('auth.portal_unavailable.signed_in_as', ['email' => auth()->user()?->email ?? '—']) }}
        </p>
    </div>
@endsection
