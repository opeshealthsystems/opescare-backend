@extends('errors._page', ['code' => '503', 'icon' => 'wrench', 'tone' => 'dark'])

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
    <a href="{{ route('public.status') }}" class="btn-ghost"><i data-lucide="activity"></i>{{ __('errors.actions.status_page') }}</a>
@endsection
