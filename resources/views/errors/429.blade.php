@extends('errors._page', ['code' => '429', 'icon' => 'hourglass', 'tone' => 'warn'])

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary"><i data-lucide="home"></i>{{ __('errors.actions.home') }}</a>
    <a href="{{ route('public.help') }}" class="btn-ghost"><i data-lucide="help-circle"></i>{{ __('errors.actions.help_centre') }}</a>
@endsection
