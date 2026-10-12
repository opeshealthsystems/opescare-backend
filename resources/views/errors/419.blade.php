@extends('errors._page', ['code' => '419', 'icon' => 'timer-off', 'tone' => 'warn'])

@section('actions')
    <a href="{{ url()->previous() }}" class="btn-primary"><i data-lucide="rotate-ccw"></i>{{ __('errors.actions.try_again') }}</a>
    <a href="{{ route('login') }}" class="btn-ghost"><i data-lucide="log-in"></i>{{ __('errors.actions.sign_in') }}</a>
@endsection
