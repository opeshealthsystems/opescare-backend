@extends('layouts.portal')

@section('title', __('notifications.prefs_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('notifications.prefs_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('content')

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('notifications.prefs_title') }}</h1>
            <p class="pf-sub">{{ __('notifications.prefs_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.notifications') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('notifications.title') }}
        </a>
    </div>

    <form method="POST" action="{{ route('portals.patient.notifications.preferences.update') }}">
        @csrf

        <div class="f-card" style="margin-bottom:12px;">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'sliders-horizontal', 'size' => 18])
                {{ __('notifications.prefs_channels') }}
            </h2>
            <div class="pf-table-wrap">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th>{{ __('notifications.category') }}</th>
                            @foreach($channels as $ch)<th style="text-align:center;">{{ __('notifications.channel_' . $ch) }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                        <tr>
                            <td><strong>{{ __('notifications.cat_' . $cat) }}</strong></td>
                            @foreach($channels as $ch)
                            <td style="text-align:center;">
                                <input type="checkbox" name="prefs[{{ $cat }}][{{ $ch }}]" value="1"
                                       aria-label="{{ __('notifications.cat_' . $cat) }} — {{ __('notifications.channel_' . $ch) }}"
                                       @checked($prefs[$cat][$ch] ?? false)>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="language">
                    @include('partials.finder.icon', ['name' => 'globe', 'size' => 15])
                    {{ __('notifications.language') }}
                </label>
                <select id="language" name="language" class="f-input" style="max-width:280px;">
                    <option value="en" @selected($language === 'en')>English</option>
                    <option value="fr" @selected($language === 'fr')>Français</option>
                </select>
            </div>
        </div>

        <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
            @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
            {{ __('notifications.prefs_save') }}
        </button>
    </form>
</div>

@endsection
