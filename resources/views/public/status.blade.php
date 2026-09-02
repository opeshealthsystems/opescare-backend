@extends('layouts.public')

@section('title', __('public.status_page.page_title'))
@section('meta_description', __('public.status_page.meta_description'))

@section('content')

@php
    // Live status styling (driven by SystemHealthService::currentHealth()).
    $statusStyles = [
        'operational' => ['dot' => '#10b981', 'bg' => '#ecfdf5', 'text' => '#047857', 'label' => __('public.status_page.operational')],
        'degraded'    => ['dot' => '#f59e0b', 'bg' => '#fffbeb', 'text' => '#b45309', 'label' => __('public.status_page.degraded')],
        'outage'      => ['dot' => '#ef4444', 'bg' => '#fef2f2', 'text' => '#b91c1c', 'label' => __('public.status_page.outage')],
    ];
    $overallMap = [
        'healthy'  => ['bg' => '#ecfdf5', 'border' => '#10b981', 'icon' => 'check',          'iconbg' => '#10b981', 'text' => '#065f46', 'title' => __('public.status_page.all_operational')],
        'degraded' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'icon' => 'alert-triangle', 'iconbg' => '#f59e0b', 'text' => '#92400e', 'title' => __('public.status_page.some_degraded')],
        'critical' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'icon' => 'alert-octagon',  'iconbg' => '#ef4444', 'text' => '#991b1b', 'title' => __('public.status_page.major_outage')],
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
    <header class="content-header" style="padding:3rem 0 2.5rem;">
        <div class="container">
            <h1>{{ __('public.status_page.hero_title') }}</h1>
            <p class="text-muted">{{ __('public.status_page.hero_subtitle') }}</p>
        </div>
    </header>

    <section class="content-body">
        <div class="container" style="max-width:800px;">

            {{-- Overall banner (live) --}}
            <div style="background:{{ $ov['bg'] }};border:1.5px solid {{ $ov['border'] }};border-radius:1.25rem;display:flex;align-items:center;gap:1.25rem;padding:1.5rem;margin-bottom:2.5rem;">
                <div style="width:3rem;height:3rem;background:{{ $ov['iconbg'] }};border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="{{ $ov['icon'] }}" style="width:1.5rem;height:1.5rem;color:#fff;"></i>
                </div>
                <div>
                    <h3 style="margin:0;color:{{ $ov['text'] }};font-size:1.125rem;">{{ $ov['title'] }}</h3>
                    <p style="margin:.25rem 0 0;font-size:.875rem;color:{{ $ov['text'] }};">{{ __('public.status_page.last_updated') }} {{ $health['checked_at']->copy()->utc()->format('d M Y — H:i') }} {{ __('public.status_page.utc') }}</p>
                </div>
            </div>

            {{-- Service table (live per-group) --}}
            @foreach($services as $group)
            @php $st = $statusStyles[$health['components'][$group['key']] ?? 'operational']; @endphp
            <div style="margin-bottom:2rem;">
                <h3 style="font-size:.875rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.75rem;">{{ $group['group'] }}</h3>
                <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;">
                    @foreach($group['items'] as $i => $name)
                    <div style="display:flex;align-items:center;padding:1rem 1.25rem;{{ $i < count($group['items'])-1 ? 'border-bottom:1px solid #e2e8f0;' : '' }}background:#fff;">
                        <div style="display:flex;align-items:center;gap:.75rem;flex:1;min-width:0;">
                            <span style="width:.625rem;height:.625rem;border-radius:50%;background:{{ $st['dot'] }};flex-shrink:0;"></span>
                            <span style="font-weight:600;font-size:.9375rem;color:#0F2744;overflow:hidden;text-overflow:ellipsis;">{{ $name }}</span>
                        </div>
                        <span style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:{{ $st['text'] }};background:{{ $st['bg'] }};padding:.2rem .6rem;border-radius:999px;min-width:6rem;text-align:center;flex-shrink:0;">{{ $st['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            {{-- Incident / maintenance history (live) --}}
            <div style="margin-bottom:2rem;">
                <h3 style="font-size:.875rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.75rem;">{{ __('public.status_page.incidents_title') }}</h3>
                <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;">
                    @forelse($maintenance as $m)
                    @php $upcoming = $m->starts_at && $m->starts_at->isFuture(); @endphp
                    <div style="padding:1.25rem 1.5rem;background:#fff;border-bottom:1px solid #e2e8f0;">
                        <div style="display:flex;align-items:flex-start;gap:1rem;">
                            <span style="width:.625rem;height:.625rem;border-radius:50%;background:{{ $upcoming ? '#3b82f6' : '#f59e0b' }};flex-shrink:0;margin-top:.35rem;"></span>
                            <div style="flex:1;">
                                <div style="font-weight:700;font-size:.9375rem;margin-bottom:.25rem;">{{ $m->title }}</div>
                                @if($m->message)<div style="font-size:.8125rem;color:#64748b;margin-bottom:.35rem;">{{ $m->message }}</div>@endif
                                <div style="font-size:.75rem;color:#94a3b8;">
                                    {{ $upcoming ? __('public.status_page.scheduled') : __('public.status_page.in_progress') }}
                                    @if($m->starts_at) · {{ $m->starts_at->copy()->utc()->format('d M Y H:i') }} {{ __('public.status_page.utc') }}@endif
                                    @if($m->ends_at) → {{ $m->ends_at->copy()->utc()->format('H:i') }}@endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div style="padding:1.25rem 1.5rem;background:#fff;">
                        <div style="display:flex;align-items:flex-start;gap:1rem;">
                            <span style="width:.625rem;height:.625rem;border-radius:50%;background:#10b981;flex-shrink:0;margin-top:.35rem;"></span>
                            <div>
                                <div style="font-weight:700;font-size:.9375rem;margin-bottom:.25rem;">{{ __('public.status_page.no_incidents') }}</div>
                                <div style="font-size:.8125rem;color:#64748b;">{{ __('public.status_page.no_incidents_desc') }}</div>
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- SLA + subscribe notice --}}
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:1.25rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <i data-lucide="shield-check" style="width:1.5rem;height:1.5rem;color:#2563EB;flex-shrink:0;"></i>
                <div style="flex:1;min-width:200px;">
                    <div style="font-weight:700;color:#1E40AF;margin-bottom:.2rem;">{{ __('public.status_page.subscribe_title') }}</div>
                    <div style="font-size:.8125rem;color:#3B82F6;">{{ __('public.status_page.subscribe_desc') }}</div>
                </div>
                <a href="{{ route('public.sla') }}" class="btn btn-primary" style="font-size:.875rem;padding:.625rem 1.25rem;flex-shrink:0;">{{ __('public.status_page.btn_sla') }}</a>
            </div>

        </div>
    </section>

@endsection
