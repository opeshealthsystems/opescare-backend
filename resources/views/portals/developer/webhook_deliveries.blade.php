@extends('layouts.portal')
@section('title', __('public.developer_portal.page_webhooks', [], app()->getLocale()) ?: 'Webhook Delivery Logs')
@section('sidebar_nav') @include('portals.developer._sidebar') @endsection
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.apps.show', $client->id) }}">{{ $client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'App' }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('public.developer_portal.lnk_webhooks', [], $l) ?: 'Webhook delivery logs' }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('public.developer_portal.page_webhooks', [], $l) ?: 'Webhook Delivery Logs' }}</h2>
    </div>

    <div class="panel">
        @if($deliveries->isEmpty())
        <div class="empty-state">
            <i data-lucide="webhook" class="empty-state-icon"></i>
            <p>{{ __('public.developer_portal.no_webhook_deliveries', [], $l) ?: 'No webhook deliveries recorded yet.' }}</p>
        </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.developer_portal.col_event', [], $l) ?: 'Event' }}</th>
                    <th>{{ __('public.developer_portal.col_type', [], $l) ?: 'Type' }}</th>
                    <th>{{ __('public.developer_portal.col_attempts', [], $l) ?: 'Attempts' }}</th>
                    <th>{{ __('public.developer_portal.col_http', [], $l) ?: 'HTTP' }}</th>
                    <th>{{ __('public.developer_portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.developer_portal.col_delivered_at', [], $l) ?: 'Delivered at' }}</th>
                </tr></thead>
                <tbody>
                @foreach($deliveries as $log)
                <tr>
                    <td data-label="{{ __('dev_extra.dl_event', [], $l) ?: 'Event' }}" class="mono">{{ Str::limit($log->event_id, 20) }}</td>
                    <td data-label="{{ __('dev_extra.dl_type', [], $l) ?: 'Type' }}" class="mono">{{ $log->event_type }}</td>
                    <td data-label="{{ __('dev_extra.dl_attempts', [], $l) ?: 'Attempts' }}">{{ $log->attempts ?? $log->retry_count ?? 0 }}</td>
                    <td data-label="{{ __('dev_extra.dl_http', [], $l) ?: 'HTTP' }}">
                        @if($log->http_status_code)
                        <span class="badge {{ $log->http_status_code >= 200 && $log->http_status_code < 300 ? 'badge-success' : 'badge-danger' }}">{{ $log->http_status_code }}</span>
                        @else
                        <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('dev_extra.dl_status', [], $l) ?: 'Status' }}"><span class="{{ $log->statusBadgeClass() }}">@enum($log->status)</span></td>
                    <td data-label="{{ __('dev_extra.dl_delivered_at', [], $l) ?: 'Delivered at' }}" class="td-muted">{{ $log->delivered_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-body">{{ $deliveries->links() }}</div>
        @endif
    </div>

@endsection
