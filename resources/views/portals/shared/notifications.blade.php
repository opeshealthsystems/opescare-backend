@extends('layouts.portal')

@section('title', __('notifications_ui.center_title', [], app()->getLocale()))
@section('breadcrumb_section', __('notifications_ui.center_title', [], app()->getLocale()))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('notifications_ui.center_title', [], app()->getLocale()) }}</h1>
        <p class="od-page__sub">{{ __('notifications_ui.center_subtitle', [], app()->getLocale()) }}</p>
    </div>

    @if($events->total() > 0)
    <form method="POST" action="{{ route('notifications.read') }}" class="odc-head__actions">
        @csrf
        <button type="submit" class="od-btn od-btn--secondary">
            <i data-lucide="check-check"></i>
            {{ __('notifications_ui.mark_all_read', [], app()->getLocale()) }}
        </button>
    </form>
    @endif
</header>

@if(session('success'))
<div class="odc-note odc-note--ok" role="status">
    <i data-lucide="check-circle"></i>
    <div>{{ session('success') }}</div>
</div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('notifications_ui.center_title', [], app()->getLocale()) }} <span class="od-card__count">({{ $events->total() }})</span></h2>
        @if($unread > 0)
            <span class="od-pill od-pill--dot od-pill--urgent">{{ $unread }}</span>
        @endif
    </header>

@if($events->total() === 0)

    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="bell-off"></i></span>
        <div class="od-empty__title">{{ __('notifications_ui.empty', [], app()->getLocale()) }}</div>
        <p class="od-empty__text">{{ __('notifications_ui.empty_desc', [], app()->getLocale()) }}</p>
    </div>

@else

    <div class="od-card__body">
        <ul class="od-list">
            @foreach($events as $e)
                @php
                    $p = json_decode($e->payload_json ?? '{}', true) ?: [];

                    $category = $e->communication_type ?: 'general';
                    $known    = ['blood', 'pharmacy', 'appointment', 'account', 'admin', 'general'];
                    $catKey   = in_array($category, $known, true) ? $category : 'general';

                    $icon = match ($category) {
                        'blood'       => 'droplet',
                        'pharmacy'    => 'pill',
                        'appointment' => 'calendar-check-2',
                        'admin'       => 'shield',
                        'account'     => 'user',
                        default       => 'bell',
                    };

                    // Tint keyed to category (blue = brand default).
                    $tint = match ($category) {
                        'blood'    => 'red',
                        'pharmacy' => 'teal',
                        'admin'    => 'amber',
                        'account'  => 'sky',
                        default    => 'blue',
                    };

                    $title = $p['title'] ?? \Illuminate\Support\Str::headline($e->event_type);
                    $body  = $p['body'] ?? ($p['message'] ?? '');
                    // The payload's url is usually an APP path (`/labs/<id>`,
                    // `/clinician/results/lab-<id>`) and the web has no such
                    // page; linking it produced a 404 from the bell.
                    $url   = \App\Support\WebNotificationLink::resolve($p['url'] ?? null);

                    $isUnread = $seenAt === null || $e->created_at > $seenAt;
                    $time     = \Illuminate\Support\Carbon::parse($e->created_at)->diffForHumans();
                @endphp

                <li class="od-list__row odc-notif {{ $url ? 'is-link' : '' }} {{ $isUnread ? 'odc-unread' : '' }}">
                    <span class="od-list__icon od-tint--{{ $tint }}"><i data-lucide="{{ $icon }}"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ $title }}</span>
                        @if(trim((string) $body) !== '')
                            <span class="od-list__sub">{{ $body }}</span>
                        @endif
                        <span class="odc-notif__meta">
                            <span class="od-pill od-pill--neutral">{{ __('notifications_ui.category_' . $catKey, [], app()->getLocale()) ?: \Illuminate\Support\Str::ucfirst($catKey) }}</span>
                            <span class="od-list__meta">{{ $time }}</span>
                        </span>
                    </span>
                    @if($isUnread)
                        <span class="od-pill od-pill--urgent">{{ __('notifications_ui.new_badge', [], app()->getLocale()) }}</span>
                    @endif

                    @if($url)
                        <a href="{{ $url }}" class="odc-notif__stretch" aria-label="{{ $title }}"><span class="od-sr">{{ $title }}</span></a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @if($events->hasPages())
        <footer class="od-card__foot odc-pager">{{ $events->links() }}</footer>
    @endif

@endif
</section>

</div>
@endsection
