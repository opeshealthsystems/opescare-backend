@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_timeline_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_timeline_breadcrumb', [], $l))

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_timeline_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.pat_timeline_subtitle', [], $l) }}</p>
    </div>
</div>

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($events->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="history"></i></div>
        <h3>{{ __('public.pat_timeline_empty_title', [], $l) }}</h3>
        <p>{{ __('public.pat_timeline_empty_desc', [], $l) }}</p>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="history"></i> {{ __('public.pat_timeline_title', [], $l) }}</h2>
        <span class="badge badge-primary">{{ $events->count() }}</span>
    </div>
    <div class="panel-body">
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.25rem;">
            @foreach($events as $event)
                @php
                    $meta = match($event->event_type) {
                        'lab_result'   => ['icon' => 'flask-conical', 'badge' => 'badge-teal',    'label' => __('public.pat_timeline_event_lab', [], $l)],
                        'prescription' => ['icon' => 'pill',          'badge' => 'badge-info',    'label' => __('public.pat_timeline_event_prescription', [], $l)],
                        default        => ['icon' => 'stethoscope',   'badge' => 'badge-primary', 'label' => __('public.pat_timeline_event_visit', [], $l)],
                    };
                    $summary = $event->event_type === 'prescription'
                        ? __('public.pat_timeline_rx_items', ['count' => $event->summary], $l)
                        : $event->summary;
                @endphp
                <li style="display:flex;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--p-border, #e5e7eb);">
                    <div style="flex:0 0 auto;width:2.5rem;height:2.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(15,76,129,.08);color:#0F4C81;">
                        <i data-lucide="{{ $meta['icon'] }}" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <div style="flex:1 1 auto;min-width:0;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;">
                            <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                            <span class="td-muted">{{ $event->occurred_at?->isoFormat('LL') ?? '—' }}</span>
                        </div>
                        <div class="td-strong" style="margin-top:.35rem;">{{ $summary ?: '—' }}</div>
                        <div class="td-muted" style="margin-top:.15rem;">
                            <i data-lucide="building-2" style="width:.85rem;height:.85rem;display:inline;vertical-align:middle;"></i>
                            {{ $event->facility_name ?: __('public.pat_timeline_no_facility', [], $l) }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@endsection
