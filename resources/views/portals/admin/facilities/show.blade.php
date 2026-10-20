@extends('layouts.portal')
@section('title', $facility->name)
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $facility->name) % count($gcTones)];
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('admin.facilities.index') }}">{{ __('public.adm_fac_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $facility->name }}</span>
</nav>

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true"><i data-lucide="building-2"></i></span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $facility->name }}</h1>
            <div class="od-detail__ref"><span>{{ $facility->type ? \App\Support\FacilityTypeLabel::label($facility->type) : '—' }}</span><span>{{ $facility->region ?? '—' }}</span></div>
            <div>
                @if(($facility->status ?? '') === 'active')<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_fac_badge_active') }}</span>
                @elseif(($facility->status ?? '') === 'suspended')<span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.adm_fac_badge_suspended') }}</span>
                @elseif(($facility->status ?? '') === 'pending_approval')<span class="od-pill od-pill--pending od-pill--dot">{{ __('public.adm_fac_badge_pending') }}</span>
                @else<span class="od-pill od-pill--neutral od-pill--dot">@enum($facility->status ?? 'pending')</span>@endif
            </div>
        </div>
        <div class="od-row">
            <a href="#edit" class="od-btn od-btn--secondary"><i data-lucide="pencil"></i> {{ __('public.adm_fac_show_btn_edit') }}</a>
            @if(($facility->status ?? '') === 'pending_approval' || ($facility->status ?? '') === 'suspended')
            <form method="POST" action="{{ route('admin.facilities.approve', $facility->id) }}" class="oda-inline-form">@csrf
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check-circle"></i> {{ __('public.adm_fac_show_btn_approve') }}</button>
            </form>
            @endif
        </div>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<nav class="od-tabs">
    <span class="od-tab is-active">{{ __('public.adm_fac_show_tab_overview') }}</span>
</nav>

<div class="od-kpis">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--blue"><i data-lucide="users"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_fac_show_lbl_staff') }}</span>
            <span class="od-kpi__value">{{ number_format($staffCount ?? 0) }}</span>
        </div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--teal"><i data-lucide="heart-pulse"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_fac_show_lbl_patients') }}</span>
            <span class="od-kpi__value">{{ number_format($patientCount ?? 0) }}</span>
        </div>
    </div>
</div>

<div class="od-grid od-grid--main">
<div class="od-stack">
<section class="od-card" id="edit">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="pencil"></i> {{ __('public.adm_fac_show_edit_title') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('admin.facilities.update', $facility->id) }}" class="oda-form">
            @csrf @method('PUT')
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_name') }} *</span>
                <input type="text" name="name" class="od-input" value="{{ old('name', $facility->name) }}" required>
                @error('name')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_type') }} *</span>
                <select name="type" class="od-select" required>
                    @foreach(['hospital','clinic','pharmacy','laboratory','radiology','specialist','other'] as $t)
                    <option value="{{ $t }}" @selected(old('type',$facility->type)===$t)>{{ \App\Support\FacilityTypeLabel::label($t) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_region') }}</span>
                <input type="text" name="region" class="od-input" value="{{ old('region', $facility->region) }}">
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_fac_show_lbl_country') }}</span>
                <input type="text" name="country_code" class="od-input" value="{{ old('country_code', $facility->country_code) }}" maxlength="3">
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('public.adm_fac_show_btn_save') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_danger_title') }}</h2></div>
    <div class="od-card__body od-row">
        @if(($facility->status ?? '') !== 'suspended')
        <button type="button" class="od-btn od-btn--secondary" data-call="opOpenModal" data-args="[&quot;suspend-modal&quot;]"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_btn_suspend') }}</button>
        @endif
        <button type="button" class="od-btn od-btn--danger" data-call="opOpenModal" data-args="[&quot;delete-modal&quot;]"><i data-lucide="trash-2"></i> {{ __('public.adm_fac_show_btn_delete') }}</button>
    </div>
</section>
</div>

<aside class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('public.adm_fac_show_tab_overview') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('public.adm_fac_show_lbl_type') }}</dt><dd>{{ $facility->type ? \App\Support\FacilityTypeLabel::label($facility->type) : '—' }}</dd>
            <dt>{{ __('public.adm_fac_show_lbl_status') }}</dt><dd>@enum($facility->status ?? 'pending')</dd>
            <dt>{{ __('public.adm_fac_show_lbl_license') }}</dt><dd class="oda-mono">{{ $facility->license_number ?? '—' }}</dd>
            <dt>{{ __('public.adm_fac_show_lbl_region') }}</dt><dd>{{ $facility->region ?? '—' }}</dd>
            <dt>{{ __('public.adm_fac_show_lbl_created') }}</dt><dd>{{ $facility->created_at?->translatedFormat('d M Y') ?? '—' }}</dd>
        </dl>
    </div>
</aside>
</div>

<div id="suspend-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="suspend-modal-title"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_modal_suspend_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.facilities.suspend', $facility->id) }}" class="od-card__body od-stack">
            @csrf
            <p>{{ __('public.adm_fac_show_modal_suspend_body', ['name' => $facility->name]) }}</p>
            <textarea name="reason" class="oda-textarea" rows="3" placeholder="{{ __('public.adm_fac_show_modal_suspend_ph') }}" required></textarea>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;suspend-modal&quot;]">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_fac_show_btn_suspend_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_modal_delete_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.facilities.destroy', $facility->id) }}" class="od-card__body od-stack">
            @csrf @method('DELETE')
            <p>{{ __('public.adm_fac_show_modal_delete_body', ['name' => $facility->name]) }}</p>
            <textarea name="reason" class="oda-textarea" rows="3" placeholder="{{ __('public.adm_fac_show_modal_delete_ph') }}" required></textarea>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;delete-modal&quot;]">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_fac_show_btn_delete_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
