@extends('layouts.portal')

@section('title', $ticket->subject . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ $ticket->subject }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--neutral">{{ __('support.category_' . $ticket->category) }}</span>
            <span class="od-pill od-pill--normal">@enum($ticket->status)</span>
            <span class="od-pill od-pill--dot od-pill--pending">@enum($ticket->priority, 'priority')</span>
        </p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.support') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</header>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="od-grid od-grid--main">
<div class="od-stack">
    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('support.description') }}</h2></header>
        <div class="od-card__body odc-prose odc-preline">{{ $ticket->description_redacted }}</div>
    </section>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('support.thread') }} <span class="od-card__count">({{ count($messages) }})</span></h2></header>
        <div class="od-card__body">
            @if(count($messages) === 0)
                <p class="od-muted">{{ __('support.no_replies') }}</p>
            @else
            <div class="odc-thread">
                @foreach($messages as $message)
                    @php $mine = $message->sender_type === 'staff'; @endphp
                    <div class="odc-msg {{ $mine ? 'odc-msg--mine' : '' }}">
                        <div class="odc-msg__meta">{{ $mine ? __('support.sender_you') : __('support.sender_support') }} · {{ $message->created_at?->isoFormat('lll') }}</div>
                        <div class="odc-prose odc-preline">{{ $message->body_redacted }}</div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>
</div>

<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('support.reply') }}</h2></header>
    <div class="od-card__body od-stack">
    @if($ticket->status !== 'resolved')
        <form method="POST" action="{{ route('portals.staff.support.reply', $ticket->id) }}" class="odc-form">
            @csrf
            <label class="odc-label" for="body">{{ __('support.reply') }}
                <textarea id="body" name="body" class="odc-textarea" rows="4" minlength="2" maxlength="2000" required></textarea>
            </label>
            <div class="odc-actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="send"></i> {{ __('support.send_reply') }}</button></div>
        </form>
        <form method="POST" action="{{ route('portals.staff.support.close', $ticket->id) }}" data-confirm="{{ __('hospital_desk.confirm_support_close') }}" class="odc-actions">
            @csrf
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="check-circle-2"></i> {{ __('support.mark_resolved') }}</button>
        </form>
    @else
        <div class="odc-note odc-note--info"><i data-lucide="check-circle-2"></i><div>{{ __('support.resolved_note') }} @if($ticket->resolution_note)— {{ $ticket->resolution_note }}@endif</div></div>
    @endif
    </div>
</section>
</div>

</div>
@endsection
