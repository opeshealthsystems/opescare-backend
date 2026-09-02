@extends('layouts.portal')

@section('title', __('notifications.prefs_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('notifications.prefs_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('notifications.prefs_title') }}</h1>
        <p class="page-subtitle">{{ __('notifications.prefs_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.notifications') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('notifications.title') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

<form method="POST" action="{{ route('portals.patient.notifications.preferences.update') }}">
    @csrf

    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="sliders-horizontal"></i> {{ __('notifications.prefs_channels') }}</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('notifications.category') }}</th>
                        @foreach($channels as $ch)<th style="text-align:center;">{{ __('notifications.channel_' . $ch) }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $cat)
                    <tr>
                        <td data-label="{{ __('notifications.category') }}"><strong>{{ __('notifications.cat_' . $cat) }}</strong></td>
                        @foreach($channels as $ch)
                        <td style="text-align:center;" data-label="{{ __('notifications.channel_' . $ch) }}">
                            <input type="checkbox" name="prefs[{{ $cat }}][{{ $ch }}]" value="1" @checked($prefs[$cat][$ch] ?? false)>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel mb-4" style="max-width:420px;">
        <div class="panel-body">
            <label class="form-label" for="language">{{ __('notifications.language') }}</label>
            <select id="language" name="language" class="form-control">
                <option value="en" @selected($language === 'en')>English</option>
                <option value="fr" @selected($language === 'fr')>Français</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('notifications.prefs_save') }}</button>
</form>

@endsection
