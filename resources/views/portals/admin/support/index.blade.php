@extends('layouts.portal')

@section('title', __('support.admin_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('support.admin_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('support.admin_title') }}</h1>
        <p class="page-subtitle">{{ __('support.admin_subtitle') }}</p>
    </div>
</div>

<div class="stats-grid mb-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:1rem;">
    <div class="panel"><div class="panel-body"><div class="td-muted">{{ __('support.stat_total') }}</div><div class="td-strong">{{ $stats['total'] }}</div></div></div>
    <div class="panel"><div class="panel-body"><div class="td-muted">{{ __('support.stat_open') }}</div><div class="td-strong">{{ $stats['open'] }}</div></div></div>
    <div class="panel"><div class="panel-body"><div class="td-muted">{{ __('support.stat_resolved') }}</div><div class="td-strong">{{ $stats['resolved'] }}</div></div></div>
</div>

<form method="GET" action="{{ route('portals.admin.support.index') }}" class="filter-bar">
    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('support.search_placeholder') }}" aria-label="{{ __('support.search_placeholder') }}">
    <select name="status" class="form-control" aria-label="{{ __('support.col_status') }}">
        <option value="">{{ __('support.all_statuses') }}</option>
        @foreach(['open', 'assigned', 'escalated', 'resolved'] as $st)
        <option value="{{ $st }}" @selected(request('status') === $st)>@enum($st)</option>
        @endforeach
    </select>
    <select name="priority" class="form-control" aria-label="{{ __('support.col_priority') }}">
        <option value="">{{ __('support.all_priorities') }}</option>
        @foreach(['critical', 'urgent', 'high', 'normal', 'low'] as $p)
        <option value="{{ $p }}" @selected(request('priority') === $p)>@enum($p, 'priority')</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('support.filter') }}</button>
    <a href="{{ route('portals.admin.support.index') }}" class="btn btn-ghost btn-sm">{{ __('support.clear') }}</a>
</form>

<div class="panel">
    @if($tickets->isEmpty())
        <div class="empty-state"><i data-lucide="inbox"></i><h3>{{ __('support.empty_title') }}</h3><p>{{ __('support.admin_empty_body') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
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
                <tr>
                    <td data-label="{{ __('support.col_subject') }}"><span class="td-strong">{{ $ticket->subject }}</span>
                        <div class="td-muted">{{ __('support.category_' . $ticket->category) }} · {{ trans_choice('support.messages_count', $ticket->messages_count, ['count' => $ticket->messages_count]) }}</div></td>
                    <td data-label="{{ __('support.col_facility') }}"><span class="td-muted">{{ $facilities[$ticket->facility_id] ?? '—' }}</span></td>
                    <td data-label="{{ __('support.col_priority') }}"><span class="badge badge-warning">@enum($ticket->priority, 'priority')</span></td>
                    <td data-label="{{ __('support.col_status') }}"><span class="badge badge-primary">@enum($ticket->status)</span></td>
                    <td data-label="{{ __('support.col_created') }}">{{ $ticket->created_at?->isoFormat('ll') }}</td>
                    <td><a href="{{ route('portals.admin.support.show', $ticket->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="eye"></i> {{ __('support.view') }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $tickets->links() }}</div>
    @endif
</div>

@endsection
