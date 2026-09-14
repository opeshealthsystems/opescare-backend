@extends('layouts.portal')

@section('title', __('public.pat_referral_title', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_referral_breadcrumb', [], app()->getLocale()))

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
            <h1>{{ __('public.pat_referral_title', [], $l) }}</h1>
            <p class="pf-sub">{{ __('public.pat_referral_subtitle', [], $l) }}</p>
        </div>
    </div>

    @if($referrals->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'arrow-right-left', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.pat_referral_empty_title', [], $l) }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.pat_referral_empty_desc', [], $l) }}</p>
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
                    <p class="f-muted" style="margin:2px 0 0;">
                        {{ __('public.pat_referral_col_from', [], $l) }}:
                        {{ $r->referringFacility?->name ?? __('public.pat_referral_unknown', [], $l) }}
                    </p>
                    <p style="margin:8px 0 0;">
                        <span class="f-muted">{{ __('public.pat_referral_col_reason', [], $l) }}:</span>
                        {{ $r->reason }}
                    </p>
                </div>
                <div class="pf-appt__side">
                    <span class="f-badge {{ $badgeCls }}">@enum($r->status)</span>
                    <span class="f-small f-muted" style="display:block;margin-top:6px;">
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
