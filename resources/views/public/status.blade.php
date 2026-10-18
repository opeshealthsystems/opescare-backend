@extends('layouts.public')

@section('title', __('public.status_page.page_title'))
@section('meta_description', __('public.status_page.meta_description'))

@section('head_scripts')
    @include('public.partials._od_pages_css')
@endsection

@section('content')
<div class="odp">

@php
    // Live status styling (driven by SystemHealthService::currentHealth()).
    $statusStyles = [
        'operational' => ['tone' => 'ok', 'label' => __('public.status_page.operational')],
        'degraded'    => ['tone' => 'warn', 'label' => __('public.status_page.degraded')],
        'outage'      => ['tone' => 'bad', 'label' => __('public.status_page.outage')],
    ];
    $overallMap = [
        'healthy'  => ['tone' => 'ok',   'icon' => 'check-circle', 'title' => __('public.status_page.all_operational')],
        'degraded' => ['tone' => 'warn', 'icon' => 'alert-triangle', 'title' => __('public.status_page.some_degraded')],
        'critical' => ['tone' => 'bad',  'icon' => 'alert-octagon', 'title' => __('public.status_page.major_outage')],
    ];
    $ov = $overallMap[$health['status']] ?? $overallMap['healthy'];

    $services = [
        ['key' => 'core',         'group' => __('public.status_page.group_core'),         'items' => [__('public.status_page.svc_connect_api'), __('public.status_page.svc_health_id'), __('public.status_page.svc_consent'), __('public.status_page.svc_audit')]],
        ['key' => 'clinical',     'group' => __('public.status_page.group_clinical'),     'items' => [__('public.status_page.svc_timeline'), __('public.status_page.svc_lab'), __('public.status_page.svc_prescription'), __('public.status_page.svc_referral')]],
        ['key' => 'availability', 'group' => __('public.status_page.group_availability'), 'items' => [__('public.status_page.svc_medication'), __('public.status_page.svc_blood'), __('public.status_page.svc_care_map')]],
        ['key' => 'integration',  'group' => __('public.status_page.group_integration'),  'items' => [__('public.status_page.svc_webhooks'), __('public.status_page.svc_bridge'), __('public.status_page.svc_sdk')]],
        ['key' => 'portal',       'group' => __('public.status_page.group_portal'),       'items' => [__('public.status_page.svc_patient_portal'), __('public.status_page.svc_staff_portal'), __('public.status_page.svc_dev_portal'), __('public.status_page.svc_auth')]],
    ];
@endphp

    {{-- Hero --}}
    <header class="content-header odp-pad-y">
        <div class="container">
            <h1>{{ __('public.status_page.hero_title') }}</h1>
            <p class="text-muted">{{ __('public.status_page.hero_subtitle') }}</p>
        </div>
    </header>

    <section class="content-body">
        <div class="container odp-measure">

            {{-- Overall banner (live) --}}
            <div class="odp-status-banner">
                <i class="odp-icon odp-icon--lg odp-icon--{{ $ov['tone'] }}" data-lucide="{{ $ov['icon'] }}"></i>
                <div>
                    <h2>{{ $ov['title'] }}</h2>
                    <p>{{ __('public.status_page.last_updated') }} {{ $health['checked_at']->copy()->utc()->translatedFormat('d M Y — H:i') }} {{ __('public.status_page.utc') }}</p>
                </div>
            </div>

            {{-- Service table (live per-group) --}}
            @foreach($services as $group)
            @php $st = $statusStyles[$health['components'][$group['key']] ?? 'operational']; @endphp
            <div class="odp-mb-32">
                <h3 class="odp-kicker">{{ $group['group'] }}</h3>
                <div class="odp-status-group">
                    @foreach($group['items'] as $i => $name)
                    <div class="odp-status-row">
                        <div class="odp-row odp-grow">
                            <span class="odp-dot odp-dot--{{ $st['tone'] }}"></span>
                            <span class="odp-status-row__name">{{ $name }}</span>
                        </div>
                        <span class="odp-pill odp-pill--{{ $st['tone'] }}">{{ $st['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            {{-- Incident / maintenance history (live) --}}
            <div class="odp-mb-32">
                <h3 class="odp-kicker">{{ __('public.status_page.incidents_title') }}</h3>
                <div class="odp-status-group">
                    @forelse($maintenance as $m)
                    @php $upcoming = $m->starts_at && $m->starts_at->isFuture(); @endphp
                    <div class="odp-status-group__head">
                        <div class="odp-row odp-row--top">
                            <span class="odp-dot {{ $upcoming ? 'odp-dot--info' : 'odp-dot--warn' }} odp-mt-6"></span>
                            <div class="odp-grow">
                                <div class="odp-bold">{{ $m->title }}</div>
                                @if($m->message)<div class="odp-muted">{{ $m->message }}</div>@endif
                                <div class="odp-muted">
                                    {{ $upcoming ? __('public.status_page.scheduled') : __('public.status_page.in_progress') }}
                                    @if($m->starts_at) · {{ $m->starts_at->copy()->utc()->translatedFormat('d M Y H:i') }} {{ __('public.status_page.utc') }}@endif
                                    @if($m->ends_at) → {{ $m->ends_at->copy()->utc()->format('H:i') }}@endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="odp-status-group__head">
                        <div class="odp-row odp-row--top">
                            <span class="odp-dot odp-dot--ok odp-mt-6"></span>
                            <div>
                                <div class="odp-bold">{{ __('public.status_page.no_incidents') }}</div>
                                <div class="odp-muted">{{ __('public.status_page.no_incidents_desc') }}</div>
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- SLA + subscribe notice --}}
            <div class="odp-panel odp-row">
                <i class="odp-icon odp-icon--lg" data-lucide="shield-check"></i>
                <div class="odp-grow">
                    <div class="odp-bold">{{ __('public.status_page.subscribe_title') }}</div>
                    <div class="odp-muted">{{ __('public.status_page.subscribe_desc') }}</div>
                </div>
                <a href="{{ route('public.sla') }}" class="btn btn-primary odp-shrink">{{ __('public.status_page.btn_sla') }}</a>
            </div>

        </div>
    </section>

</div>
@endsection
