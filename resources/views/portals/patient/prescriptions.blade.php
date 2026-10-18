@extends('layouts.portal')

@section('title', __('public.portal.prescriptions_title', [], app()->getLocale()))
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.prescriptions_breadcrumb', [], app()->getLocale()))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.prescriptions_title', [], $l) }}</h1>
            <p class="pf-sub">{{ __('public.portal.prescriptions_subtitle', [], $l) }}</p>
        </div>
    </div>

    @if(!$patient)
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.portal.no_profile_found_title', [], $l) }}</h2>
        <p class="f-muted ods-1386d50">{{ __('public.portal.no_profile_found_desc', [], $l) }}</p>
    </div>
    @elseif($prescriptions->isEmpty())
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'pill', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.portal.no_prescriptions_title', [], $l) }}</h2>
        <p class="f-muted ods-1386d50">{{ __('public.portal.no_prescriptions_desc', [], $l) }}</p>
    </div>
    @else

    <div class="pf-stack">
        @foreach($prescriptions as $rx)
        <div class="f-card">
            <h2 class="pf-card-head ods-901210d">
                @include('partials.finder.icon', ['name' => 'pill', 'size' => 18])
                {{ __('public.portal.rx_heading_prefix', [], $l) }} {{ $rx->prescribed_at?->translatedFormat('d M Y') ?? (__('public.portal.rx_unknown_date', [], $l)) }}
                @php
                    $stCls = match($rx->statusColor()) {
                        'success' => 'f-badge--good', 'info' => '', default => 'pf-badge--muted'
                    };
                @endphp
                <span class="f-badge {{ $stCls }} ods-8345637">@enum($rx->status)</span>
            </h2>
            @if($rx->facility)
            <p class="f-small f-muted ods-53efc3e">
                @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
                {{ $rx->facility->name }}
            </p>
            @endif
            {{-- The code any OpesCare pharmacy looks this prescription up by —
                 the patient needs it when the paper is left at home. --}}
            @if(filled($rx->reference))
            <p class="f-small ods-633a6d0" title="{{ __('prescriber.rx_code_hint') }}">
                @include('partials.finder.icon', ['name' => 'qr-code', 'size' => 14])
                {{ __('prescriber.rx_code') }}: <strong data-rx-code>{{ $rx->reference }}</strong>
            </p>
            <p class="f-small f-muted ods-4e3268c">{{ __('prescriber.rx_code_hint') }}</p>
            @endif

            @if($rx->items->isNotEmpty())
            <div class="ods-b1b744e">
                @foreach($rx->items as $item)
                <div class="ods-1625a0f">
                    <div class="ods-3f9f96c">
                        <div class="ods-1bdf003">{{ $item->drug_name }}</div>
                        <div class="f-small f-muted">
                            {{ $item->dose }} ({{ $item->route }}) &middot; {{ $item->frequency }}
                            @if($item->duration_days)
                                &middot; {{ $item->duration_days }} {{ __('public.portal.lbl_days', [], $l) }}
                            @endif
                        </div>
                    </div>
                    <span class="f-badge {{ $item->isDispensed() ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($item->status)</span>
                </div>
                @endforeach
            </div>
            @endif

            @if(in_array($rx->status, ['dispensed', 'active', 'partially_dispensed']))
            <div class="pf-appt__actions">
                <button type="button" class="f-btn" data-call="pfOpenModal" data-args="[&quot;refill-rx-{{ $rx->id }}&quot;]">
                    @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 16])
                    {{ __('public.portal.btn_request_refill', [], $l) }}
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if(method_exists($prescriptions, 'links') && $prescriptions->hasPages())
    <div class="pf-pagination">
        {{ $prescriptions->links() }}
    </div>
    @endif

    @foreach($prescriptions as $rx)
        @if(in_array($rx->status, ['dispensed', 'active', 'partially_dispensed']))
        <div id="refill-rx-{{ $rx->id }}" class="pf-modal-backdrop" hidden>
            <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="refill-rx-{{ $rx->id }}-title">
                <h3 id="refill-rx-{{ $rx->id }}-title">
                    @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 18])
                    {{ __('public.portal.modal_refill_title', [], $l) }}
                </h3>
                <form method="POST" action="{{ route('portals.patient.prescriptions.refill', $rx->id) }}">
                    @csrf
                    <p>{{ __('public.portal.modal_refill_body', [], $l) }}</p>
                    <div class="pf-modal__actions">
                        <button type="button" class="f-btn" data-call="pfCloseModal" data-args="[&quot;refill-rx-{{ $rx->id }}&quot;]">{{ __('public.portal.btn_cancel', [], $l) }}</button>
                        <button type="submit" class="f-btn f-btn--primary">{{ __('public.portal.btn_request_refill', [], $l) }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endforeach

    @endif
</div>

@endsection

@section('scripts')
<script @cspNonce>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
