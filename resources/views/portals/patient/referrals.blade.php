@extends('layouts.portal')

@section('title', __('public.pat_referral_title', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_referral_breadcrumb', [], app()->getLocale()))

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
            <h1>{{ __('public.pat_referral_title', [], $l) }}</h1>
            <p class="pf-sub">{{ __('public.pat_referral_subtitle', [], $l) }}</p>
        </div>
    </div>

    @if($referrals->isEmpty())
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'arrow-right-left', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.pat_referral_empty_title', [], $l) }}</h2>
        <p class="f-muted ods-1386d50">{{ __('public.pat_referral_empty_desc', [], $l) }}</p>
    </div>
    @else

    <div class="pf-stack">
        @foreach($referrals as $r)
        @php
            $badgeCls = match($r->status) {
                'accepted', 'completed'          => 'f-badge--good',
                'sent'                           => 'f-badge--warn',
                'rejected', 'expired'            => 'f-badge--danger',
                'cancelled', 'draft'             => 'pf-badge--muted',
                default                          => '',
            };
            // receiving_facility_id is nullable: a referral may name a
            // specialty or a provider outside the register.
            $to = $r->receivingFacility?->name
                ?? $r->receiving_provider_name
                ?? $r->receiving_specialty
                ?? __('public.pat_referral_unknown', [], $l);
        @endphp
        <div class="f-card">
            <div class="pf-appt">
                <div class="pf-appt__date" aria-hidden="true">
                    <div class="d">{{ $r->created_at?->format('d') ?? '—' }}</div>
                    <div class="m">{{ $r->created_at?->isoFormat('MMM YYYY') ?? '' }}</div>
                </div>
                <div class="pf-appt__body">
                    <p class="pf-appt__title">
                        {{ __('public.pat_referral_col_to', [], $l) }}: {{ $to }}
                    </p>
                    <p class="f-muted ods-9bed7a6">
                        {{ __('public.pat_referral_col_from', [], $l) }}:
                        {{ $r->referringFacility?->name ?? __('public.pat_referral_unknown', [], $l) }}
                    </p>
                    <p class="ods-00dd4a9">
                        <span class="f-muted">{{ __('public.pat_referral_col_reason', [], $l) }}:</span>
                        {{ $r->reason }}
                    </p>
                </div>
                <div class="pf-appt__side">
                    <span class="f-badge {{ $badgeCls }}">@enum($r->status)</span>
                    <span class="f-small f-muted ods-c1bc483">
                        {{ __('public.pat_referral_col_urgency', [], $l) }}: @enum($r->urgency, 'urgency')
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
