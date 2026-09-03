@extends('layouts.portal')

@section('title', __('notifications.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('notifications.title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('notifications.title') }}
                @if($unread > 0)<span class="f-badge">{{ $unread }} {{ __('notifications.unread') }}</span>@endif
            </h1>
            <p class="pf-sub">{{ __('notifications.subtitle') }}</p>
        </div>
        <div class="pf-actions">
            <a href="{{ route('portals.patient.notifications.preferences') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'filter', 'size' => 16])
                {{ __('notifications.preferences') }}
            </a>
            @if($unread > 0)
            <form method="POST" action="{{ route('portals.patient.notifications.read') }}">
                @csrf
                <button type="submit" class="f-btn f-btn--primary">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                    {{ __('notifications.mark_all_read') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    @if($events->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'bell', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('notifications.empty_title') }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('notifications.empty_body') }}</p>
    </div>
    @else
    <div class="pf-rows">
        @foreach($events as $ev)
            @php
                $p = json_decode($ev->payload_json ?? '{}', true) ?: [];
                $msg = $p['message'] ?? $p['body'] ?? $p['title'] ?? ucfirst(str_replace('_', ' ', $ev->event_type));
                $createdAt = $ev->created_at ? \Illuminate\Support\Carbon::parse($ev->created_at) : null;
                $isUnread = $seenAt === null || ($createdAt && $createdAt->gt($seenAt));
            @endphp
            <div class="f-card pf-row {{ $isUnread ? 'pf-unread' : '' }}">
                <div class="pf-row__body">
                    <div class="pf-row__title">
                        @enum($ev->event_type)
                        @if($isUnread)<span class="f-badge">{{ __('notifications.new') }}</span>@endif
                        @if(($ev->priority ?? 'normal') !== 'normal')<span class="f-badge f-badge--warn">@enum($ev->priority, 'priority')</span>@endif
                    </div>
                    <p class="f-small f-muted" style="margin:2px 0 0;white-space:normal;">{{ \Illuminate\Support\Str::limit($msg, 180) }}</p>
                </div>
                <div class="pf-row__aside">
                    <span>{{ $createdAt?->isoFormat('D MMM, HH:mm') }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <div class="pf-pagination">{{ $events->links() }}</div>
    @endif
</div>

@endsection
