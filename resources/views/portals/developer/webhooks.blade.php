@extends('layouts.portal')
@section('title', __('developer_ext.wh_page_title'))
@php
    $l = app()->getLocale();
    $deliveryLabel = fn (?string $s) => match ($s) {
        'delivered' => __('developer_ext.wh_dl_delivered'),
        'failed'    => __('developer_ext.wh_dl_failed'),
        'exhausted' => __('developer_ext.wh_dl_exhausted'),
        default     => __('developer_ext.wh_dl_pending'),
    };
@endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('developer_ext.wh_page_title') }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('developer_ext.wh_page_title') }}</h2>
        <p class="portal-page-subtitle">{{ __('developer_ext.wh_subtitle') }}</p>
        <div class="page-head__spacer"></div>
        <a href="{{ route('docs.webhooks') }}" class="btn btn-ghost btn-sm" target="_blank" rel="noopener"><i data-lucide="book-open"></i> {{ __('developer_ext.wh_docs_link') }}</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

    @if(session('new_webhook_secret'))
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> {{ __('developer_ext.wh_secret_title') }}</h3></div>
        <div class="panel-body">
            <p class="mb-6">{{ __('developer_ext.wh_secret_body') }}</p>
            <div class="code-block"><strong>{{ __('developer_ext.wh_lbl_secret') }}:</strong> <span class="code-token" data-copy="{{ session('new_webhook_secret') }}">{{ session('new_webhook_secret') }}</span></div>
        </div>
    </div>
    @endif

    {{-- Register --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="plus-circle"></i> {{ __('developer_ext.wh_register_title') }}</h3></div>
        <div class="panel-body">
            @if($clients->isEmpty())
                <div class="empty-state">
                    <i data-lucide="app-window" class="empty-state-icon"></i>
                    <p>{{ __('developer_ext.wh_no_apps') }}</p>
                    <a href="{{ route('portals.developer.apps.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_create_app', [], $l) }}</a>
                </div>
            @else
                <form method="POST" action="{{ route('portals.developer.webhooks.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="wh-app">{{ __('developer_ext.wh_lbl_app') }}</label>
                        <select id="wh-app" name="integration_client_id" class="form-control" required>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ old('integration_client_id') === $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->client_id }})</option>
                            @endforeach
                        </select>
                        @error('integration_client_id')<div class="form-hint">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="wh-url">{{ __('developer_ext.wh_lbl_callback_url') }}</label>
                        <input id="wh-url" type="url" name="callback_url" class="form-control" value="{{ old('callback_url') }}" placeholder="https://" required>
                        <div class="form-hint">{{ __('developer_ext.wh_help_https') }}</div>
                        @error('callback_url')<div class="form-hint">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __("developer_ext.wh_lbl_events") }}</label>
                        @foreach($eventTypes as $ev)
                            <label class="form-check">
                                <input type="checkbox" name="events[]" value="{{ $ev }}" {{ in_array($ev, (array) old('events', [])) ? 'checked' : '' }}>
                                <span class="mono">{{ $ev }}</span>
                            </label>
                        @endforeach
                        @error('events')<div class="form-hint">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><i data-lucide="webhook"></i> {{ __('developer_ext.wh_btn_register') }}</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Subscriptions --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="webhook"></i> {{ __('developer_ext.wh_page_title') }}</h3></div>
        @if($subscriptions->isEmpty())
            <div class="panel-body empty-state"><p>{{ __('developer_ext.wh_no_subscriptions') }}</p></div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.wh_col_app') }}</th>
                        <th>{{ __('developer_ext.wh_col_endpoint') }}</th>
                        <th>{{ __('developer_ext.wh_col_events') }}</th>
                        <th>{{ __('developer_ext.wh_col_status') }}</th>
                        <th>{{ __('developer_ext.wh_col_created') }}</th>
                        <th class="row-actions"></th>
                    </tr></thead>
                    <tbody>
                    @foreach($subscriptions as $sub)
                        <tr>
                            <td data-label="{{ __('developer_ext.wh_col_app') }}" class="td-strong">{{ $clientNames[$sub->client_id] ?? $sub->client_id }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_endpoint') }}" class="mono">{{ $sub->callback_url }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_events') }}">
                                @foreach((array) $sub->subscribed_events as $ev)<span class="code-token">{{ $ev }}</span> @endforeach
                            </td>
                            <td data-label="{{ __('developer_ext.wh_col_status') }}">
                                <span class="badge {{ $sub->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ $sub->status === 'active' ? __('developer_ext.wh_status_active') : __('developer_ext.wh_status_paused') }}</span>
                            </td>
                            <td data-label="{{ __('developer_ext.wh_col_created') }}" class="td-muted">{{ $sub->created_at?->translatedFormat('d M Y') }}</td>
                            <td class="row-actions" data-label="">
                                <form method="POST" action="{{ route('portals.developer.webhooks.toggle', $sub->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i data-lucide="{{ $sub->status === 'active' ? 'pause' : 'play' }}"></i>
                                        {{ $sub->status === 'active' ? __('developer_ext.wh_btn_pause') : __('developer_ext.wh_btn_resume') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent deliveries --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="send"></i> {{ __('developer_ext.wh_deliveries_title') }}</h3>
        </div>
        @if($deliveries->isEmpty())
            <div class="panel-body empty-state"><p>{{ __('developer_ext.wh_no_deliveries') }}</p></div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('developer_ext.wh_col_event_type') }}</th>
                        <th>{{ __('developer_ext.wh_col_endpoint') }}</th>
                        <th>{{ __('developer_ext.wh_col_attempts') }}</th>
                        <th>{{ __('developer_ext.wh_col_http') }}</th>
                        <th>{{ __('developer_ext.wh_col_status') }}</th>
                        <th>{{ __('developer_ext.wh_col_delivered_at') }}</th>
                        <th class="row-actions"></th>
                    </tr></thead>
                    <tbody>
                    @foreach($deliveries as $log)
                        <tr>
                            <td data-label="{{ __('developer_ext.wh_col_event_type') }}" class="mono">{{ $log->event_type }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_endpoint') }}" class="mono">{{ Str::limit($log->endpoint_url, 48) }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_attempts') }}">{{ $log->attempts ?? $log->retry_count ?? 0 }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_http') }}">{{ $log->http_status_code ?? '—' }}</td>
                            <td data-label="{{ __('developer_ext.wh_col_status') }}">
                                <span class="badge {{ $log->status === 'delivered' ? 'badge-success' : ($log->status === 'pending' ? 'badge-info' : 'badge-danger') }}">{{ $deliveryLabel($log->status) }}</span>
                            </td>
                            <td data-label="{{ __('developer_ext.wh_col_delivered_at') }}" class="td-muted">{{ $log->delivered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                            <td class="row-actions">
                                @if($log->isFailed())
                                    <form method="POST" action="{{ route('portals.developer.webhooks.replay', $log->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" title="{{ __('developer_ext.wh_btn_replay_help') }}"><i data-lucide="rotate-ccw"></i> {{ __('developer_ext.wh_btn_replay') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($clients->isNotEmpty())
        <div class="panel-body td-muted">
            {{ __('developer_ext.wh_per_app_logs') }}:
            @foreach($clients as $c)
                <a href="{{ route('portals.developer.webhook_deliveries', $c->id) }}">{{ $c->name }}</a>@if(!$loop->last), @endif
            @endforeach
        </div>
        @endif
    </div>

@endsection
@section('scripts')
<script @cspNonce>
document.addEventListener('click', function(e){
    var t = e.target.closest('.code-token[data-copy]');
    if(!t) return;
    navigator.clipboard && navigator.clipboard.writeText(t.getAttribute('data-copy'));
    var prev = t.textContent; t.textContent = @json(__('developer_ext.copied')); setTimeout(function(){ t.textContent = prev; }, 1200);
});
</script>
@endsection
