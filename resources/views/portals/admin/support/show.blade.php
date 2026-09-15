@extends('layouts.portal')

@section('title', $ticket->subject . ' — OpesCare')

@section('breadcrumb_home', __('support.admin_title'))
@section('breadcrumb_home_url', route('portals.admin.support.index'))
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
        <a href="{{ route('portals.admin.support.index') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));gap:1rem;align-items:start;">
<div>
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
                        <span class="kv-strong">{{ $people[$message->sender_id] ?? __('support.sender_support') }}</span>
                        @if($message->internal)<span class="badge badge-warning">{{ __('support.internal') }}</span>@endif
                        <span class="td-muted">{{ $message->created_at?->isoFormat('lll') }}</span>
                    </div>
                    <div class="thread-item__body" style="white-space:pre-line;">{{ $message->body_redacted }}</div>
                </div>
            @empty
                <p class="td-muted">{{ __('support.no_replies') }}</p>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.support.reply', $ticket->id) }}">
                @csrf
                <label class="form-label" for="body">{{ __('support.reply') }}</label>
                <textarea id="body" name="body" class="form-control" rows="3" minlength="2" maxlength="2000" required></textarea>
                <label style="display:flex;gap:.4rem;align-items:center;margin-top:.5rem;"><input type="checkbox" name="internal" value="1"> {{ __('support.internal_note') }}</label>
                <div style="margin-top:.75rem;"><button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('support.send_reply') }}</button></div>
            </form>
        </div>
    </div>
</div>

<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('support.details') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('support.col_facility') }}</td><td class="kv-strong">{{ $facility?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('support.requester') }}</td><td class="kv-strong">{{ $people[$ticket->requester_id] ?? '—' }}</td></tr>
                <tr><td>{{ __('support.assignee') }}</td><td class="kv-strong">{{ $people[$ticket->assigned_to] ?? __('support.unassigned') }}</td></tr>
                <tr><td>{{ __('support.col_created') }}</td><td class="kv-strong">{{ $ticket->created_at?->isoFormat('lll') }}</td></tr>
                <tr><td>{{ __('support.sla_due') }}</td><td class="kv-strong">{{ $ticket->sla_due_at?->isoFormat('lll') ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user-check"></i> {{ __('support.assign') }}</h3></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.support.assign', $ticket->id) }}">
                @csrf
                <select name="assignee_id" class="form-control" required aria-label="{{ __('support.assignee') }}">
                    <option value="">—</option>
                    @foreach($assignees as $person)
                    <option value="{{ $person->id }}" @selected((string) $ticket->assigned_to === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
                <div style="margin-top:.5rem;"><button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="check"></i> {{ __('support.assign') }}</button></div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body">
            @if($ticket->status !== 'resolved')
            <form method="POST" action="{{ route('portals.admin.support.close', $ticket->id) }}">
                @csrf
                <label class="form-label" for="resolution_note">{{ __('support.resolution_note') }}</label>
                <textarea id="resolution_note" name="resolution_note" class="form-control" rows="2" maxlength="1000"></textarea>
                <div style="margin-top:.5rem;"><button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check-circle-2"></i> {{ __('support.mark_resolved') }}</button></div>
            </form>
            @else
            <form method="POST" action="{{ route('portals.admin.support.reopen', $ticket->id) }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="refresh-cw"></i> {{ __('support.reopen') }}</button>
            </form>
            @endif
        </div>
    </div>
</div>
</div>

@endsection
