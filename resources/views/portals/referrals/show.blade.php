@extends('layouts.portal')

@section('title', __('referral_desk.show_title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index', ['tab' => $direction]))
@section('breadcrumb_section', __('referral_desk.show_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
@php
    $dash = __('referral_desk.not_recorded');
    $hid  = $referral->patient?->health_id ?? '';
    $tone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($hid !== '' ? $hid : (string) $referral->id) % 6];
    $statusPill = match ((string) $status) {
        'draft'     => 'neutral',
        'sent'      => 'pending',
        'accepted'  => 'normal',
        'completed' => 'ready',
        'rejected', 'cancelled' => 'low',
        default     => 'neutral',
    };
    $actionFor = ['sent' => 'send', 'accepted' => 'accept', 'rejected' => 'reject', 'completed' => 'complete', 'cancelled' => 'cancel'];
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('referral_desk.show_title') }}</h1>
        <p class="od-page__sub">{{ __('referral_desk.direction_' . $direction) }}</p>
    </div>
    <a href="{{ route('documents.new.referral', $referral->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_referral_letter') }}</a>
    <a href="{{ route('portals.referrals.index', ['tab' => $direction]) }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('referral_desk.back') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('record_access'))<div class="odc-note odc-note--info" role="status"><i data-lucide="shield"></i><div>{{ session('record_access') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

<div class="od-grid od-grid--main">

<section class="od-card" aria-labelledby="odc-ref-detail">
    <header class="od-card__head">
        <h2 class="od-card__title" id="odc-ref-detail">{{ __('referral_desk.show_title') }}</h2>
        <span class="od-pill od-pill--dot od-pill--{{ $statusPill }}">@enum($status)</span>
    </header>
    <div class="od-card__body od-detail">
        <div class="od-detail__head">
            <span class="od-avatar od-avatar--lg od-tint--{{ $tone }}" aria-hidden="true"><i data-lucide="user"></i></span>
            <div class="od-detail__main">
                <h3 class="od-detail__name">{{ $hid !== '' ? $hid : '—' }}</h3>
                <div class="od-detail__ref">
                    <span>{{ __('referral_desk.patient') }}</span>
                    <span>{{ $referral->created_at?->translatedFormat('d M Y') ?? '—' }}</span>
                    <span>{{ $referral->created_at?->format('H:i') }}</span>
                </div>
                <div class="od-detail__facts">
                    <span class="od-detail__fact"><i data-lucide="siren"></i> {{ __('referral_desk.urgency.' . $referral->urgency) }}</span>
                    <span class="od-detail__fact"><i data-lucide="calendar-clock"></i> {{ __('referral_desk.expires_at') }}: {{ $referral->expires_at?->translatedFormat('d M Y') ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="od-detail__people">
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="building-2"></i></span>
                <div>
                    <span class="od-person__label">{{ __('referral_desk.from') }}</span>
                    <span class="od-person__name">{{ $referral->referringFacility?->name ?? '—' }}</span>
                </div>
            </div>
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="arrow-right-left"></i></span>
                <div>
                    <span class="od-person__label">{{ __('referral_desk.to') }}</span>
                    <span class="od-person__name">{{ $referral->receivingFacility?->name ?? '—' }}</span>
                    @if($referral->receiving_specialty || $referral->receiving_provider_name)
                    <span class="od-person__sub">{{ collect([$referral->receiving_specialty, $referral->receiving_provider_name])->filter()->implode(' · ') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="odc-section">
            <h3>{{ __('referral_desk.records_included') }}</h3>
            <p>
                @forelse((array) ($referral->included_record_types ?? []) as $type)
                    <span class="od-pill od-pill--normal">{{ is_array(__('referral_desk.record_types')) ? (__('referral_desk.record_types')[$type] ?? $type) : $type }}</span>
                @empty
                    <span class="od-muted">{{ __('referral_desk.records_none') }}</span>
                @endforelse
            </p>
        </div>

        <div class="odc-section">
            <h3>{{ __('referral_desk.reason') }}</h3>
            <p>{{ $referral->reason }}</p>
        </div>

        <div class="odc-section">
            <h3>{{ __('referral_desk.clinical_summary') }}</h3>
            <p class="{{ $referral->clinical_summary ? '' : 'od-muted' }}">{{ $referral->clinical_summary ?: $dash }}</p>
        </div>

        @if($referral->accepted_at || $referral->rejected_at || $referral->completed_at || $referral->cancelled_at)
        <div class="odc-section">
            <dl class="odc-facts odc-facts--2">
                @if($referral->accepted_at)
                    <div><dt>{{ __('referral_desk.accepted_at') }}</dt><dd>{{ $referral->accepted_at->translatedFormat('d M Y H:i') }}</dd></div>
                @endif
                @if($referral->rejected_at)
                    <div><dt>{{ __('referral_desk.rejected_at') }}</dt><dd>{{ $referral->rejected_at->translatedFormat('d M Y H:i') }}</dd></div>
                    <div><dt>{{ __('referral_desk.rejection_reason') }}</dt><dd>{{ $referral->rejection_reason ?: $dash }}</dd></div>
                @endif
                @if($referral->completed_at)
                    <div><dt>{{ __('referral_desk.completed_at') }}</dt><dd>{{ $referral->completed_at->translatedFormat('d M Y H:i') }}</dd></div>
                    <div><dt>{{ __('referral_desk.feedback') }}</dt><dd>{{ $referral->feedback ?: $dash }}</dd></div>
                @endif
                @if($referral->cancelled_at)
                    <div><dt>{{ __('referral_desk.cancelled_at') }}</dt><dd>{{ $referral->cancelled_at->translatedFormat('d M Y H:i') }}</dd></div>
                    <div><dt>{{ __('referral_desk.cancellation_reason') }}</dt><dd>{{ $referral->cancellation_reason ?: $dash }}</dd></div>
                @endif
            </dl>
        </div>
        @endif
    </div>
</section>

<section class="od-card" aria-labelledby="odc-ref-actions">
    <header class="od-card__head"><h2 class="od-card__title" id="odc-ref-actions">{{ __('referral_desk.actions') }}</h2></header>
    <div class="od-card__body">
        @if(empty($moves))
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><i data-lucide="list-checks"></i></span>
                <p class="od-empty__text">{{ __('referral_desk.no_actions') }}</p>
            </div>
        @else
            <div class="odc-form">
            @foreach($moves as $move)
                @php $action = $actionFor[$move]; @endphp
                <form method="POST" action="{{ route('portals.referrals.transition', [$referral->id, $action]) }}" class="odc-form{{ $loop->first ? '' : ' odc-section' }}">
                    @csrf
                    @if($action === 'send')
                        <div class="odc-field">
                            <label class="odc-label" for="ref-expires">{{ __('referral_desk.expires_in_days') }}</label>
                            <input type="number" id="ref-expires" name="expires_in_days" class="od-input" min="1" max="180" placeholder="30">
                        </div>
                    @elseif($action === 'reject' || $action === 'cancel')
                        <div class="odc-field">
                            <label class="odc-label odc-label--req" for="ref-reason-{{ $action }}">{{ __('referral_desk.reason') }}</label>
                            <textarea id="ref-reason-{{ $action }}" name="reason" class="odc-textarea" rows="2" required maxlength="2000"></textarea>
                            <p class="odc-hint">{{ __('referral_desk.reason_required_hint') }}</p>
                        </div>
                    @elseif($action === 'complete')
                        <div class="odc-field">
                            <label class="odc-label" for="ref-feedback">{{ __('referral_desk.feedback') }}</label>
                            <textarea id="ref-feedback" name="feedback" class="odc-textarea" rows="3" maxlength="8000"></textarea>
                        </div>
                    @endif
                    <div class="odc-actions">
                        <button type="submit" class="od-btn {{ in_array($action, ['reject', 'cancel'], true) ? 'od-btn--secondary' : 'od-btn--primary' }}" data-confirm="{{ __('hospital_desk.confirm_referral_' . $action) }}">
                            <i data-lucide="{{ match($action) { 'send' => 'send', 'accept' => 'check', 'reject' => 'x', 'complete' => 'check-check', default => 'ban' } }}"></i>
                            {{ __('referral_desk.action.' . $action) }}
                        </button>
                    </div>
                </form>
            @endforeach
            </div>
        @endif
    </div>
</section>

</div>
</div>
@endsection
