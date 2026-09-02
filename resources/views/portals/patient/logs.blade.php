@extends('layouts.portal')

@section('title', __('public.medical_id.access_logs', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.medical_id.access_logs', [], app()->getLocale()) ?: 'Access Logs')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.medical_id.access_logs', [], app()->getLocale()) ?: 'Access Logs' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.access_logs_subtitle', [], app()->getLocale()) ?: 'Review who has accessed your Health ID and the status of those requests.' }}</p>
    </div>
</div>

<!-- Info Banner -->
<div class="alert alert-info mb-6">
    <i data-lucide="shield-check"></i>
    <div>
        {{ __('public.portal.access_logs_info', [], app()->getLocale()) ?: 'Every access to your Health ID is recorded here for your review. If you see an access you don\'t recognise, contact support immediately.' }}
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="history"></i>
            {{ __('public.portal.access_history', [], app()->getLocale()) ?: 'Access History' }}
        </h2>
        @php $logCount = method_exists($logs, 'total') ? $logs->total() : $logs->count(); @endphp
        @if($logCount > 0)
        <span class="badge badge-primary">{{ $logCount }}</span>
        @endif
    </div>

    @if($logCount === 0)
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
            <h3>{{ __('public.portal.no_logs_title', [], app()->getLocale()) ?: 'No Access Logs' }}</h3>
            <p>{{ __('public.portal.no_logs_desc', [], app()->getLocale()) ?: 'Your Medical ID has not been accessed recently.' }}</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table" aria-label="{{ __('public.aria_access_logs') }}">
                <thead>
                    <tr>
                        <th>{{ __('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time' }}</th>
                        <th>{{ __('public.portal.access_type', [], app()->getLocale()) ?: 'Access Type' }}</th>
                        <th>{{ __('public.portal.purpose', [], app()->getLocale()) ?: 'Purpose' }}</th>
                        <th>{{ __('public.portal.result', [], app()->getLocale()) ?: 'Result' }}</th>
                        <th>{{ __('public.portal.details', [], app()->getLocale()) ?: 'Details' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td data-label="{{ __('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time' }}">
                            <span class="td-strong">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</span>
                            <div class="td-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i') }}</div>
                        </td>
                        <td data-label="{{ __('public.portal.access_type', [], app()->getLocale()) ?: 'Access Type' }}">
                            <span class="cell-with-icon">
                                @if(str_contains($log->access_type ?? '', 'qr'))
                                    <i data-lucide="qr-code"></i>
                                    <span>{{ __('public.portal.qr_scan', [], app()->getLocale()) ?: 'QR Scan' }}</span>
                                @else
                                    <i data-lucide="search"></i>
                                    <span>{{ __('public.portal.id_lookup', [], app()->getLocale()) ?: 'ID Lookup' }}</span>
                                @endif
                            </span>
                        </td>
                        <td data-label="{{ __('public.portal.purpose', [], app()->getLocale()) ?: 'Purpose' }}">
                            <span class="td-muted">@enum($log->purpose ?? '—')</span>
                        </td>
                        <td data-label="{{ __('public.portal.result', [], app()->getLocale()) ?: 'Result' }}">
                            @if(($log->result ?? '') === 'success')
                                <span class="badge badge-success">{{ __('public.portal.granted', [], app()->getLocale()) ?: 'Granted' }}</span>
                            @else
                                <span class="badge badge-danger">{{ __('public.portal.denied', [], app()->getLocale()) ?: 'Denied' }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.portal.details', [], app()->getLocale()) ?: 'Details' }}">
                            <span class="td-mono">{{ $log->ip_address ?? '—' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(method_exists($logs, 'links') && $logs->hasPages())
        <div class="panel-body">
            {{ $logs->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
