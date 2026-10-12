@extends('errors._page', ['code' => '402', 'icon' => 'credit-card', 'tone' => 'light'])

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
    <a href="{{ route('public.contact') }}" class="btn-ghost"><i data-lucide="headset"></i>{{ __('errors.actions.contact_support') }}</a>
@endsection
