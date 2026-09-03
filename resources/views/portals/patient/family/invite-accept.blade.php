<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('public.portal.invite_accept_title') }}</title>
    <meta name="theme-color" content="#0F4C81">
    <link rel="stylesheet" href="{{ asset('assets/finder.css') }}">
</head>
<body>
<main style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div class="f-card" style="max-width:480px;width:100%;text-align:center;">
        @if($error)
            <div style="display:flex;gap:10px;align-items:flex-start;text-align:left;padding:12px 14px;border-radius:var(--f-r);background:var(--f-danger-wash);color:var(--f-danger);margin-bottom:16px;">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p style="margin:0;">{{ $error }}</p>
            </div>
            <a href="{{ route('login') }}" class="f-btn f-btn--primary" style="min-height:48px;">{{ __('public.portal.invite_accept_go_login') }}</a>
        @else
            <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--f-wash);color:var(--f-brand);margin:0 auto 12px;">
                @include('partials.finder.icon', ['name' => 'users', 'size' => 24])
            </div>
            <h1 style="font-size:1.25rem;font-weight:800;margin:0 0 10px;">{{ __('public.portal.invite_accept_h3') }}</h1>
            <p style="color:var(--f-ink-soft);margin:0 0 20px;">
                <strong>{{ $link->guardianUser->name ?? $link->guardianUser->email }}</strong> {{ __('public.portal.invite_accept_wants') }}
                <strong>{{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}</strong>{{ __('public.portal.invite_accept_records') }}
                <strong>@enum($link->relationship)</strong>
                {{ __('public.portal.invite_accept_with') }} <strong>{{ $link->access_level === 'full' ? __('public.portal.invite_accept_full') : __('public.portal.invite_accept_readonly') }}</strong> {{ __('public.portal.invite_accept_access') }}
            </p>
            <form method="POST" action="{{ route('portals.patient.family.invite.confirm', $token) }}">
                @csrf
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                    <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                        {{ __('public.portal.invite_accept_btn') }}
                    </button>
                    <a href="{{ route('login') }}" class="f-btn">{{ __('public.portal.invite_accept_decline') }}</a>
                </div>
            </form>
        @endif
    </div>
</main>
</body>
</html>
