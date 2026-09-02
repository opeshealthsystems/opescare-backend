@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_referral_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_referral_breadcrumb', [], $l))

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_referral_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.pat_referral_subtitle', [], $l) }}</p>
    </div>
</div>

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($referrals->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="share-2"></i></div>
        <h3>{{ __('public.pat_referral_empty_title', [], $l) }}</h3>
        <p>{{ __('public.pat_referral_empty_desc', [], $l) }}</p>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="share-2"></i> {{ __('public.pat_referral_title', [], $l) }}</h2>
        <span class="badge badge-primary">{{ $referrals->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.pat_referral_col_from', [], $l) }}</th>
                    <th>{{ __('public.pat_referral_col_to', [], $l) }}</th>
                    <th>{{ __('public.pat_referral_col_reason', [], $l) }}</th>
                    <th>{{ __('public.pat_referral_col_urgency', [], $l) }}</th>
                    <th>{{ __('public.pat_referral_col_status', [], $l) }}</th>
                    <th>{{ __('public.pat_referral_col_date', [], $l) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($referrals as $referral)
                @php
                    $statusBadge = match($referral->status) {
                        'accepted', 'completed' => 'badge-success',
                        'rejected', 'cancelled' => 'badge-danger',
                        'pending'               => 'badge-warning',
                        default                 => 'badge-primary',
                    };
                    $urgencyBadge = match($referral->urgency) {
                        'emergency', 'urgent' => 'badge-danger',
                        'priority'            => 'badge-warning',
                        default               => 'badge-teal',
                    };
                @endphp
                <tr>
                    <td data-label="{{ __('public.pat_referral_col_from', [], $l) }}">
                        <span class="td-strong">{{ $referral->referringFacility?->name ?? __('public.pat_referral_unknown', [], $l) }}</span>
                    </td>
                    <td data-label="{{ __('public.pat_referral_col_to', [], $l) }}">
                        <span class="td-strong">{{ $referral->receivingFacility?->name ?? $referral->receiving_specialty ?? __('public.pat_referral_unknown', [], $l) }}</span>
                    </td>
                    <td data-label="{{ __('public.pat_referral_col_reason', [], $l) }}">
                        <span class="td-muted">{{ $referral->reason ?: '—' }}</span>
                    </td>
                    <td data-label="{{ __('public.pat_referral_col_urgency', [], $l) }}">
                        <span class="badge {{ $urgencyBadge }}">@enum($referral->urgency ?? 'routine', 'urgency')</span>
                    </td>
                    <td data-label="{{ __('public.pat_referral_col_status', [], $l) }}">
                        <span class="badge {{ $statusBadge }}">@enum($referral->status ?? 'pending')</span>
                    </td>
                    <td data-label="{{ __('public.pat_referral_col_date', [], $l) }}">
                        <span class="td-muted">{{ $referral->created_at?->isoFormat('LL') ?? '—' }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
