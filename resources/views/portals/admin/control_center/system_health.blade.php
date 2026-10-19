@extends('layouts.portal')
@section('title', __('public.adm_cc_hlth_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_hlth_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_hlth_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.cc.health') }}" class="od-btn od-btn--secondary">
            <i data-lucide="refresh-cw"></i> {{ __('public.adm_cc_hlth_btn_refresh') }}
        </a>
    </div>
</div>

@php
    $statusTint = fn($s) => match($s) {
        'ok'      => 'od-tint--green',
        'warning' => 'od-tint--amber',
        'error'   => 'od-tint--rose',
        default   => 'od-tint--blue',
    };
    $statusBadge = fn($s) => match($s) {
        'ok'      => 'od-pill--ready',
        'warning' => 'od-pill--pending',
        'error'   => 'od-pill--urgent',
        default   => 'od-pill--neutral',
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
    $errors   = collect($health)->where('status', 'error')->count();
    $warnings = collect($health)->where('status', 'warning')->count();
    $oks      = collect($health)->where('status', 'ok')->count();
@endphp

{{-- Summary tiles --}}
<div class="od-kpis oda-ga-kpis3">
    <a href="{{ route('portals.admin.cc.health') }}" class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="check-circle"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_cc_hlth_stat_healthy') }}</span>
            <span class="od-kpi__value">{{ $oks }}</span>
        </span>
    </a>
    <a href="{{ route('portals.admin.cc.health') }}" class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="alert-circle"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_cc_hlth_stat_warnings') }}</span>
            <span class="od-kpi__value">{{ $warnings }}</span>
        </span>
    </a>
    <a href="{{ route('portals.admin.cc.health') }}" class="od-kpi od-tint--rose">
        <span class="od-kpi__icon"><i data-lucide="x-circle"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_cc_hlth_stat_errors') }}</span>
            <span class="od-kpi__value">{{ $errors }}</span>
        </span>
    </a>
</div>

<div class="od-grid od-grid--3">
    @foreach($checks as [$label, $data, $defaultMsg])
    <section class="od-card oda-ga-check {{ $statusTint($data['status']) }}">
        <div class="oda-ga-check__head">
            <span class="oda-ga-check__icon"><i data-lucide="{{ $statusIcon($data['status']) }}"></i></span>
            <span>{{ $label }}</span>
            <span class="od-pill {{ $statusBadge($data['status']) }}">{{ strtoupper($data['status']) }}</span>
        </div>
        <p class="oda-ga-muted">{{ $data['message'] ?? $defaultMsg }}</p>
    </section>
    @endforeach

    {{-- Failed Jobs --}}
    <section class="od-card oda-ga-check {{ $statusTint($fjStatus) }}">
        <div class="oda-ga-check__head">
            <span class="oda-ga-check__icon"><i data-lucide="{{ $statusIcon($fjStatus) }}"></i></span>
            <span>{{ __('public.adm_cc_hlth_check_failed_jobs') }}</span>
            <span class="od-pill {{ $fjStatus === 'warning' ? 'od-pill--pending' : 'od-pill--ready' }}">{{ $fj['count'] ?? 0 }}</span>
        </div>
        <p class="oda-ga-muted">{{ $fj['message'] ?? __('public.adm_cc_hlth_check_failed_jobs_msg') }}</p>
    </section>
</div>

{{-- Summary Panel --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_hlth_summary_heading') }}</h2>
    </div>
    <div class="od-card__body">
        @if($errors > 0)
            <div class="oda-ga-alert oda-ga-alert--danger">
                <i data-lucide="alert-triangle"></i>
                <div class="oda-ga-alert__body">{{ __('public.adm_cc_hlth_alert_critical') }}</div>
            </div>
        @elseif($warnings > 0)
            <div class="oda-ga-alert oda-ga-alert--warn">
                <i data-lucide="alert-circle"></i>
                <div class="oda-ga-alert__body">{{ __('public.adm_cc_hlth_alert_warning') }}</div>
            </div>
        @else
            <div class="oda-ga-alert oda-ga-alert--ok">
                <i data-lucide="check-circle"></i>
                <div class="oda-ga-alert__body">{{ __('public.adm_cc_hlth_alert_ok') }}</div>
            </div>
        @endif
        <p class="oda-ga-muted">
            {{ __('public.adm_cc_hlth_last_checked') }} {{ now()->translatedFormat('M d, Y H:i:s') }} {{ __('public.adm_cc_hlth_server_time') }}
        </p>
    </div>
</section>
</div>
@endsection
