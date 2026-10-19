@extends('layouts.portal')
@section('title', __('admin_desk.credential_detail', [], app()->getLocale()))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@section('content')

@php
    $l = app()->getLocale();
    $ctKey   = 'professional.ct_' . $credential->credential_type;
    $ctLabel = \Illuminate\Support\Facades\Lang::has($ctKey) ? __($ctKey, [], $l) : $credential->credential_type;
    $roleKey   = $credential->provider?->role?->name;
    $roleLabel = $roleKey && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $roleKey)
        ? __('portal.role_name.' . $roleKey)
        : $credential->provider?->role?->description;
    $stBadge = match ($credential->status) {
        'active'          => 'od-pill--ready',
        'suspended', 'revoked' => 'od-pill--urgent',
        'expired'         => 'od-pill--pending',
        default           => 'od-pill--neutral',
    };
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $credential->provider?->email) % count($gcTones)];
    $gcInit = mb_strtoupper(collect(preg_split('/\s+/', trim((string) $credential->provider?->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $ctLabel }}</h1>
        <p class="od-page__sub od-row">
            <span class="od-pill od-pill--dot {{ $stBadge }}">@enum($credential->status)</span>
            @if($credential->verified_at)
                <span class="od-pill od-pill--ready"><i data-lucide="badge-check"></i> {{ __('admin_desk.verified', [], $l) }}</span>
            @elseif($pending)
                <span class="od-pill od-pill--pending">{{ __('admin_desk.awaiting_review', [], $l) }}</span>
            @endif
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.verifications.index') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list', [], $l) }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-grid od-grid--main">
<div class="od-stack">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="user"></i> {{ __('professional.ver_provider', [], $l) }}</h2></div>
        <div class="od-card__body od-stack">
            <div class="od-table__person">
                <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true">{{ $gcInit }}</span>
                <div>
                    <span class="od-detail__name">{{ $credential->provider?->name ?? '—' }}</span>
                    @if($credential->provider)
                    <a href="{{ route('admin.users.show', $credential->provider->id) }}" class="od-card__link"><i data-lucide="external-link"></i> {{ __('admin_desk.open_user', [], $l) }}</a>
                    @endif
                </div>
            </div>
            <dl class="oda-dl">
                <dt>{{ __('admin_desk.name', [], $l) }}</dt><dd>{{ $credential->provider?->name ?? '—' }}</dd>
                <dt>{{ __('admin_desk.email', [], $l) }}</dt><dd>{{ $credential->provider?->email ?? '—' }}</dd>
                <dt>{{ __('admin_desk.role', [], $l) }}</dt><dd>{{ $roleLabel ?? '—' }}</dd>
            </dl>
        </div>
    </section>

    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="award"></i> {{ __('professional.ver_credential', [], $l) }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('professional.ver_number', [], $l) }}</dt><dd class="oda-mono">{{ $credential->credential_number ?: '—' }}</dd>
                <dt>{{ __('professional.ver_issuer', [], $l) }}</dt><dd>{{ $credential->issuing_body ?: '—' }}</dd>
                <dt>{{ __('admin_desk.issued_on', [], $l) }}</dt><dd>{{ $credential->issued_date?->translatedFormat('d M Y') ?? '—' }}</dd>
                <dt>{{ __('admin_desk.expires_on', [], $l) }}</dt><dd>{{ $credential->expiry_date?->translatedFormat('d M Y') ?? __('admin_desk.no_expiry', [], $l) }}</dd>
                <dt>{{ __('admin_desk.document', [], $l) }}</dt><dd>{{ $credential->document_path ? __('admin_desk.document_on_file', [], $l) : __('admin_desk.document_none', [], $l) }}</dd>
                <dt>{{ __('professional.ver_submitted', [], $l) }}</dt><dd>{{ $credential->created_at?->isoFormat('lll') ?? '—' }}</dd>
            </dl>
        </div>
    </section>
</div>

<div class="od-stack">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="shield-check"></i> {{ __('admin_desk.decision', [], $l) }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('admin_desk.reviewed_by', [], $l) }}</dt><dd>{{ $credential->verifier?->name ?? '—' }}</dd>
                <dt>{{ __('admin_desk.verified_at', [], $l) }}</dt><dd>{{ $credential->verified_at?->isoFormat('lll') ?? '—' }}</dd>
                <dt>{{ __('admin_desk.notes', [], $l) }}</dt><dd class="oda-gc-prewrap">{{ $credential->notes ?: '—' }}</dd>
            </dl>
        </div>
    </section>

    @if($pending)
    <section class="od-card">
        <div class="od-card__body od-stack">
            <form method="POST" action="{{ route('portals.admin.verifications.verify', $credential->id) }}">
                @csrf
                <button type="submit" class="od-btn od-btn--primary od-btn--block"><i data-lucide="check"></i> {{ __('professional.ver_verify', [], $l) }}</button>
            </form>
            <form method="POST" action="{{ route('portals.admin.verifications.reject', $credential->id) }}" data-confirm="{{ __('admin_desk.confirm_reject_credential', [], $l) }}" class="od-stack">
                @csrf
                <label class="oda-label" for="notes">
                    <span class="oda-label__text">{{ __('professional.ver_reject_reason', [], $l) }}</span>
                    <textarea name="notes" id="notes" rows="3" class="oda-textarea" maxlength="500"></textarea>
                </label>
                <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--danger"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], $l) }}</button></div>
            </form>
        </div>
    </section>
    @else
    <section class="od-card"><div class="od-card__body oda-gc-muted">{{ __('admin_desk.credential_decided_hint', [], $l) }}</div></section>
    @endif
</div>
</div>

</div>
@endsection
