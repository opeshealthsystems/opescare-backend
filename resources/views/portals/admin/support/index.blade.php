@extends('layouts.portal')

@section('title', __('support.admin_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('support.admin_title'))
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
        <h1 class="od-page__title">{{ __('support.admin_title') }}</h1>
        <p class="od-page__sub">{{ __('support.admin_subtitle') }}</p>
    </div>
</div>

<div class="od-kpis oda-gd-kpis-3">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="life-buoy"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('support.stat_total') }}</span><span class="od-kpi__value">{{ $stats['total'] }}</span></span></div>
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="inbox"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('support.stat_open') }}</span><span class="od-kpi__value">{{ $stats['open'] }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="check-circle-2"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('support.stat_resolved') }}</span><span class="od-kpi__value">{{ $stats['resolved'] }}</span></span></div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="life-buoy"></i> {{ __('support.admin_title') }}</h2>
        <span class="od-card__count">{{ method_exists($tickets, 'total') ? $tickets->total() : $tickets->count() }}</span>
    </div>
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.support.index') }}" class="oda-toolbar">
            <label class="od-field"><i data-lucide="search"></i>
                <input type="search" name="search" class="od-input" value="{{ request('search') }}" placeholder="{{ __('support.search_placeholder') }}" aria-label="{{ __('support.search_placeholder') }}"></label>
            <select name="status" class="od-select" aria-label="{{ __('support.col_status') }}">
                <option value="">{{ __('support.all_statuses') }}</option>
                @foreach(['open', 'assigned', 'escalated', 'resolved'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>@enum($st)</option>
                @endforeach
            </select>
            <select name="priority" class="od-select" aria-label="{{ __('support.col_priority') }}">
                <option value="">{{ __('support.all_priorities') }}</option>
                @foreach(['critical', 'urgent', 'high', 'normal', 'low'] as $p)
                <option value="{{ $p }}" @selected(request('priority') === $p)>@enum($p, 'priority')</option>
                @endforeach
            </select>
            <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('support.filter') }}</button>
            <a href="{{ route('portals.admin.support.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('support.clear') }}</a>
        </form>
    </div>
    @if($tickets->isEmpty())
        <div class="od-card__body"><div class="od-empty"><span class="od-empty__icon"><i data-lucide="inbox"></i></span><h3 class="od-empty__title">{{ __('support.empty_title') }}</h3><p class="od-empty__text">{{ __('support.admin_empty_body') }}</p></div></div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('support.col_subject') }}</th>
                    <th>{{ __('support.col_facility') }}</th>
                    <th>{{ __('support.col_priority') }}</th>
                    <th>{{ __('support.col_status') }}</th>
                    <th>{{ __('support.col_created') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    @php $facName = $facilities[$ticket->facility_id] ?? '—'; @endphp
                    <tr>
                        <td data-label="{{ __('support.col_subject') }}"><span class="od-table__name">{{ $ticket->subject }}</span>
                            <div class="od-table__sub">{{ __('support.category_' . $ticket->category) }} · {{ trans_choice('support.messages_count', $ticket->messages_count, ['count' => $ticket->messages_count]) }}</div></td>
                        <td data-label="{{ __('support.col_facility') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($ticket->facility_id) }}">{{ $gdInit($facName) }}</span>
                                <span class="od-table__sub">{{ $facName }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('support.col_priority') }}"><span class="od-pill {{ $gdPrio($ticket->priority) }}">@enum($ticket->priority, 'priority')</span></td>
                        <td data-label="{{ __('support.col_status') }}"><span class="od-pill {{ $gdStat($ticket->status) }}">@enum($ticket->status)</span></td>
                        <td data-label="{{ __('support.col_created') }}">{{ $ticket->created_at?->isoFormat('ll') }}</td>
                        <td><a href="{{ route('portals.admin.support.show', $ticket->id) }}" class="od-btn od-btn--row"><i data-lucide="eye"></i> {{ __('support.view') }}</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $tickets->links() }}</div>
    @endif
</div>

</div>
@endsection
