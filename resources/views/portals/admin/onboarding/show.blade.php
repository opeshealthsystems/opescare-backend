{{-- Imported from the platform (portals/admin/onboarding/show). Finder: keys in onboarding.admin_portal.*,
     the automatic readiness checks are shown and must all pass before approval. --}}
@extends('layouts.portal')
@section('title', $facility->name . ' — ' . __('onboarding.admin_portal.show_title_suffix'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')

@php
    $statusColor = match($readiness->status) {
        'approved' => 'ready', 'ready_for_approval' => 'normal', default => 'pending',
    };
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $facility->name) % count($gcTones)];
    $pct = $totalCount > 0 ? round($completedCount / $totalCount * 100) : 0;
@endphp
<div class="od-page">

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true"><i data-lucide="rocket"></i></span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $facility->name }}</h1>
            <div class="od-row">
                <span class="od-pill od-pill--dot od-pill--{{ $statusColor }}">{{ __('onboarding.admin_portal.status_'.$readiness->status) }}</span>
                <span class="oda-gc-muted">{{ __('onboarding.admin_portal.items_completed', ['done' => $completedCount, 'total' => $totalCount]) }}</span>
                @if($readiness->status === 'approved')
                <span class="od-pill od-pill--ready">
                    <i data-lucide="check-circle-2"></i> {{ __('onboarding.admin_portal.badge_golive_approved') }}
                </span>
                @endif
            </div>
        </div>
        <div class="od-row">
            <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="od-btn od-btn--ghost"><i data-lucide="gauge"></i> {{ __('onboarding.admin_portal.btn_readiness') }}</a>
        </div>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-stack">
{{-- Progress --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('onboarding.admin_portal.progress_label') }}</h2>
        <span class="od-card__count">{{ $pct }}%</span>
    </div>
    <div class="od-card__body">
        <progress class="oda-gc-progress oda-gc-progress--lg{{ $pct == 100 ? ' is-done' : '' }}" value="{{ $pct }}" max="100">{{ $pct }}%</progress>
        @if(!empty($missingItems))
        <p class="oda-gc-note oda-gc-note--alert">{{ __('onboarding.admin_portal.remaining') }} {{ implode(', ', array_map(fn ($k) => $labels[$k] ?? $k, $missingItems)) }}</p>
        @endif
    </div>
</section>

{{-- Automatic Finder readiness checks --}}
@include('portals.admin._readiness_checks', ['automatic' => $automatic])

<div class="od-grid od-grid--main">
{{-- Checklist --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="list-checks"></i> {{ __('onboarding.admin_portal.checklist_title') }}</h2>
    </div>
    <ul class="od-list">
        @foreach($labels as $key => $label)
            @php $done = $checklist[$key] ?? false; @endphp
            <li class="od-list__row">
                <span class="od-list__icon od-tint--{{ $done ? 'green' : 'amber' }}">
                    <i data-lucide="{{ $done ? 'check-circle-2' : 'circle' }}"></i>
                </span>
                <div class="od-list__text">
                    <span class="od-list__title{{ $done ? ' oda-gc-done' : '' }}">{{ $label }}</span>
                </div>
                @if($readiness->status !== 'approved' && $key !== 'go_live_approval_recorded')
                <form method="POST" action="{{ route('portals.admin.onboarding.mark', $facility->id) }}" class="oda-inline-form">
                    @csrf
                    <input type="hidden" name="item" value="{{ $key }}">
                    <input type="hidden" name="complete" value="{{ $done ? '0' : '1' }}">
                    <button type="submit" class="od-btn {{ $done ? 'od-btn--ghost' : 'od-btn--row' }} od-btn--sm">
                        {{ $done ? __('onboarding.admin_portal.btn_mark_incomplete') : __('onboarding.admin_portal.btn_mark_complete') }}
                    </button>
                </form>
                @elseif($readiness->status === 'approved')
                <span class="od-list__meta">{{ __('onboarding.admin_portal.locked') }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</section>

<div class="od-stack">
{{-- Approval info --}}
@if($readiness->approved_at)
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="check-circle-2"></i> {{ __('onboarding.admin_portal.approved_head') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('onboarding.admin_portal.approved_on') }}</dt><dd>{{ $readiness->approved_at->format('d/m/Y H:i') }}</dd>
            @if($readiness->approval_note)
            <dt>{{ __('onboarding.admin_portal.note') }}</dt><dd>{{ $readiness->approval_note }}</dd>
            @endif
        </dl>
    </div>
</section>
@endif

{{-- Risks --}}
@if(!empty($risks))
<div class="oda-gc-flash oda-gc-flash--warn">
    <i data-lucide="alert-triangle"></i>
    <div>
        <strong>{{ __('onboarding.admin_portal.risks_label') }}</strong>
        <ul class="oda-gc-errlist">
            @foreach($risks as $risk)
            <li>{{ $risk }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- Approve go-live (inline form; the platform used a modal) --}}
@if($readiness->status === 'ready_for_approval')
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="check-circle"></i> {{ __('onboarding.admin_portal.modal_title') }}</h2></div>
    <div class="od-card__body od-stack">
        <p class="oda-gc-note">{{ __('onboarding.admin_portal.modal_desc', ['name' => $facility->name]) }}</p>
        <form method="POST" action="{{ route('portals.admin.onboarding.approve', $facility->id) }}" class="od-stack">
            @csrf
            <label class="oda-label" for="approval_note">
                <span class="oda-label__text">{{ __('onboarding.admin_portal.modal_lbl_note') }} *</span>
                <textarea id="approval_note" name="approval_note" rows="4" required class="oda-textarea"
                          placeholder="{{ __('onboarding.admin_portal.modal_ph_note') }}"></textarea>
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary" @disabled(! empty($automatic['missing']))>{{ __('onboarding.admin_portal.modal_btn_confirm') }}</button>
            </div>
        </form>
    </div>
</section>
@endif
</div>
</div>
</div>

</div>
@endsection
