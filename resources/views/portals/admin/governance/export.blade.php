@extends('layouts.portal')
@section('title', __('governance_desk.export_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdPill = fn ($s) => match ($s) { 'pending' => 'od-pill--pending', 'approved', 'completed', 'applied', 'fulfilled' => 'od-pill--ready', 'rejected', 'refused' => 'od-pill--urgent', default => 'od-pill--neutral' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('governance_desk.export_title') }}</h1>
        <p class="od-page__sub">{{ __('governance_desk.export_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.governance.index') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('governance_desk.back_to_queue') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="od-grid od-grid--main">
<div class="od-stack">

@if($export->status === 'pending')
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="gavel"></i> {{ __('governance_desk.col_decision') }}</h2>
    </div>
    <div class="od-card__body od-stack">
        {{-- One reason box, two buttons. The reason is required on the GRANT as
             well as the refusal: an unexplained approval of a full-record
             disclosure is not a decision anyone can review later. --}}
        <form method="POST" action="{{ route('portals.admin.governance.exports.approve', $export->id) }}" class="oda-form">
            @csrf
            <label class="oda-label oda-form__full" for="approve-reason"><span class="oda-label__text">{{ __('governance_desk.form_reason') }}</span>
                <textarea id="approve-reason" name="reason" class="oda-textarea" rows="2" required minlength="5" maxlength="1000"
                          placeholder="{{ __('governance_desk.form_reason_placeholder') }}"></textarea>
                <span class="oda-help">{{ __('governance_desk.form_reason_help') }}</span>
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                    <i data-lucide="check"></i> {{ __('governance_desk.btn_approve') }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('portals.admin.governance.exports.refuse', $export->id) }}" class="oda-form">
            @csrf
            <label class="oda-label oda-form__full" for="refuse-reason"><span class="oda-label__text">{{ __('governance_desk.form_reason') }}</span>
                <textarea id="refuse-reason" name="reason" class="oda-textarea" rows="2" required minlength="5" maxlength="1000"
                          placeholder="{{ __('governance_desk.form_reason_placeholder') }}"></textarea>
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--danger od-btn--sm">
                    <i data-lucide="x"></i> {{ __('governance_desk.btn_refuse') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="history"></i> {{ __('governance_desk.history_title') }}</h2>
        <span class="od-card__count">{{ $history->count() }}</span>
    </div>
    @if($history->isEmpty())
        <div class="od-card__body"><div class="od-empty"><p class="od-empty__text">{{ __('governance_desk.history_empty') }}</p></div></div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_when') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                    <th>{{ __('governance_desk.col_actor') }}</th>
                    <th>{{ __('governance_desk.col_reason') }}</th>
                </tr></thead>
                <tbody>
                @foreach($history as $ev)
                    <tr>
                        <td>{{ optional($ev->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code class="oda-mono">{{ $ev->action_type }}</code></td>
                        <td>{{ $ev->actor_role ?: '—' }}</td>
                        <td>{{ $ev->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="download"></i> {{ __('governance_desk.export_title') }}</h2>
        <span class="od-pill {{ $gdPill($export->status) }}">{{ __('governance_desk.status_' . $export->status) }}</span>
    </div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('governance_desk.col_filed') }}</dt>
            <dd>{{ optional($export->created_at)->format('Y-m-d H:i') }}</dd>

            <dt>{{ __('governance_desk.export_type') }}</dt>
            <dd><code class="oda-mono">{{ $export->export_type }}</code></dd>

            <dt>{{ __('governance_desk.export_scope') }}</dt>
            <dd><code class="oda-mono">{{ json_encode($export->scope_json ?: []) }}</code></dd>

            <dt>{{ __('governance_desk.export_expires') }}</dt>
            <dd>{{ $export->expires_at ? $export->expires_at->format('Y-m-d H:i') : __('governance_desk.export_no_expiry') }}</dd>

            @if(filled($export->decision_reason))
            <dt>{{ __('governance_desk.export_decision_reason') }}</dt>
            <dd>{{ $export->decision_reason }}</dd>
            @endif
        </dl>
    </div>
</div>
</div>

</div>
@endsection
