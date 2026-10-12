@extends('layouts.portal')

@section('title', __('messaging.compose_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.compose_title'))

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
    $locked      = $readOnly || !$hasTeam;
@endphp

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('messaging.compose_title') }}</h1>
            <p class="pf-sub">{{ __('messaging.compose_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.messages') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('messaging.thread_breadcrumb') }}
        </a>
    </div>

    @if($readOnly)
    {{-- Read-only guardian: the composer is shown but cannot be used — the reason is right above it. --}}
    <div class="pf-alert" data-section="read-only-note">
        <i data-lucide="lock" aria-hidden="true" style="width:18px;height:18px;flex:none;margin-top:2px;"></i>
        <p id="compose-disabled-reason">{{ __('family.inbox_read_only_compose', ['name' => $viewingName]) }}</p>
    </div>
    @elseif($viewing)
    <div class="pf-alert" data-section="on-behalf-note">
        @include('partials.finder.icon', ['name' => 'users', 'size' => 18])
        <p>{{ __('family.inbox_on_behalf', ['name' => $viewingName]) }}</p>
    </div>
    @endif

    @unless($hasTeam)
    <div class="pf-alert">
        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
        <p>{{ __('messaging.no_care_team') }}</p>
    </div>
    @endunless

    <form method="POST" action="{{ route('portals.patient.messages.store') }}" class="f-card" @if($readOnly) data-section="composer-disabled" aria-describedby="compose-disabled-reason" @endif>
        @csrf

        <div class="f-field">
            <label class="f-label" for="title">{{ __('messaging.compose_subject') }}</label>
            <input type="text" id="title" name="title" class="f-input" maxlength="150"
                   value="{{ old('title') }}" required @disabled($readOnly)>
            @error('title')<p class="f-error">{{ $message }}</p>@enderror
        </div>

        <div class="f-field">
            <label class="f-label" for="context_type">{{ __('messaging.compose_about') }}</label>
            <select id="context_type" name="context_type" class="f-input" data-call-change="pfFilterCtx" @disabled($readOnly)>
                <option value="">{{ __('messaging.compose_about_none') }}</option>
                <option value="appointment">{{ __('messaging.ctx_appt') }}</option>
                <option value="lab_result">{{ __('messaging.ctx_lab') }}</option>
                <option value="prescription">{{ __('messaging.ctx_rx') }}</option>
                <option value="visit">{{ __('messaging.ctx_visit') }}</option>
                <option value="insurance_policy">{{ __('messaging.ctx_insurance') }}</option>
            </select>
            <p class="f-hint">{{ __('messaging.compose_about_hint') }}</p>
        </div>

        <div class="f-field" id="ctx-item-wrap" style="display:none;">
            <label class="f-label" for="context_id">{{ __('messaging.compose_select_item') }}</label>
            <select id="context_id" name="context_id" class="f-input" @disabled($readOnly)>
                <option value="">—</option>
                @foreach($catalog as $type => $items)
                    @foreach($items as $item)
                        <option value="{{ $item['id'] }}" data-ctx="{{ $type }}" style="display:none;">{{ $item['label'] }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="f-field">
            <label class="f-label" for="body">{{ __('messaging.compose_message') }}</label>
            <textarea id="body" name="body" class="f-input" rows="5" maxlength="5000"
                      placeholder="{{ __('messaging.reply_placeholder') }}" required @disabled($readOnly)>{{ old('body') }}</textarea>
            @error('body')<p class="f-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;" @disabled($locked) @if($locked) aria-disabled="true" @endif>
            {{ __('messaging.compose_send') }}
            @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
        </button>
    </form>
</div>

<script @cspNonce>
function pfFilterCtx(type) {
    var wrap = document.getElementById('ctx-item-wrap');
    var sel  = document.getElementById('context_id');
    if (!type) { wrap.style.display = 'none'; sel.value = ''; return; }
    wrap.style.display = '';
    sel.value = '';
    Array.prototype.forEach.call(sel.options, function (o) {
        if (!o.value) { return; }
        o.style.display = (o.getAttribute('data-ctx') === type) ? '' : 'none';
    });
}
</script>

@endsection
