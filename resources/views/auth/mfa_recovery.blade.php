@extends('layouts.auth')

@section('title', __('finder_auth.mfa.recovery_title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 22])
            </div>

            <h1 class="f-auth-title">{{ __('finder_auth.mfa.recovery_title') }}</h1>
            <p class="f-auth-sub">{{ __('finder_auth.mfa.recovery_intro') }}</p>

            <ul class="f-mfa-codes" aria-label="{{ __('finder_auth.mfa.recovery_title') }}">
                @foreach($codes as $code)
                    <li>{{ $code }}</li>
                @endforeach
            </ul>

            <p class="f-auth-sub">{{ __('finder_auth.mfa.recovery_once') }}</p>

            <a class="f-btn f-btn--primary f-btn--block" href="{{ $continueTo }}">
                @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                {{ __('finder_auth.mfa.recovery_continue') }}
            </a>
        </div>
    </div>
@endsection
