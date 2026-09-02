@extends('layouts.portal')

@section('title', __('notifications.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('notifications.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('notifications.title') }}</h1>
        <p class="page-subtitle">{{ __('notifications.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portals.patient.notifications.preferences') }}" class="btn btn-secondary">
            <i data-lucide="sliders-horizontal"></i> {{ __('notifications.preferences') }}
        </a>
        @if($unread > 0)
        <form method="POST" action="{{ route('portals.patient.notifications.read') }}">
            @csrf
            <button type="submit" class="btn btn-primary"><i data-lucide="check-check"></i> {{ __('notifications.mark_all_read') }}</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="bell"></i> {{ __('notifications.title') }}</h3>
        @if($unread > 0)<span class="badge badge-primary">{{ $unread }} {{ __('notifications.unread') }}</span>@endif
    </div>
    <div class="panel-body">
        @if($events->isEmpty())
            <div class="empty-state">
                <i data-lucide="bell-off"></i>
                <h3>{{ __('notifications.empty_title') }}</h3>
                <p>{{ __('notifications.empty_body') }}</p>
            </div>
        @else
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;">
                @foreach($events as $ev)
                    @php
                        $p = json_decode($ev->payload_json ?? '{}', true) ?: [];
                        $msg = $p['message'] ?? $p['body'] ?? $p['title'] ?? ucfirst(str_replace('_', ' ', $ev->event_type));
                        $createdAt = $ev->created_at ? \Illuminate\Support\Carbon::parse($ev->created_at) : null;
                        $isUnread = $seenAt === null || ($createdAt && $createdAt->gt($seenAt));
                    @endphp
                    <li class="panel" style="margin:0;padding:.85rem 1rem;border-left:4px solid {{ $isUnread ? '#0F4C81' : 'transparent' }};">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                            <div style="min-width:0;">
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <strong>@enum($ev->event_type)</strong>
                                    @if($isUnread)<span class="badge badge-primary">{{ __('notifications.new') }}</span>@endif
                                    @if(($ev->priority ?? 'normal') !== 'normal')<span class="badge badge-warning">@enum($ev->priority, 'priority')</span>@endif
                                </div>
                                <p class="page-subtitle" style="margin:.3rem 0 0;">{{ \Illuminate\Support\Str::limit($msg, 180) }}</p>
                            </div>
                            <span class="page-subtitle" style="font-size:.78rem;white-space:nowrap;">{{ $createdAt?->isoFormat('LLL') }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div style="margin-top:1rem;">{{ $events->links() }}</div>
        @endif
    </div>
</div>

@endsection
