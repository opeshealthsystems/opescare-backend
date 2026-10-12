@extends('errors._page', ['code' => '500', 'icon' => 'server-crash', 'tone' => 'dark'])

@section('note'){{ __('errors.pages.500.note') }}@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
    <a href="{{ route('public.contact') }}" class="btn-ghost"><i data-lucide="headset"></i>{{ __('errors.actions.contact_support') }}</a>
@endsection

@section('footer'){{ __('errors.pages.500.footer_prefix') }} <a href="{{ route('public.status') }}">{{ __('errors.actions.status_page') }}</a> {{ __('errors.pages.500.footer_suffix') }}@endsection
