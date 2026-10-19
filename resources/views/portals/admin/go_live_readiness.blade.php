{{-- Imported from the platform (portals/admin/go_live_readiness). Finder: /admin/facilities/{id}/readiness,
     keys in lang/{en,fr}/readiness.php, automatic checks + stored score snapshots added. --}}
@extends('layouts.portal')
@section('title', $facility->name . ' — ' . __('readiness.page_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')

@php
    $total = count($labels);
    $done  = collect($checklist)->filter(fn ($v) => $v === true)->count();
    $pct   = $total > 0 ? round($done / $total * 100) : 0;
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $facility->name }} — {{ __('readiness.page_title') }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--neutral">{{ $facility->type }}</span>
            <span class="od-pill {{ $readiness->can_go_live ? 'od-pill--ready' : 'od-pill--urgent' }}">{{ __('onboarding.admin_portal.status_'.$readiness->status) }}</span>
        </p>
    </div>
    <div class="od-row">
        <form method="POST" action="{{ route('portals.admin.facilities.readiness.score', $facility->id) }}" class="oda-inline-form">
            @csrf
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="refresh-cw"></i> {{ __('readiness.btn_compute') }}</button>
        </form>
        <a href="{{ route('portals.admin.onboarding.show', $facility->id) }}" class="od-btn od-btn--primary"><i data-lucide="list-checks"></i> {{ __('readiness.btn_manage') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<div class="od-grid od-grid--main">
<div class="od-stack">
    @include('portals.admin._readiness_checks', ['automatic' => $automatic])

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="list-checks"></i> {{ __('readiness.section_checklist') }}</h2>
            <span class="od-card__count">{{ $done }}/{{ $total }} ({{ $pct }}%)</span>
        </div>
        <div class="od-card__body od-card__body--flush">
            <div class="od-list">
                @foreach ($labels as $key => $label)
                    @php $ok = $checklist[$key] ?? false; @endphp
                    <div class="od-list__row">
                        <span class="od-list__icon {{ $ok ? 'od-tint--green' : 'od-tint--rose' }}">
                            <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"></i>
                        </span>
                        <div class="od-list__text"><div class="od-list__title">{{ $label }}</div></div>
                        <span class="od-pill {{ $ok ? 'od-pill--ready' : 'od-pill--urgent' }}">
                            {{ $ok ? __('readiness.done') : __('readiness.todo') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @if ($readiness->approval_note)
            <div class="od-card__foot"><p class="oda-gd-text"><strong>{{ __('onboarding.admin_portal.note') }}</strong> {{ $readiness->approval_note }}</p></div>
        @endif
    </div>
</div>

<div class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="history"></i> {{ __('readiness.latest_snapshot') }}</h2></div>
    <div class="od-card__body">
        @if($latestScore)
            <dl class="oda-dl">
                <dt>{{ __('readiness.snap_overall') }}</dt><dd><strong>{{ (int) $latestScore->overall_score }}%</strong></dd>
                <dt>{{ __('readiness.snap_staff') }}</dt><dd>{{ (int) $latestScore->staff_score }}%</dd>
                <dt>{{ __('readiness.snap_config') }}</dt><dd>{{ (int) $latestScore->config_score }}%</dd>
                <dt>{{ __('readiness.snap_data') }}</dt><dd>{{ (int) $latestScore->data_score }}%</dd>
                <dt>{{ __('readiness.snap_when') }}</dt><dd>{{ $latestScore->calculated_at?->format('d/m/Y H:i') }}</dd>
            </dl>
        @else
            <div class="od-empty"><p class="od-empty__text">{{ __('readiness.no_snapshot') }}</p></div>
        @endif
    </div>
</div>
</div>

</div>
@endsection
