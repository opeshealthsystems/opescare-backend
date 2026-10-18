@extends('layouts.portal')

@section('title', $ticket->subject . ' — OpesCare')

@section('breadcrumb_home', __('support.title'))
@section('breadcrumb_home_url', route('portals.staff.support'))
@section('breadcrumb_section', __('support.ticket'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $ticket->subject }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-neutral">{{ __('support.category_' . $ticket->category) }}</span>
            <span class="badge badge-primary">@enum($ticket->status)</span>
            <span class="badge badge-warning">@enum($ticket->priority, 'priority')</span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.staff.support') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="message-square"></i> {{ __('support.description') }}</h3></div>
    <div class="panel-body" style="white-space:pre-line;">{{ $ticket->description_redacted }}</div>
</div>

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="messages-square"></i> {{ __('support.thread') }}</h3></div>
    <div class="panel-body">
        @forelse($messages as $message)
            <div class="thread-item {{ ! $loop->last ? 'thread-item--bordered' : '' }}">
                <div class="thread-item__head">
                    <span class="kv-strong">{{ $message->sender_type === 'staff' ? __('support.sender_you') : __('support.sender_support') }}</span>
                    <span class="td-muted">{{ $message->created_at?->isoFormat('lll') }}</span>
                </div>
                <div class="thread-item__body" style="white-space:pre-line;">{{ $message->body_redacted }}</div>
            </div>
        @empty
            <p class="td-muted">{{ __('support.no_replies') }}</p>
        @endforelse
    </div>
</div>

@if($ticket->status !== 'resolved')
<div class="panel mb-4">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.support.reply', $ticket->id) }}">
            @csrf
            <label class="form-label" for="body">{{ __('support.reply') }}</label>
            <textarea id="body" name="body" class="form-control" rows="3" minlength="2" maxlength="2000" required></textarea>
            <div style="margin-top:.75rem;"><button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('support.send_reply') }}</button></div>
        </form>
        <form method="POST" action="{{ route('portals.staff.support.close', $ticket->id) }}" style="margin-top:1rem;" data-confirm="{{ __('hospital_desk.confirm_support_close') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="check-circle-2"></i> {{ __('support.mark_resolved') }}</button>
        </form>
    </div>
</div>
@else
<div class="alert alert-info"><i data-lucide="check-circle-2"></i><div>{{ __('support.resolved_note') }} @if($ticket->resolution_note)— {{ $ticket->resolution_note }}@endif</div></div>
@endif

@endsection
