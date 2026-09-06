@extends('layouts.portal')

@section('title', ($thread->title ?: __('messaging.conversation')) . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner', ['viewingNote' => __('family.inbox_messages_note', ['name' => request()->attributes->get('viewing_patient')?->first_name])])
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
            <h1>{{ $thread->title ?: __('messaging.conversation') }}</h1>
            <p class="pf-sub">
                <span class="f-badge {{ $thread->status === 'closed' ? 'pf-badge--muted' : 'f-badge--good' }}">
                    {{ $thread->status === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
                </span>
            </p>
        </div>
        <a href="{{ route('portals.patient.messages') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('messaging.thread_breadcrumb') }}
        </a>
    </div>

    @if(!empty($context))
    <div class="pf-alert">
        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
        <p><span class="f-small" style="text-transform:uppercase;letter-spacing:.04em;font-weight:700;display:block;">{{ __('messaging.ctx_about') }}</span>
        <strong>{{ $context['label'] }}</strong></p>
    </div>
    @endif

    <div class="f-card" style="margin-bottom:12px;">
        @if($messages->isEmpty())
            <p class="f-muted" style="text-align:center;margin:20px 0;">{{ __('messaging.no_messages') }}</p>
        @else
            <div class="pf-msgs">
                @foreach($messages as $msg)
                    @php $mine = $msg['mine'] ?? ($msg['sender_id'] === $userId); @endphp
                    <div class="pf-msg {{ $mine ? 'pf-msg--mine' : '' }}">
                        <div class="pf-msg__who">{{ $msg['who'] ?? ($mine ? __('messaging.you') : __('messaging.participant')) }}</div>
                        <div class="pf-msg__body">{{ $msg['body'] }}</div>
                        @if($msg['sent_at'])
                            <div class="pf-msg__at">{{ $msg['sent_at']->isoFormat('LLL') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($thread->status === 'closed')
        <div class="pf-alert">
            @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
            <p>{{ __('messaging.closed_notice') }}</p>
        </div>
    @elseif($readOnly)
        {{-- Read-only guardian: the composer is shown but cannot be used — the reason is right above it. --}}
        <div class="pf-alert" data-section="read-only-note">
            <i data-lucide="lock" aria-hidden="true" style="width:18px;height:18px;flex:none;margin-top:2px;"></i>
            <p id="reply-disabled-reason">{{ __('family.inbox_read_only_compose', ['name' => $viewingName]) }}</p>
        </div>
        <div class="f-card" data-section="composer-disabled">
            <div class="f-field" style="margin-bottom:12px;">
                <label class="f-label" for="body">{{ __('messaging.reply_label') }}</label>
                <textarea id="body" name="body" class="f-input" rows="3" maxlength="5000"
                          placeholder="{{ __('messaging.reply_placeholder') }}" disabled aria-disabled="true" aria-describedby="reply-disabled-reason"></textarea>
            </div>
            <button type="button" class="f-btn f-btn--primary" style="min-height:48px;" disabled aria-disabled="true">
                {{ __('messaging.send') }}
                @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
            </button>
        </div>
    @else
        <form method="POST" action="{{ route('portals.patient.messages.send', $thread->uuid) }}" class="f-card">
            @csrf
            @if($viewing)
            <p class="pf-alert" style="margin-bottom:12px;" data-section="on-behalf-note">
                @include('partials.finder.icon', ['name' => 'users', 'size' => 18])
                <span>{{ __('family.inbox_on_behalf', ['name' => $viewingName]) }}</span>
            </p>
            @endif
            <div class="f-field" style="margin-bottom:12px;">
                <label class="f-label" for="body">{{ __('messaging.reply_label') }}</label>
                <textarea id="body" name="body" class="f-input" rows="3" maxlength="5000"
                          placeholder="{{ __('messaging.reply_placeholder') }}" required>{{ old('body') }}</textarea>
                @error('body')<p class="f-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                {{ __('messaging.send') }}
                @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
            </button>
        </form>
    @endif
</div>

@endsection
