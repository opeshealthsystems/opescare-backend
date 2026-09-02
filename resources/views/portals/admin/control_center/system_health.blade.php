@extends('layouts.portal')
@section('title', __('public.adm_cc_hlth_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_hlth_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_hlth_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_hlth_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_hlth_subtitle') }}</p>
    </div>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.cc.health') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="refresh-cw"></i> {{ __('public.adm_cc_hlth_btn_refresh') }}
    </a>
</div>

@php
    $statusVariant = fn($s) => match($s) {
        'ok'      => 'stat-card--success',
        'warning' => 'stat-card--warning',
        'error'   => 'stat-card--danger',
        default   => '',
    };
    $statusBadge = fn($s) => match($s) {
        'ok'      => 'badge-success',
        'warning' => 'badge-warning',
        'error'   => 'badge-danger',
        default   => 'badge-neutral',
    };
    $statusIcon = fn($s) => match($s) {
        'ok'      => 'check-circle',
        'warning' => 'alert-circle',
        'error'   => 'x-circle',
        default   => 'minus-circle',
    };
    $checks = [
        [__('public.adm_cc_hlth_check_database'),    $health['database']    ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_database_msg')],
        [__('public.adm_cc_hlth_check_storage'),     $health['storage']     ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_storage_msg')],
        [__('public.adm_cc_hlth_check_queue'),       $health['queue']       ?? ['status'=>'unknown'], __('public.adm_cc_hlth_check_queue_msg')],
        [__('public.adm_cc_hlth_check_maintenance'), $health['maintenance'] ?? ['status'=>'ok'],      __('public.adm_cc_hlth_check_maintenance_msg')],
    ];
    $fj = $health['failed_jobs'] ?? ['status'=>'ok','count'=>0,'message'=>''];
    $fjStatus = ($fj['count'] ?? 0) > 0 ? 'warning' : 'ok';
@endphp

<div class="card-grid mb-6">

    @foreach($checks as [$label, $data, $defaultMsg])
    <div class="stat-card {{ $statusVariant($data['status']) }}">
        <div class="stat-card__head">
            <i data-lucide="{{ $statusIcon($data['status']) }}"></i>
            <span class="stat-card__label">{{ $label }}</span>
            <span class="badge {{ $statusBadge($data['status']) }}  badge-sm ml-auto">{{ strtoupper($data['status']) }}</span>
        </div>
        <p class="nav-card__desc">{{ $data['message'] ?? $defaultMsg }}</p>
    </div>
    @endforeach

    {{-- Failed Jobs --}}
    <div class="stat-card {{ $statusVariant($fjStatus) }}">
        <div class="stat-card__head">
            <i data-lucide="{{ $statusIcon($fjStatus) }}"></i>
            <span class="stat-card__label">{{ __('public.adm_cc_hlth_check_failed_jobs') }}</span>
            <span class="badge {{ $fjStatus === 'warning' ? 'badge-warning' : 'badge-success' }}  badge-sm ml-auto">{{ $fj['count'] ?? 0 }}</span>
        </div>
        <p class="nav-card__desc">{{ $fj['message'] ?? __('public.adm_cc_hlth_check_failed_jobs_msg') }}</p>
    </div>

</div>

{{-- Summary Panel --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> {{ __('public.adm_cc_hlth_summary_heading') }}</h3>
    </div>
    <div class="panel-body">
        @php
            $errors   = collect($health)->where('status', 'error')->count();
            $warnings = collect($health)->where('status', 'warning')->count();
            $oks      = collect($health)->where('status', 'ok')->count();
        @endphp
        <div class="stat-grid mb-4">
            <div class="stat-card stat-card--success">
                <div class="stat-card__value">{{ $oks }}</div>
                <div class="stat-card__label">{{ __('public.adm_cc_hlth_stat_healthy') }}</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-card__value">{{ $warnings }}</div>
                <div class="stat-card__label">{{ __('public.adm_cc_hlth_stat_warnings') }}</div>
            </div>
            <div class="stat-card stat-card--danger">
                <div class="stat-card__value">{{ $errors }}</div>
                <div class="stat-card__label">{{ __('public.adm_cc_hlth_stat_errors') }}</div>
            </div>
        </div>
        @if($errors > 0)
            <div class="alert alert-danger">
                <i data-lucide="alert-triangle"></i>
                <div>{{ __('public.adm_cc_hlth_alert_critical') }}</div>
            </div>
        @elseif($warnings > 0)
            <div class="alert alert-warning">
                <i data-lucide="alert-circle"></i>
                <div>{{ __('public.adm_cc_hlth_alert_warning') }}</div>
            </div>
        @else
            <div class="alert alert-success">
                <i data-lucide="check-circle"></i>
                <div>{{ __('public.adm_cc_hlth_alert_ok') }}</div>
            </div>
        @endif
        <div class="td-muted text-sm mt-6">
            {{ __('public.adm_cc_hlth_last_checked') }} {{ now()->format('M d, Y H:i:s') }} {{ __('public.adm_cc_hlth_server_time') }}
        </div>
    </div>
</div>
@endsection
