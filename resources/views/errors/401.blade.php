@extends('errors._page', ['code' => '401', 'icon' => 'lock', 'tone' => 'warn'])

@section('actions')
    <a href="{{ route('login') }}" class="btn-primary"><i data-lucide="log-in"></i>{{ __('errors.actions.sign_in') }}</a>
    <a href="{{ url('/') }}" class="btn-ghost"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
@endsection
