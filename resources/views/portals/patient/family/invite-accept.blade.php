<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('public.portal.invite_accept_title') }}</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="portal-body" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div class="panel" style="max-width:480px;width:100%;margin:2rem;">
    <div class="panel-body" style="text-align:center;">
        @if($error)
        <div class="alert alert-danger mb-6">
            <i data-lucide="alert-circle"></i> {{ $error }}
        </div>
        <a href="{{ route('login') }}" class="btn btn-primary">{{ __('public.portal.invite_accept_go_login') }}</a>
        @else
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="users"></i></div>
            <h3>{{ __('public.portal.invite_accept_h3') }}</h3>
            <p>
                <strong>{{ $link->guardianUser->name ?? $link->guardianUser->email }}</strong> {{ __('public.portal.invite_accept_wants') }}
                <strong>{{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}</strong>{{ __('public.portal.invite_accept_records') }}
                <strong>@enum($link->relationship)</strong>
                {{ __('public.portal.invite_accept_with') }} <strong>{{ $link->access_level === 'full' ? __('public.portal.invite_accept_full') : __('public.portal.invite_accept_readonly') }}</strong> {{ __('public.portal.invite_accept_access') }}
            </p>
        </div>
        <form method="POST" action="{{ route('portals.patient.family.invite.confirm', $token) }}">
            @csrf
            <div class="row-actions" style="justify-content:center;">
                <button type="submit" class="btn btn-primary">{{ __('public.portal.invite_accept_btn') }}</button>
                <a href="{{ route('login') }}" class="btn btn-secondary">{{ __('public.portal.invite_accept_decline') }}</a>
            </div>
        </form>
        @endif
    </div>
</div>
<script src="{{ asset('js/lucide.min.js') }}"></script>
<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
