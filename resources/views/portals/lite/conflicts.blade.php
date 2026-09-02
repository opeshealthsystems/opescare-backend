@extends('layouts.lite')
@section('title', __('public.lite_portal.conflicts_title', [], app()->getLocale()) ?: 'Sync Conflicts')
@php $l = app()->getLocale(); @endphp

@section('content')

<h1 class="lite-page-title">{{ __('public.lite_portal.conflicts_title', [], $l) ?: 'Sync conflicts' }}</h1>
<p class="lite-page-sub">{{ __('public.lite_portal.conflicts_subtitle', [], $l) ?: 'Offline events that could not be automatically applied' }}</p>

@if($conflicts->isEmpty())
    <div class="lite-empty lite-empty--success">
        <i data-lucide="check-circle"></i>
        <p>{{ __('public.lite_portal.conflicts_empty', [], $l) ?: 'No open conflicts — all syncs applied cleanly.' }}</p>
    </div>
@else
    <div class="lite-card">
        <div class="lite-card__body lite-card__body--scroll">
            <table class="lite-table">
                <thead>
                    <tr>
                        <th>{{ __('public.lite_portal.conflicts_col_event_type', [], $l) ?: 'Event type' }}</th>
                        <th>{{ __('public.lite_portal.conflicts_col_conflict', [], $l) ?: 'Conflict' }}</th>
                        <th>{{ __('public.lite_portal.conflicts_col_device', [], $l) ?: 'Device' }}</th>
                        <th>{{ __('public.lite_portal.conflicts_col_status', [], $l) ?: 'Status' }}</th>
                        <th>{{ __('public.lite_portal.conflicts_col_captured', [], $l) ?: 'Captured' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conflicts as $c)
                    <tr>
                        <td class="lite-td-strong">@enum($c->offlineEvent?->event_type)</td>
                        <td>@enum($c->conflict_type)</td>
                        <td>{{ $c->device?->device_name ?? '—' }}</td>
                        <td><span class="lite-badge lite-badge--{{ $c->statusColor() }}">@enum($c->status)</span></td>
                        <td>{{ $c->offlineEvent?->captured_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            @if($c->isOpen())
                            <div class="lite-row lite-row--end">
                                <form method="POST" action="{{ route('portals.lite.conflicts.resolve', $c) }}">
                                    @csrf
                                    <input type="hidden" name="resolution" value="resolved">
                                    <input type="hidden" name="note" value="{{ __('lite_chrome.conflicts_resolve_note', [], $l) ?: 'Resolved via Lite portal.' }}">
                                    <button type="submit" class="lite-btn lite-btn--success lite-btn--xs">{{ __('public.lite_portal.conflicts_btn_resolve', [], $l) ?: 'Resolve' }}</button>
                                </form>
                                <form method="POST" action="{{ route('portals.lite.conflicts.resolve', $c) }}" onsubmit="return confirm('{{ __('public.lite_portal.conflicts_confirm_dismiss', [], $l) ?: 'Dismiss this conflict?' }}')">
                                    @csrf
                                    <input type="hidden" name="resolution" value="dismiss">
                                    <button type="submit" class="lite-btn lite-btn--outline lite-btn--xs">{{ __('public.lite_portal.conflicts_btn_dismiss', [], $l) ?: 'Dismiss' }}</button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="lite-mt">{{ $conflicts->links() }}</div>
@endif

@endsection
