@extends('layouts.portal')

@section('title', $ticket->subject . ' — OpesCare')

@section('breadcrumb_home', __('support.admin_title'))
@section('breadcrumb_home_url', route('portals.admin.support.index'))
@section('breadcrumb_section', __('support.ticket'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
    $gdPrio  = fn ($p) => match ($p) { 'critical', 'urgent' => 'od-pill--urgent', 'high' => 'od-pill--pending', 'low' => 'od-pill--neutral', default => 'od-pill--normal' };
    $gdStat  = fn ($s) => match ($s) { 'open' => 'od-pill--pending', 'assigned' => 'od-pill--normal', 'escalated' => 'od-pill--urgent', 'resolved' => 'od-pill--ready', default => 'od-pill--neutral' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $ticket->subject }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--neutral">{{ __('support.category_' . $ticket->category) }}</span>
            <span class="od-pill {{ $gdStat($ticket->status) }}">@enum($ticket->status)</span>
            <span class="od-pill {{ $gdPrio($ticket->priority) }}">@enum($ticket->priority, 'priority')</span>
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.support.index') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="oda-split">
<div class="od-stack">
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="message-square"></i> {{ __('support.description') }}</h2></div>
        <div class="od-card__body"><p class="oda-gd-text oda-gd-prewrap">{{ $ticket->description_redacted }}</p></div>
    </div>

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="messages-square"></i> {{ __('support.thread') }}</h2>
            <span class="od-card__count">{{ count($messages) }}</span>
        </div>
        <div class="od-card__body">
            @forelse($messages as $message)
                @php $sender = $people[$message->sender_id] ?? __('support.sender_support'); @endphp
                <div class="oda-gd-thread">
                    <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($message->sender_id ?? 'support') }}">{{ $gdInit($sender) }}</span>
                    <div class="oda-gd-thread__main">
                        <div class="oda-gd-thread__head">
                            <span class="od-table__name">{{ $sender }}</span>
                            @if($message->internal)<span class="od-pill od-pill--pending">{{ __('support.internal') }}</span>@endif
                            <span class="od-table__sub">{{ $message->created_at?->isoFormat('lll') }}</span>
                        </div>
                        <p class="oda-gd-text oda-gd-prewrap">{{ $message->body_redacted }}</p>
                    </div>
                </div>
            @empty
                <div class="od-empty"><p class="od-empty__text">{{ __('support.no_replies') }}</p></div>
            @endforelse
        </div>
    </div>

    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="reply"></i> {{ __('support.reply') }}</h2></div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.support.reply', $ticket->id) }}" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full" for="body"><span class="oda-label__text">{{ __('support.reply') }}</span>
                    <textarea id="body" name="body" class="oda-textarea" rows="3" minlength="2" maxlength="2000" required></textarea></label>
                <label class="oda-check oda-form__full"><input type="checkbox" name="internal" value="1"> {{ __('support.internal_note') }}</label>
                <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="send"></i> {{ __('support.send_reply') }}</button></div>
            </form>
        </div>
    </div>
</div>

<div class="od-stack">
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="info"></i> {{ __('support.details') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('support.col_facility') }}</dt><dd>{{ $facility?->name ?? '—' }}</dd>
                <dt>{{ __('support.requester') }}</dt><dd>{{ $people[$ticket->requester_id] ?? '—' }}</dd>
                <dt>{{ __('support.assignee') }}</dt><dd>{{ $people[$ticket->assigned_to] ?? __('support.unassigned') }}</dd>
                <dt>{{ __('support.col_created') }}</dt><dd>{{ $ticket->created_at?->isoFormat('lll') }}</dd>
                <dt>{{ __('support.sla_due') }}</dt><dd>{{ $ticket->sla_due_at?->isoFormat('lll') ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="user-check"></i> {{ __('support.assign') }}</h2></div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.support.assign', $ticket->id) }}" class="oda-form">
                @csrf
                <select name="assignee_id" class="od-select oda-form__full" required aria-label="{{ __('support.assignee') }}">
                    <option value="">—</option>
                    @foreach($assignees as $person)
                    <option value="{{ $person->id }}" @selected((string) $ticket->assigned_to === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
                <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="check"></i> {{ __('support.assign') }}</button></div>
            </form>
        </div>
    </div>

    <div class="od-card">
        <div class="od-card__body">
            @if($ticket->status !== 'resolved')
            <form method="POST" action="{{ route('portals.admin.support.close', $ticket->id) }}" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full" for="resolution_note"><span class="oda-label__text">{{ __('support.resolution_note') }}</span>
                    <textarea id="resolution_note" name="resolution_note" class="oda-textarea" rows="2" maxlength="1000"></textarea></label>
                <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="check-circle-2"></i> {{ __('support.mark_resolved') }}</button></div>
            </form>
            @else
            <form method="POST" action="{{ route('portals.admin.support.reopen', $ticket->id) }}" class="oda-inline-form">
                @csrf
                <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="refresh-cw"></i> {{ __('support.reopen') }}</button>
            </form>
            @endif
        </div>
    </div>
</div>
</div>

</div>
@endsection
