@extends('layouts.portal')

@section('title', __('messaging.inbox_title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head">
    <h1 class="od-page__title">{{ __('messaging.inbox_title') }}</h1>
    <p class="od-page__sub">{{ __('messaging.staff_inbox_subtitle') }}</p>
</header>

@if(session('success'))
<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('messaging.panel_title') }} <span class="od-card__count">({{ $threads->count() }})</span></h2>
    </header>
        @if($threads->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="message-square"></i></span>
                <div class="od-empty__title">{{ __('messaging.empty_title') }}</div>
                <p class="od-empty__text">{{ __('messaging.staff_empty_body') }}</p>
                <p class="od-empty__text">{{ __('hospital_desk.empty_messages_hint') }}</p>
            </div>
        @else
        <div class="od-card__body">
            <ul class="od-list">
                @foreach($threads as $row)
                    @php
                        $who = $row['patient_name'] ?: ($row['title'] ?: '');
                        $ini = collect(preg_split('/\s+/', trim((string) $who)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '—';
                        $tone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($who !== '' ? $who : (string) $row['uuid']) % 6];
                    @endphp
                    <li class="od-list__row {{ $row['unread'] ? 'odc-unread' : '' }}">
                        <span class="od-avatar od-tint--{{ $tone }}" aria-hidden="true">{{ $ini }}</span>
                        <span class="od-list__text">
                            <a href="{{ route('portals.staff.messages.show', $row['uuid']) }}" class="od-list__title">{{ $row['title'] ?: __('messaging.no_subject') }}</a>
                            @if($row['patient_name'])
                                <span class="od-list__sub">{{ __('messaging.patient_label') }}: {{ $row['patient_name'] }}@if($row['health_id']) · <span class="odc-mono">{{ $row['health_id'] }}</span>@endif</span>
                            @endif
                            @if($row['snippet'])
                                <span class="od-list__sub">{{ \Illuminate\Support\Str::limit($row['snippet'], 120) }}</span>
                            @endif
                        </span>
                        @if($row['unread'])
                            <span class="od-pill od-pill--dot od-pill--normal">{{ __('messaging.unread') }}</span>
                        @endif
                        <span class="od-pill od-pill--{{ $row['status'] === 'closed' ? 'neutral' : 'ready' }}">
                            {{ $row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
                        </span>
                        @if($row['last_at'])
                            <span class="od-list__meta">{{ $row['last_at']->isoFormat('LLL') }}</span>
                        @endif
                        <a href="{{ route('portals.staff.messages.show', $row['uuid']) }}" class="od-kebab" aria-label="{{ $row['title'] ?: __('messaging.no_subject') }}"><i data-lucide="chevron-right"></i></a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
</section>

</div>
@endsection
