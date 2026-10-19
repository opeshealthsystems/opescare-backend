{{-- Group D import: web page over the platform's BreachWorkflowService
     (open → contain → notify authority → close; 48 h warning, 72 h deadline). --}}
@extends('layouts.portal')
@section('title', __('security_ops.br_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.br_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.br_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.security.incidents') }}" class="od-btn od-btn--secondary"><i data-lucide="siren"></i> {{ __('security_ops.nav_incidents') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif
@if(count($overdue) > 0)
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="alarm-clock"></i><div class="oda-ga-alert__body">{{ __('security_ops.br_regulatory_warning', ['count' => count($overdue)]) }}</div></div>
@endif

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('security_ops.br_open') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.security.breaches.store') }}" class="oda-form oda-form--3">
            @csrf
            <label class="oda-label oda-form__full" for="br_title">
                <span class="oda-label__text">{{ __('security_ops.br_lbl_title') }}</span>
                <input id="br_title" type="text" name="title" class="od-input" maxlength="255">
            </label>
            <label class="oda-label oda-form__full" for="br_desc">
                <span class="oda-label__text">{{ __('security_ops.br_lbl_description') }} *</span>
                <textarea id="br_desc" name="description" rows="3" required class="oda-textarea"></textarea>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('security_ops.br_lbl_type') }}</span>
                <select name="breach_type" class="od-select" aria-label="{{ __('security_ops.br_lbl_type') }}">
                    @foreach(['unauthorized_access', 'data_leak', 'ransomware', 'lost_device', 'insider_threat'] as $t)
                        <option value="{{ $t }}">{{ __('security_ops.br_type_'.$t) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('security_ops.br_lbl_severity') }}</span>
                <select name="severity" class="od-select" aria-label="{{ __('security_ops.br_lbl_severity') }}">
                    @foreach(['critical', 'high', 'medium', 'low'] as $sev)
                        <option value="{{ $sev }}">@enum($sev, 'severity')</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('security_ops.br_lbl_affected') }}</span>
                <input type="number" min="0" name="estimated_affected_records" class="od-input" placeholder="{{ __('security_ops.br_lbl_affected') }}" aria-label="{{ __('security_ops.br_lbl_affected') }}">
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="plus-circle"></i> {{ __('security_ops.br_btn_open') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.br_title') }} <span class="od-card__count">({{ $breaches->total() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($breaches->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.br_empty') }}</h3>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.br_lbl_title') }}</th>
                    <th>{{ __('security_ops.br_lbl_severity') }}</th>
                    <th>{{ __('security_ops.br_col_status') }}</th>
                    <th>{{ __('security_ops.br_col_discovered') }}</th>
                    <th>{{ __('security_ops.br_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($breaches as $breach)
                    @php
                        $sevPill = match($breach->severity) { 'critical' => 'od-pill--urgent', 'high' => 'od-pill--pending', 'medium' => 'od-pill--normal', default => 'od-pill--neutral' };
                        $stPill = match($breach->status) { 'open' => 'od-pill--urgent', 'investigating' => 'od-pill--pending', 'contained' => 'od-pill--normal', 'closed' => 'od-pill--ready', default => 'od-pill--neutral' };
                    @endphp
                    <tr>
                        <td data-label="{{ __('security_ops.br_lbl_title') }}">
                            <span class="od-table__name">{{ $breach->title }}</span>
                            <span class="od-table__sub">{{ __('security_ops.br_type_'.$breach->breach_type) }}</span>
                            @if(in_array($breach->id, $overdue))<span class="od-pill od-pill--urgent">{{ __('security_ops.br_badge_regulatory') }}</span>@endif
                        </td>
                        <td data-label="{{ __('security_ops.br_lbl_severity') }}"><span class="od-pill {{ $sevPill }}">@enum($breach->severity, 'severity')</span></td>
                        <td data-label="{{ __('security_ops.br_col_status') }}">
                            <span class="od-pill {{ $stPill }}">{{ __('security_ops.br_status_'.$breach->status) }}</span>
                            @if($breach->reported_to_authority_at)<span class="od-table__sub">{{ __('security_ops.br_notified_at', ['when' => $breach->reported_to_authority_at->format('d/m/Y H:i')]) }}</span>@endif
                        </td>
                        <td data-label="{{ __('security_ops.br_col_discovered') }}" class="oda-ga-muted">{{ $breach->discovered_at?->format('d/m/Y H:i') }}</td>
                        <td data-label="{{ __('security_ops.br_col_actions') }}">
                            @if(in_array($breach->status, ['open', 'investigating']))
                                <form method="POST" action="{{ route('portals.admin.security.breaches.contain', $breach->id) }}" class="oda-ga-rowform">
                                    @csrf
                                    <input type="text" name="notes" required class="od-input" placeholder="{{ __('security_ops.br_ph_contain') }}" aria-label="{{ __('security_ops.br_ph_contain') }}">
                                    <button type="submit" class="od-btn od-btn--row od-btn--sm">{{ __('security_ops.br_btn_contain') }}</button>
                                </form>
                            @endif
                            @if(! $breach->reported_to_authority_at && $breach->status !== 'closed')
                                <form method="POST" action="{{ route('portals.admin.security.breaches.notify', $breach->id) }}" class="oda-ga-rowform">
                                    @csrf
                                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="send"></i> {{ __('security_ops.br_btn_notify') }}</button>
                                </form>
                            @endif
                            @if($breach->status !== 'closed')
                                <form method="POST" action="{{ route('portals.admin.security.breaches.close', $breach->id) }}" data-confirm="{{ __('admin_desk.confirm_close_breach') }}" class="oda-ga-rowform">
                                    @csrf
                                    <input type="text" name="resolution" required class="od-input" placeholder="{{ __('security_ops.br_ph_resolution') }}" aria-label="{{ __('security_ops.br_ph_resolution') }}">
                                    <button type="submit" class="od-btn od-btn--ghost od-btn--sm">{{ __('security_ops.br_btn_close') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($breaches->hasPages())<div class="od-card__foot">{{ $breaches->links() }}</div>@endif
</section>
</div>
@endsection
