@extends('layouts.portal')

@section('title', __('support.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('support.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('support.title') }}</h1>
        <p class="page-subtitle">{{ __('support.subtitle') }}</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-toggle-hidden="new-ticket">
            <i data-lucide="plus"></i> {{ __('support.create_ticket') }}
        </button>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-ticket" {{ $errors->any() || request('create') ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="life-buoy"></i> {{ __('support.create_ticket') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.support.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;">
                <div style="grid-column:1/-1;"><label class="form-label" for="subject">{{ __('support.col_subject') }}</label>
                    <input type="text" id="subject" name="subject" class="form-control" maxlength="200" value="{{ old('subject') }}" required></div>
                <div><label class="form-label" for="category">{{ __('support.col_category') }}</label>
                    <select id="category" name="category" class="form-control" required>
                        @foreach($categories as $c)<option value="{{ $c }}" @selected(old('category') === $c)>{{ __('support.category_' . $c) }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="priority">{{ __('support.col_priority') }}</label>
                    <select id="priority" name="priority" class="form-control" required>
                        @foreach(['normal', 'high', 'urgent', 'critical'] as $p)<option value="{{ $p }}" @selected(old('priority') === $p)>@enum($p, 'priority')</option>@endforeach
                    </select></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="description">{{ __('support.description') }}</label>
                    <textarea id="description" name="description" class="form-control" rows="4" minlength="10" maxlength="2000" required>{{ old('description') }}</textarea>
                    <p class="td-muted">{{ __('support.redaction_note') }}</p></div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('support.submit') }}</button></div>
        </form>
    </div>
</div>

<form method="GET" action="{{ route('portals.staff.support') }}" class="filter-bar">
    <select name="status" class="form-control" aria-label="{{ __('support.col_status') }}">
        <option value="">{{ __('support.all_statuses') }}</option>
        @foreach(['open', 'assigned', 'escalated', 'resolved'] as $st)
        <option value="{{ $st }}" @selected(request('status') === $st)>@enum($st)</option>
        @endforeach
    </select>
    <select name="priority" class="form-control" aria-label="{{ __('support.col_priority') }}">
        <option value="">{{ __('support.all_priorities') }}</option>
        @foreach(['critical', 'urgent', 'high', 'normal'] as $p)
        <option value="{{ $p }}" @selected(request('priority') === $p)>@enum($p, 'priority')</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('support.filter') }}</button>
    <a href="{{ route('portals.staff.support') }}" class="btn btn-ghost btn-sm">{{ __('support.clear') }}</a>
</form>

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="inbox"></i> {{ __('support.my_tickets') }}</h3></div>
    <div class="panel-body panel-body--flush">
        @if(count($tickets) === 0)
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <h3>{{ __('support.empty_title') }}</h3>
                <p>{{ __('support.empty_body') }}</p>
                <p class="td-muted">{{ __('hospital_desk.empty_support_hint') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('support.col_subject') }}</th>
                        <th>{{ __('support.col_priority') }}</th>
                        <th>{{ __('support.col_status') }}</th>
                        <th>{{ __('support.col_created') }}</th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        @php
                            $priorityBadge = match($ticket->priority) { 'critical' => 'badge-critical', 'urgent' => 'badge-danger', 'high' => 'badge-warning', default => 'badge-neutral' };
                            $statusBadge = match($ticket->status) { 'resolved' => 'badge-success', 'escalated' => 'badge-danger', 'assigned' => 'badge-teal', 'open' => 'badge-primary', default => 'badge-neutral' };
                        @endphp
                        <tr>
                            <td data-label="{{ __('support.col_subject') }}"><span class="td-strong">{{ $ticket->subject }}</span>
                                <div class="td-muted">{{ __('support.category_' . $ticket->category) }} · {{ trans_choice('support.messages_count', $ticket->messages_count, ['count' => $ticket->messages_count]) }}</div></td>
                            <td data-label="{{ __('support.col_priority') }}"><span class="badge {{ $priorityBadge }}">@enum($ticket->priority, 'priority')</span></td>
                            <td data-label="{{ __('support.col_status') }}"><span class="badge {{ $statusBadge }}">@enum($ticket->status)</span></td>
                            <td data-label="{{ __('support.col_created') }}">{{ $ticket->created_at?->isoFormat('ll') }}</td>
                            <td><a href="{{ route('portals.staff.support.show', $ticket->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="eye"></i> {{ __('support.view') }}</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="book-open"></i> {{ __('support.kb_title') }}</h3></div>
    <div class="panel-body">
        @forelse($articles as $article)
            <div><a href="{{ route('portals.staff.support.article', $article->slug) }}"><i data-lucide="file-text"></i> {{ $article->title }}</a></div>
        @empty
            <p class="td-muted">{{ __('support.kb_empty') }}</p>
        @endforelse
    </div>
</div>

@endsection
