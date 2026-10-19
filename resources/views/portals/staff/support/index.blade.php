@extends('layouts.portal')

@section('title', __('support.title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('support.title') }}</h1>
        <p class="od-page__sub">{{ __('support.subtitle') }}</p>
    </div>
    <div class="odc-head__actions">
        <button type="button" class="od-btn od-btn--primary" data-toggle-hidden="new-ticket">
            <i data-lucide="plus"></i> {{ __('support.create_ticket') }}
        </button>
    </div>
</header>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<section class="od-card" id="new-ticket" {{ $errors->any() || request('create') ? '' : 'hidden' }}>
    <header class="od-card__head"><h2 class="od-card__title">{{ __('support.create_ticket') }}</h2></header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.staff.support.store') }}" class="odc-form">
            @csrf
            <div class="odc-grid">
                <label class="odc-label odc-span" for="subject">{{ __('support.col_subject') }}
                    <input type="text" id="subject" name="subject" class="od-input" maxlength="200" value="{{ old('subject') }}" required></label>
                <label class="odc-label" for="category">{{ __('support.col_category') }}
                    <select id="category" name="category" class="od-select" required>
                        @foreach($categories as $c)<option value="{{ $c }}" @selected(old('category') === $c)>{{ __('support.category_' . $c) }}</option>@endforeach
                    </select></label>
                <label class="odc-label" for="priority">{{ __('support.col_priority') }}
                    <select id="priority" name="priority" class="od-select" required>
                        @foreach(['normal', 'high', 'urgent', 'critical'] as $p)<option value="{{ $p }}" @selected(old('priority') === $p)>@enum($p, 'priority')</option>@endforeach
                    </select></label>
                <label class="odc-label odc-span" for="description">{{ __('support.description') }}
                    <textarea id="description" name="description" class="odc-textarea" rows="4" minlength="10" maxlength="2000" required>{{ old('description') }}</textarea>
                    <span class="odc-hint">{{ __('support.redaction_note') }}</span></label>
            </div>
            <div class="odc-actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="send"></i> {{ __('support.submit') }}</button></div>
        </form>
    </div>
</section>

<div class="od-grid od-grid--main">
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('support.my_tickets') }} <span class="od-card__count">({{ count($tickets) }})</span></h2></header>
    <div class="od-card__body">
    <form method="GET" action="{{ route('portals.staff.support') }}" class="odc-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('support.col_status') }}">
            <option value="">{{ __('support.all_statuses') }}</option>
            @foreach(['open', 'assigned', 'escalated', 'resolved'] as $st)
            <option value="{{ $st }}" @selected(request('status') === $st)>@enum($st)</option>
            @endforeach
        </select>
        <select name="priority" class="od-select" aria-label="{{ __('support.col_priority') }}">
            <option value="">{{ __('support.all_priorities') }}</option>
            @foreach(['critical', 'urgent', 'high', 'normal'] as $p)
            <option value="{{ $p }}" @selected(request('priority') === $p)>@enum($p, 'priority')</option>
            @endforeach
        </select>
        <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('support.filter') }}</button>
        <a href="{{ route('portals.staff.support') }}" class="od-btn od-btn--ghost">{{ __('support.clear') }}</a>
    </form>

        @if(count($tickets) === 0)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="inbox"></i></span>
                <div class="od-empty__title">{{ __('support.empty_title') }}</div>
                <p class="od-empty__text">{{ __('support.empty_body') }}</p>
                <p class="od-empty__text">{{ __('hospital_desk.empty_support_hint') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table odc-stack">
                    <thead><tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('support.col_subject') }}</th>
                        <th>{{ __('support.col_priority') }}</th>
                        <th>{{ __('support.col_status') }}</th>
                        <th>{{ __('support.col_created') }}</th>
                        <th><span class="od-sr">{{ __('support.view') }}</span></th>
                    </tr></thead>
                    <tbody>
                        @foreach($tickets as $i => $ticket)
                        @php
                            $priorityPill = match($ticket->priority) { 'critical' => 'critical', 'urgent' => 'urgent', 'high' => 'pending', default => 'neutral' };
                            $statusPill = match($ticket->status) { 'resolved' => 'ready', 'escalated' => 'urgent', 'assigned' => 'teal', 'open' => 'normal', default => 'neutral' };
                        @endphp
                        <tr>
                            <td class="od-table__num">{{ $i + 1 }}</td>
                            <td data-label="{{ __('support.col_subject') }}"><a href="{{ route('portals.staff.support.show', $ticket->id) }}" class="od-table__name">{{ $ticket->subject }}</a>
                                <span class="od-table__sub">{{ __('support.category_' . $ticket->category) }} · {{ trans_choice('support.messages_count', $ticket->messages_count, ['count' => $ticket->messages_count]) }}</span></td>
                            <td data-label="{{ __('support.col_priority') }}"><span class="od-pill od-pill--dot od-pill--{{ $priorityPill }}">@enum($ticket->priority, 'priority')</span></td>
                            <td data-label="{{ __('support.col_status') }}"><span class="od-pill od-pill--{{ $statusPill }}">@enum($ticket->status)</span></td>
                            <td data-label="{{ __('support.col_created') }}" class="od-muted">{{ $ticket->created_at?->isoFormat('ll') }}</td>
                            <td class="od-table__actions"><a href="{{ route('portals.staff.support.show', $ticket->id) }}" class="od-btn od-btn--row">{{ __('support.view') }}</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('support.kb_title') }}</h2></header>
    @if($articles->isEmpty())
        <div class="od-empty od-empty--compact">
            <span class="od-empty__icon"><i data-lucide="book-open"></i></span>
            <p class="od-empty__text">{{ __('support.kb_empty') }}</p>
        </div>
    @else
    <div class="od-card__body">
        <ul class="od-list">
        @foreach($articles as $article)
            <li class="od-list__row">
                <span class="od-list__icon od-tint--blue"><i data-lucide="file-text"></i></span>
                <span class="od-list__text"><a href="{{ route('portals.staff.support.article', $article->slug) }}" class="od-list__title">{{ $article->title }}</a></span>
                <i data-lucide="chevron-right" class="od-muted"></i>
            </li>
        @endforeach
        </ul>
    </div>
    @endif
</section>
</div>

</div>
@endsection
