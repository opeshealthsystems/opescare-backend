@extends('layouts.portal')

@section('title', __('notifications.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('notifications.title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner', ['viewingNote' => __('family.inbox_notifications_note', ['name' => request()->attributes->get('viewing_patient')?->first_name])])
@endsection

@section('content')
@php
    $readOnly    = $readOnly ?? false;
    $viewing     = $viewing ?? null;
    $viewingName = $viewing ? trim(($viewing->first_name ?? '') . ' ' . ($viewing->last_name ?? '')) : '';
@endphp

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
                @if($readOnly)
                <button type="button" class="f-btn f-btn--primary" disabled aria-disabled="true" aria-describedby="mark-read-disabled-reason" data-section="mark-read-disabled">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                    {{ __('notifications.mark_all_read') }}
                </button>
                @else
                <form method="POST" action="{{ route('portals.patient.notifications.read') }}">
                    @csrf
                    <button type="submit" class="f-btn f-btn--primary">
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                        {{ __('notifications.mark_all_read') }}
                    </button>
                </form>
                @endif
            @endif
        </div>
    </div>

    @if($readOnly)
    <div class="pf-alert" data-section="read-only-note">
        <i class="ods-d9ea87f" data-lucide="lock" aria-hidden="true"></i>
        <p id="mark-read-disabled-reason">{{ __('family.inbox_read_only_mark_read', ['name' => $viewingName]) }}</p>
    </div>
    @endif

    @if($events->isEmpty())
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'bell', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('notifications.empty_title') }}</h2>
        <p class="f-muted ods-1386d50">{{ __('notifications.empty_body') }}</p>
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
                    <p class="f-small f-muted ods-d24030b">{{ \Illuminate\Support\Str::limit($msg, 180) }}</p>
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
