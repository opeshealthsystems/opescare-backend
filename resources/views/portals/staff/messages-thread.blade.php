@extends('layouts.portal')

@section('title', ($thread->title ?: __('messaging.conversation')) . ' — OpesCare')

@section('breadcrumb_home', __('messaging.staff_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ $thread->title ?: __('messaging.conversation') }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--{{ $thread->status === 'closed' ? 'neutral' : 'ready' }}">
                {{ $thread->status === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
            </span>
            @if($patient)
                {{ __('messaging.patient_label') }}: {{ trim($patient->first_name . ' ' . $patient->last_name) }}
                @if($patient->health_id)· <span class="odc-mono">{{ $patient->health_id }}</span>@endif
            @endif
        </p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.messages') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('messaging.thread_breadcrumb') }}
        </a>
    </div>
</header>

@if(session('success'))
<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('messaging.conversation') }} <span class="od-card__count">({{ $messages->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        @if($messages->isEmpty())
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><i data-lucide="message-square"></i></span>
                <p class="od-empty__text">{{ __('messaging.no_messages') }}</p>
            </div>
        @else
            <div class="odc-thread">
                @foreach($messages as $msg)
                    @php $mine = $msg['sender_id'] === $userId; @endphp
                    <div class="odc-msg {{ $mine ? 'odc-msg--mine' : '' }}">
                        <div class="odc-msg__meta">
                            {{ $mine ? __('messaging.you') : __('messaging.from_patient') }}
                            @if($msg['sent_at']) · {{ __('messaging.sent_at') }} {{ $msg['sent_at']->isoFormat('LLL') }}@endif
                        </div>
                        <div class="odc-prose odc-pre">{{ $msg['body'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="od-card">
    <div class="od-card__body">
        @if($thread->status === 'closed')
            <div class="odc-note odc-note--info">
                <i data-lucide="lock"></i><div>{{ __('messaging.closed_notice') }}</div>
            </div>
        @else
            <form method="POST" action="{{ route('portals.staff.messages.send', $thread->uuid) }}" class="odc-form">
                @csrf
                <label class="odc-label" for="body">{{ __('messaging.reply_label') }}
                    <textarea id="body" name="body" class="odc-textarea" rows="3" maxlength="5000"
                              placeholder="{{ __('messaging.reply_placeholder') }}" required>{{ old('body') }}</textarea>
                    @error('body')<span class="odc-error">{{ $message }}</span>@enderror
                </label>
                <div class="odc-actions">
                    <button type="submit" class="od-btn od-btn--primary">
                        <i data-lucide="send"></i> {{ __('messaging.send') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</section>

</div>
@endsection
