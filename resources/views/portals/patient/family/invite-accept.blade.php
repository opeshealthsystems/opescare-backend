@extends('layouts.finder')
@section('title', __('public.portal.invite_accept_title'))
@push('head')
<link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
<style>.fam-invite { display: flex; justify-content: center; padding: 48px 16px 64px; }</style>
@endpush

@section('content')
<div class="fam-invite">
    <div class="f-card ods-93f49cc">
        @if($error)
            <div class="ods-c06b534">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p class="ods-1386d50">{{ $error }}</p>
            </div>
            <a href="{{ route('login') }}" class="f-btn f-btn--primary ods-a27b458">{{ __('public.portal.invite_accept_go_login') }}</a>
        @else
            <div class="ods-a425013">
                @include('partials.finder.icon', ['name' => 'users', 'size' => 24])
            </div>
            <h1 class="ods-ec0ea8b">{{ __('public.portal.invite_accept_h3') }}</h1>
            <p class="ods-a132887">
                <strong>{{ $link->guardianUser->name ?? $link->guardianUser->email }}</strong> {{ __('public.portal.invite_accept_wants') }}
                <strong>{{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}</strong>{{ __('public.portal.invite_accept_records') }}
                <strong>@enum($link->relationship)</strong>
                {{ __('public.portal.invite_accept_with') }} <strong>{{ $link->access_level === 'full' ? __('public.portal.invite_accept_full') : __('public.portal.invite_accept_readonly') }}</strong> {{ __('public.portal.invite_accept_access') }}
            </p>
            <form method="POST" action="{{ route('portals.patient.family.invite.confirm', $token) }}">
                @csrf
                <div class="ods-d6bd826">
                    <button type="submit" class="f-btn f-btn--primary ods-a27b458">
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                        {{ __('public.portal.invite_accept_btn') }}
                    </button>
                    <a href="{{ route('login') }}" class="f-btn">{{ __('public.portal.invite_accept_decline') }}</a>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
