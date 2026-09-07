@extends('layouts.portal')

@section('title', __('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications')
@section('breadcrumb_section', __('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications')

@section('head')
<style>
    /* Whole-row link overlay. .timeline-item is already position:relative,
       so the anchor stretches across the item and the entire row navigates. */
    .notif-stretch { position: absolute; inset: 0; z-index: 3; border-radius: var(--p-radius-md, 8px); }
    .notif-stretch:focus-visible { outline: 2px solid var(--p-primary); outline-offset: 2px; }
    .notif-item-link:hover .timeline-title { color: var(--p-primary); }
    .notif-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            {{ __('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications' }}
            @if($unread > 0)
                <span class="badge badge-danger badge-sm">{{ $unread }}</span>
            @endif
        </h1>
        <p class="page-subtitle">{{ __('notifications_ui.center_subtitle', [], app()->getLocale()) ?: 'Everything that needs your attention.' }}</p>
    </div>

    @if($events->total() > 0)
    <form method="POST" action="{{ route('notifications.read') }}">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="check-check"></i>
            {{ __('notifications_ui.mark_all_read', [], app()->getLocale()) ?: 'Mark all as read' }}
        </button>
    </form>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success mb-6">
    <i data-lucide="check-circle"></i>
    <div>{{ session('success') }}</div>
</div>
@endif

@if($events->total() === 0)

    <div class="panel">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="bell-off"></i></div>
            <h3>{{ __('notifications_ui.empty', [], app()->getLocale()) ?: 'You have no notifications yet.' }}</h3>
            <p>{{ __('notifications_ui.empty_desc', [], app()->getLocale()) ?: 'Orders, alerts and updates will appear here.' }}</p>
        </div>
    </div>

@else

    <div class="panel">
        <div class="panel-body">
            <div class="timeline">
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

                        // Dot colour keyed to category (empty = brand navy default).
                        $dot = match ($category) {
                            'blood'    => 'danger',
                            'pharmacy' => 'teal',
                            'admin'    => 'warning',
                            'account'  => 'neutral',
                            default    => '',
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

                    <div class="timeline-item {{ $url ? 'notif-item-link' : '' }}">
                        <div class="timeline-dot {{ $dot }}">
                            <i data-lucide="{{ $icon }}"></i>
                        </div>
                        <div class="timeline-body min-w-0">
                            <div class="flex-between">
                                <span class="timeline-title">{{ $title }}</span>
                                @if($isUnread)
                                    <span class="badge badge-danger badge-sm">{{ __('notifications_ui.new_badge', [], app()->getLocale()) ?: 'New' }}</span>
                                @endif
                            </div>

                            @if(trim((string) $body) !== '')
                                <div class="timeline-desc">{{ $body }}</div>
                            @endif

                            <div class="flex items-center gap-2" style="margin-top: 0.4rem;">
                                <span class="badge badge-neutral badge-sm">{{ __('notifications_ui.category_' . $catKey, [], app()->getLocale()) ?: \Illuminate\Support\Str::ucfirst($catKey) }}</span>
                                <span class="timeline-time">{{ $time }}</span>
                            </div>
                        </div>

                        @if($url)
                            <a href="{{ $url }}" class="notif-stretch" aria-label="{{ $title }}"><span class="notif-sr-only">{{ $title }}</span></a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if($events->hasPages())
            <div class="panel-body">{{ $events->links() }}</div>
        @endif
    </div>

@endif

@endsection
