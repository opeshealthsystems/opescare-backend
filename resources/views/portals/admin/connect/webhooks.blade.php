@extends('layouts.portal')

@section('title', __('public.adm_connect_wh_title'))

@section('sidebar')
    @include('portals.admin.connect._sidebar')
@endsection

@section('content')

<div class="page-head">
    <h2><i data-lucide="webhook"></i> {{ __('public.adm_connect_wh_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_connect_wh_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Delivery Stats --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle"></i></div>
        <div class="stat-card__value">{{ $stats['delivered'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_connect_wh_stat_delivered') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__head"><i data-lucide="x-circle"></i></div>
        <div class="stat-card__value">{{ $stats['failed'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_connect_wh_stat_failed') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__value">{{ $stats['pending'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_connect_wh_stat_pending') }}</div>
    </div>
</div>

{{-- Subscriptions --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="rss"></i> {{ __('public.adm_connect_wh_subs_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_connect_wh_col_client_id') }}</th>
                    <th>{{ __('public.adm_connect_wh_col_endpoint') }}</th>
                    <th>{{ __('public.adm_connect_wh_col_events') }}</th>
                    <th>{{ __('public.adm_connect_wh_col_status') }}</th>
                    <th>{{ __('public.adm_connect_wh_col_created') }}</th>
                    <th class="row-actions">{{ __('public.adm_connect_wh_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td data-label="{{ __('public.adm_connect_wh_col_client_id') }}"><span class="code-token">{{ $sub->client_id }}</span></td>
                        <td data-label="{{ __('public.adm_connect_wh_col_endpoint') }}"><span class="td-muted" title="{{ $sub->endpoint_url }}">{{ Str::limit($sub->endpoint_url, 60) }}</span></td>
                        <td data-label="{{ __('public.adm_connect_wh_col_events') }}">
                            @foreach(array_slice($sub->events ?? [], 0, 3) as $event)
                                <span class="badge badge-success">{{ $event }}</span>
                            @endforeach
                            @if(count($sub->events ?? []) > 3)<span class="td-muted">+{{ count($sub->events) - 3 }} more</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_wh_col_status') }}">
                            <span class="badge badge-{{ $sub->status === 'active' ? 'success' : 'warning' }}">{{ $sub->status }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_connect_wh_col_created') }}">{{ $sub->created_at->format('d M Y') }}</td>
                        <td class="row-actions" data-label="{{ __('public.adm_connect_wh_col_actions') }}">
                            <form method="POST" action="{{ route('portals.admin.connect.webhooks.toggle', $sub->id) }}" class="inline-form">
                                @csrf
                                <button class="btn btn-{{ $sub->status === 'active' ? 'warning' : 'success' }} btn-sm" title="{{ $sub->status === 'active' ? 'Pause' : 'Resume' }}">
                                    <i data-lucide="{{ $sub->status === 'active' ? 'pause' : 'play' }}"></i> {{ $sub->status === 'active' ? 'Pause' : 'Resume' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_connect_wh_subs_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
        <div class="panel-body">{{ $subscriptions->links() }}</div>
    @endif
</div>

{{-- Delivery Log --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="activity"></i> {{ __('public.adm_connect_wh_log_title') }} <span class="td-muted">{{ __('public.adm_connect_wh_log_last30') }}</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_connect_wh_log_col_event') }}</th>
                    <th>{{ __('public.adm_connect_wh_log_col_endpoint') }}</th>
                    <th>{{ __('public.adm_connect_wh_log_col_status') }}</th>
                    <th>{{ __('public.adm_connect_wh_log_col_http') }}</th>
                    <th>{{ __('public.adm_connect_wh_log_col_attempts') }}</th>
                    <th>{{ __('public.adm_connect_wh_log_col_delivered') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryLogs as $log)
                    <tr>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_event') }}"><span class="code-token">{{ $log->event_type ?? '—' }}</span></td>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_endpoint') }}"><span class="td-muted">{{ Str::limit($log->endpoint_url ?? '', 40) }}</span></td>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_status') }}">
                            @php
                                $statusBadge = match($log->status ?? '') {
                                    'delivered' => 'success',
                                    'failed'    => 'danger',
                                    default     => 'warning',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusBadge }}">{{ $log->status ?? 'pending' }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_http') }}">
                            @if($log->http_status_code ?? null)
                                <span class="badge badge-{{ ($log->http_status_code >= 200 && $log->http_status_code < 300) ? 'success' : 'danger' }}">{{ $log->http_status_code }}</span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_attempts') }}">{{ $log->attempt_count ?? 1 }}</td>
                        <td data-label="{{ __('public.adm_connect_wh_log_col_delivered') }}">{{ $log->delivered_at ? \Carbon\Carbon::parse($log->delivered_at)->diffForHumans() : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_connect_wh_log_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
