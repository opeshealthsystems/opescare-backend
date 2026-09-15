@extends('layouts.portal')

@section('title', $broadcast->title . ' — OpesCare')

@section('breadcrumb_home', __('broadcasts.title'))
@section('breadcrumb_home_url', route('portals.admin.broadcasts'))
@section('breadcrumb_section', __('broadcasts.acks_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $broadcast->title }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-neutral">{{ __('broadcasts.type_' . $broadcast->broadcast_type) }}</span>
            <span class="badge badge-primary">@enum($broadcast->status)</span>
            <span class="td-muted">{{ __('broadcasts.audience_' . $broadcast->target_type) }}</span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.broadcasts') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('broadcasts.back') }}</a>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body" style="white-space:pre-line;">{{ $broadcast->body }}</div>
    <div class="panel-body td-muted">
        {{ __('broadcasts.field_publish_at') }}: {{ $broadcast->publish_at?->isoFormat('lll') ?? '—' }}
        · {{ __('broadcasts.field_expires') }}: {{ $broadcast->expires_at?->isoFormat('lll') ?? '—' }}
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="list-checks"></i> {{ __('broadcasts.acks_title') }} <span class="badge badge-primary">{{ $acks->count() }}</span></h3></div>
    @if(! $broadcast->requires_acknowledgement)
        <div class="empty-state"><p>{{ __('broadcasts.ack_not_required') }}</p></div>
    @elseif($acks->isEmpty())
        <div class="empty-state"><i data-lucide="inbox"></i><p>{{ __('broadcasts.acks_empty') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>{{ __('broadcasts.col_user') }}</th><th>{{ __('broadcasts.col_facility') }}</th><th>{{ __('broadcasts.col_acknowledged_at') }}</th></tr></thead>
            <tbody>
                @foreach($acks as $ack)
                <tr>
                    <td data-label="{{ __('broadcasts.col_user') }}">{{ $users[$ack->user_id]->name ?? '—' }}</td>
                    <td data-label="{{ __('broadcasts.col_facility') }}">{{ $facilities[$ack->facility_id] ?? '—' }}</td>
                    <td data-label="{{ __('broadcasts.col_acknowledged_at') }}">{{ $ack->acknowledged_at?->isoFormat('lll') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
