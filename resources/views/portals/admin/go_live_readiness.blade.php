@extends('layouts.portal')
@section('title', ($facility->name ?? 'Facility') . ' — Go-Live Readiness')
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

@php
    $total = count($labels);
    $done  = collect($labels)->filter(fn($l, $key) => ($readiness->checklist_json[$key] ?? false))->count();
    $pct   = $total > 0 ? round($done / $total * 100) : 0;
    $ringColor = $readiness->can_go_live ? 'var(--p-success)' : ($pct >= 50 ? 'var(--p-warning)' : 'var(--p-danger)');
@endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.onboarding') }}">{{ __('public.adm_golive_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $facility->name }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="rocket"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $facility->name }} — {{ __('public.adm_golive_page_title') }}</h2>
        <div class="entity-head__sub">
            <span class="td-muted text-sm">{{ __('public.adm_golive_facility_status') }} {{ $facility->status }}</span>
            <span class="badge badge-neutral badge-sm">{{ $facility->type }}</span>
        </div>
    </div>
    <div class="entity-head__spacer"></div>
    <span class="badge {{ $readiness->can_go_live ? 'badge-success' : 'badge-danger' }}">@enum($readiness->status)</span>
</div>

{{-- Stepper --}}
@php
    $steps = [
        [__('public.adm_golive_step_onboarding'), $total > 0],
        [__('public.adm_golive_step_checklist'), $done > 0],
        [__('public.adm_golive_step_review'), $done === $total && $total > 0],
        [__('public.adm_golive_step_golive'), $readiness->can_go_live],
    ];
    $activeIndex = $readiness->can_go_live ? 3 : ($done === $total && $total > 0 ? 2 : ($done > 0 ? 1 : 0));
@endphp
<div class="stepper">
    @foreach($steps as $i => [$label, $isDone])
    <div class="stepper__step {{ $i < $activeIndex || $isDone ? 'done' : '' }} {{ $i === $activeIndex ? 'active' : '' }}">
        <div class="stepper__dot">
            @if($i < $activeIndex || ($isDone && $i !== $activeIndex))<i data-lucide="check"></i>@else{{ $i + 1 }}@endif
        </div>
        <span class="stepper__label">{{ $label }}</span>
    </div>
    @endforeach
</div>

<div class="grid-2 mb-6">
    {{-- Score ring --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="gauge"></i> {{ __('public.adm_golive_section_result') }}</h3></div>
        <div class="panel-body">
            <div class="ring-row">
                <div class="score-ring" style="--ring-pct: {{ $pct }}; --ring-color: {{ $ringColor }};">
                    <div class="score-ring__inner">
                        <div class="score-ring__value">{{ $pct }}%</div>
                        <div class="score-ring__label">{{ __('public.adm_golive_ring_ready') }}</div>
                    </div>
                </div>
                <div>
                    <table class="kv-table">
                        <tr><td>{{ __('public.adm_golive_can_go_live') }}</td>
                            <td class="kv-strong">{{ $readiness->can_go_live ? __('public.adm_golive_yes') : __('public.adm_golive_no') }}</td></tr>
                        <tr><td>{{ __('public.adm_golive_missing') }}</td>
                            <td class="kv-strong">{{ empty($missingItems) ? __('public.adm_golive_none') : implode(', ', $missingItems) }}</td></tr>
                        <tr><td>{{ __('public.adm_golive_risks') }}</td>
                            <td class="kv-strong">{{ empty($missingItems) ? __('public.adm_golive_none') : __('public.adm_golive_risks_blocked') }}</td></tr>
                        @if ($readiness->approval_note)
                        <tr><td>{{ __('public.adm_golive_approval_note') }}</td>
                            <td class="kv-strong">{{ $readiness->approval_note }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Checklist scorecard --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="list-checks"></i> {{ __('public.adm_golive_section_checklist') }}</h3></div>
        <div class="panel-body">
            <div class="checklist">
                @foreach ($labels as $key => $label)
                    @php $ok = $readiness->checklist_json[$key] ?? false; @endphp
                    <div class="checklist__item">
                        <span class="checklist__icon {{ $ok ? 'ok' : 'fail' }}">
                            <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"></i>
                        </span>
                        <div class="checklist__body">
                            <div class="checklist__title">{{ $label }}</div>
                        </div>
                        <span class="badge {{ $ok ? 'badge-success' : 'badge-danger' }} badge-sm">
                            {{ $ok ? __('public.adm_golive_complete') : __('public.adm_golive_missing_status') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
