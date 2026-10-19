@extends('layouts.portal')
@section('title', __('admin_desk.incident_detail'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $sevBadge = match($incident->severity) {
        'critical' => 'od-pill--urgent',
        'high' => 'od-pill--pending',
        'medium' => 'od-pill--normal',
        default => 'od-pill--neutral',
    };
    $stBadge = match($incident->status) {
        'open' => 'od-pill--urgent',
        'investigating' => 'od-pill--pending',
        'contained' => 'od-pill--normal',
        'resolved', 'closed' => 'od-pill--ready',
        default => 'od-pill--neutral',
    };
@endphp

<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $incident->incident_type }}</h1>
        <p class="od-page__sub od-row">
            <span class="od-pill {{ $sevBadge }}">@enum($incident->severity, 'severity')</span>
            <span class="od-pill {{ $stBadge }}">@enum($incident->status)</span>
            @if($incident->is_demo)<span class="od-pill od-pill--neutral">{{ __('security_ops.badge_demo') }}</span>@endif
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.security.incidents') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ $errors->first() }}</div></div>@endif

<div class="oda-split">
<div class="od-stack">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('security_ops.inc_col_summary') }}</h2></div>
        <div class="od-card__body"><div class="oda-pre">{{ $incident->summary }}</div></div>
    </section>

    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('security_ops.inc_modal_update_title') }}</h2></div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.security.incidents.update', $incident->id) }}" class="oda-form">
                @csrf
                <input type="hidden" name="stay" value="1">
                <label class="oda-label oda-form__full" for="upd-status">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_status') }}</span>
                    <select id="upd-status" name="status" class="od-select" required>
                        @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $incident->status) === $st)>@enum($st)</option>
                        @endforeach
                    </select>
                </label>
                <label class="oda-label oda-form__full" for="upd-summary">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_summary') }}</span>
                    <textarea id="upd-summary" name="summary" class="oda-textarea" rows="5" maxlength="2000">{{ old('summary', $incident->summary) }}</textarea>
                </label>
                <label class="oda-label" for="upd-contained">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_contained_at') }}</span>
                    <input id="upd-contained" type="date" name="contained_at" class="od-input" value="{{ old('contained_at', $incident->contained_at?->format('Y-m-d')) }}">
                </label>
                <label class="oda-label" for="upd-resolved">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_resolved_at') }}</span>
                    <input id="upd-resolved" type="date" name="resolved_at" class="od-input" value="{{ old('resolved_at', $incident->resolved_at?->format('Y-m-d')) }}">
                </label>
                <div class="oda-form__actions">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('security_ops.inc_btn_save') }}</button>
                </div>
            </form>
        </div>
    </section>
</div>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('admin_desk.details') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt class="oda-dl__k">{{ __('security_ops.inc_col_detected') }}</dt><dd class="oda-dl__v">{{ $incident->detected_at?->isoFormat('lll') ?? '—' }}</dd>
            <dt class="oda-dl__k">{{ __('security_ops.inc_form_contained_at') }}</dt><dd class="oda-dl__v">{{ $incident->contained_at?->isoFormat('lll') ?? '—' }}</dd>
            <dt class="oda-dl__k">{{ __('security_ops.inc_col_resolved') }}</dt><dd class="oda-dl__v">{{ $incident->resolved_at?->isoFormat('lll') ?? '—' }}</dd>
            <dt class="oda-dl__k">{{ __('admin_desk.opened_by') }}</dt><dd class="oda-dl__v">{{ $incident->creator?->name ?? '—' }}</dd>
            <dt class="oda-dl__k">{{ __('admin_desk.created_at') }}</dt><dd class="oda-dl__v">{{ $incident->created_at?->isoFormat('lll') ?? '—' }}</dd>
            <dt class="oda-dl__k">{{ __('admin_desk.reference') }}</dt><dd class="oda-dl__v oda-mono">{{ $incident->id }}</dd>
        </dl>
    </div>
</section>
</div>
</div>
@endsection
