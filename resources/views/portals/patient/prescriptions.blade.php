@extends('layouts.portal')

@section('title', __('public.portal.prescriptions_title', [], app()->getLocale()) ?: 'My Prescriptions')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.prescriptions_breadcrumb', [], app()->getLocale()) ?: 'Prescriptions')

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.prescriptions_title', [], $l) ?: 'My Prescriptions' }}</h1>
            <p class="pf-sub">{{ __('public.portal.prescriptions_subtitle', [], $l) ?: 'All medications prescribed to you across your care history.' }}</p>
        </div>
    </div>

    @if(!$patient)
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
    @elseif($prescriptions->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'pill', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.no_prescriptions_title', [], $l) ?: 'No Prescriptions' }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.portal.no_prescriptions_desc', [], $l) ?: 'You have no recorded prescriptions at this time.' }}</p>
    </div>
    @else

    <div class="pf-stack">
        @foreach($prescriptions as $rx)
        <div class="f-card">
            <h2 class="pf-card-head" style="flex-wrap:wrap;">
                @include('partials.finder.icon', ['name' => 'pill', 'size' => 18])
                {{ __('public.portal.rx_heading_prefix', [], $l) ?: 'Prescription —' }} {{ $rx->prescribed_at?->format('d M Y') ?? (__('public.portal.rx_unknown_date', [], $l) ?: 'Unknown date') }}
                @php
                    $stCls = match($rx->statusColor()) {
                        'success' => 'f-badge--good', 'info' => '', default => 'pf-badge--muted'
                    };
                @endphp
                <span class="f-badge {{ $stCls }}" style="margin-left:auto;">@enum($rx->status)</span>
            </h2>
            @if($rx->facility)
            <p class="f-small f-muted" style="margin:-6px 0 10px;display:flex;align-items:center;gap:5px;">
                @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 14])
                {{ $rx->facility->name }}
            </p>
            @endif
            {{-- The code any OpesCare pharmacy looks this prescription up by —
                 the patient needs it when the paper is left at home. --}}
            @if(filled($rx->reference))
            <p class="f-small" style="margin:-4px 0 10px;display:flex;align-items:center;gap:5px;" title="{{ __('prescriber.rx_code_hint') }}">
                @include('partials.finder.icon', ['name' => 'qr-code', 'size' => 14])
                {{ __('prescriber.rx_code') }}: <strong data-rx-code>{{ $rx->reference }}</strong>
            </p>
            <p class="f-small f-muted" style="margin:-6px 0 10px;">{{ __('prescriber.rx_code_hint') }}</p>
            @endif

            @if($rx->items->isNotEmpty())
            <div style="display:grid;gap:8px;">
                @foreach($rx->items as $item)
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--f-line);">
                    <div style="min-width:0;">
                        <div style="font-weight:700;color:var(--f-ink);">{{ $item->drug_name }}</div>
                        <div class="f-small f-muted">
                            {{ $item->dose }} ({{ $item->route }}) &middot; {{ $item->frequency }}
                            @if($item->duration_days)
                                &middot; {{ $item->duration_days }} {{ __('public.portal.lbl_days', [], $l) ?: 'days' }}
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
                <button type="button" class="f-btn" onclick="pfOpenModal('refill-rx-{{ $rx->id }}')">
                    @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 16])
                    {{ __('public.portal.btn_request_refill', [], $l) ?: 'Request Refill' }}
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
                    {{ __('public.portal.modal_refill_title', [], $l) ?: 'Request refill' }}
                </h3>
                <form method="POST" action="{{ route('portals.patient.prescriptions.refill', $rx->id) }}">
                    @csrf
                    <p>{{ __('public.portal.modal_refill_body', [], $l) ?: 'Request a refill for this prescription?' }}</p>
                    <div class="pf-modal__actions">
                        <button type="button" class="f-btn" onclick="pfCloseModal('refill-rx-{{ $rx->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                        <button type="submit" class="f-btn f-btn--primary">{{ __('public.portal.btn_request_refill', [], $l) ?: 'Request Refill' }}</button>
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
<script>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
