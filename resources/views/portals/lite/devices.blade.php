@extends('layouts.lite')
@section('title', __('public.lite_portal.page_devices', [], app()->getLocale()) ?: 'Devices')
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="lite-page-head">
    <div>
        <h1 class="lite-page-title">{{ __('public.lite_portal.action_devices', [], $l) ?: 'Devices' }}</h1>
        <p class="lite-page-sub">{{ __('public.lite_portal.devices_subtitle', [], $l) ?: 'Manage registered OpesCare Lite devices' }}</p>
    </div>
</div>

{{-- Stats --}}
<div class="lite-stat-row">
    <div class="lite-stat-chip">
        <div class="lite-stat-chip__val">{{ $stats['total_devices'] }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_total_today', [], $l) ?: 'Total' }}</div>
    </div>
    <div class="lite-stat-chip lite-stat-chip--success">
        <div class="lite-stat-chip__val">{{ $stats['active_devices'] }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_active', [], $l) ?: 'Active' }}</div>
    </div>
    <div class="lite-stat-chip lite-stat-chip--warning">
        <div class="lite-stat-chip__val">{{ $stats['pending_devices'] }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_waiting', [], $l) ?: 'Pending' }}</div>
    </div>
    @if($stats['open_conflicts'] > 0)
    <div class="lite-stat-chip lite-stat-chip--danger">
        <div class="lite-stat-chip__val">{{ $stats['open_conflicts'] }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_conflicts', [], $l) ?: 'Conflicts' }}</div>
    </div>
    @endif
</div>

@if($devices->isEmpty())
    <div class="lite-alert lite-alert--info">
        <i data-lucide="monitor-smartphone"></i>
        <span>{{ __('lite_chrome.devices_empty', [], $l) ?: 'No Lite devices registered yet. Devices register via the API endpoint' }}
        <code class="lite-code">POST /api/v1/lite/register-device</code>.</span>
    </div>
@else
    <div class="lite-card">
        <div class="lite-card__body lite-card__body--scroll">
            <table class="lite-table">
                <thead>
                    <tr>
                        <th>{{ __('public.lite_portal.col_device', [], $l) ?: 'Device' }}</th>
                        <th>{{ __('public.lite_portal.col_platform', [], $l) ?: 'Platform' }}</th>
                        <th>{{ __('public.lite_portal.col_status', [], $l) ?: 'Status' }}</th>
                        <th>{{ __('public.lite_portal.col_modules', [], $l) ?: 'Modules' }}</th>
                        <th>{{ __('public.lite_portal.col_last_seen', [], $l) ?: 'Last seen' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($devices as $dev)
                    <tr>
                        <td>
                            <div class="lite-td-strong">{{ $dev->device_name }}</div>
                            <div class="lite-mono">{{ substr($dev->id, 0, 8) }}…</div>
                        </td>
                        <td>@enum($dev->platform ?? 'web', 'platform')</td>
                        <td><span class="lite-badge lite-badge--{{ $dev->statusColor() }}">@enum($dev->status)</span></td>
                        <td>{{ $dev->entitlements->where('is_enabled', true)->count() }} {{ __('lite_chrome.devices_modules', [], $l) ?: 'modules' }}</td>
                        <td>{{ $dev->last_seen_at ? $dev->last_seen_at->diffForHumans() : (__('lite_chrome.devices_never', [], $l) ?: 'Never') }}</td>
                        <td>
                            <div class="lite-row lite-row--end">
                                @if($dev->status === 'pending')
                                    <form method="POST" action="{{ route('portals.lite.devices.activate', $dev) }}" onsubmit="return confirm('{{ __('lite_chrome.devices_confirm_activate', [], $l) ?: 'Activate this device?' }}')">
                                        @csrf
                                        <button type="submit" class="lite-btn lite-btn--success lite-btn--xs">{{ __('public.lite_portal.btn_activate', [], $l) ?: 'Activate' }}</button>
                                    </form>
                                @endif
                                @if(!in_array($dev->status, ['revoked', 'lost']))
                                    <form method="POST" action="{{ route('portals.lite.devices.revoke', $dev) }}" onsubmit="return confirm('{{ __('lite_chrome.devices_confirm_revoke', [], $l) ?: 'Revoke this device? This cannot be undone.' }}')">
                                        @csrf
                                        <input type="hidden" name="reason" value="{{ __('lite_chrome.devices_revoke_reason', [], $l) ?: 'Revoked via Lite portal.' }}">
                                        <button type="submit" class="lite-btn lite-btn--danger lite-btn--xs">{{ __('public.lite_portal.btn_revoke', [], $l) ?: 'Revoke' }}</button>
                                    </form>
                                @endif
                                <a href="{{ route('portals.lite.offline_events', $dev) }}" class="lite-btn lite-btn--outline lite-btn--xs">{{ __('public.lite_portal.btn_events', [], $l) ?: 'Events' }}</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="lite-mt">{{ $devices->links() }}</div>
@endif

<div class="lite-alert lite-alert--info lite-alert--sm lite-mt">
    <i data-lucide="info"></i>
    <span>{{ __('public.lite_portal.devices_info', [], $l) ?: 'New devices must be registered via the API and then activated here. Revoked devices cannot sync.' }}</span>
</div>

@endsection
