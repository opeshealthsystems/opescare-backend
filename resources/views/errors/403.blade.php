@extends('errors._page', ['code' => '403', 'icon' => 'shield-x', 'tone' => 'warn'])

@php
    // The reason the request was refused (DASHBOARD_SCOPE: "role refusal = 403
    // that names the reason"). Controllers abort with a translated message;
    // Laravel's generic defaults carry no reason, so they are not repeated.
    $errReason = isset($exception) ? trim((string) $exception->getMessage()) : '';
    if (in_array($errReason, ['', 'Forbidden', 'This action is unauthorized.', 'Unauthorized.'], true)) {
        $errReason = '';
    }
    $errUser = auth()->user();
    $errHome = null;
    if ($errUser) {
        try {
            $errHome = app(\App\Services\Dashboard\DashboardProfileService::class)->landingUrlForUser($errUser);
        } catch (\Throwable) {
            $errHome = null;
        }
    }
@endphp

@if($errReason !== '')
    @section('note'){{ __('errors.reason_label') }} {{ $errReason }}@endsection
@endif

@section('actions')
    @if($errUser)
        <a href="{{ $errHome ?: url('/') }}" class="btn-primary"><i data-lucide="layout-dashboard"></i>{{ __('errors.actions.my_dashboard') }}</a>
        <a href="{{ route('public.help') }}" class="btn-ghost"><i data-lucide="help-circle"></i>{{ __('errors.actions.help_centre') }}</a>
    @else
        <a href="{{ route('login') }}" class="btn-primary"><i data-lucide="log-in"></i>{{ __('errors.actions.sign_in') }}</a>
        <a href="{{ url('/') }}" class="btn-ghost"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
    @endif
@endsection
