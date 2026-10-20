@extends('layouts.portal')
@section('title', __('caremap_claim.my_claims_title'))
@section('breadcrumb_section', __('caremap_claim.my_claims_title'))

@section('content')

<div class="page-head">
    <h2>{{ __('caremap_claim.my_claims_title') }}</h2>
    <div class="page-head__spacer"></div>
    @if($listing)
        <a href="{{ route('portals.listing.edit') }}" class="btn btn-primary btn-sm">
            <i data-lucide="pencil"></i> {{ __('caremap_claim.btn_manage_listing') }}
        </a>
    @else
        <a href="{{ route('public.care-map') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="map"></i> {{ __('caremap_claim.btn_find_listing') }}
        </a>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($claims->isEmpty())
    <div class="panel">
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="map-pin"></i></div>
                <p>{{ __('caremap_claim.empty_claims') }}</p>
                <p class="td-muted">{{ __('caremap_claim.empty_claims_hint') }}</p>
            </div>
        </div>
    </div>
@else
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="list-checks"></i> {{ __('caremap_claim.my_claims_subtitle') }}</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('caremap_claim.col_facility') }}</th>
                        <th>{{ __('caremap_claim.col_status') }}</th>
                        <th>{{ __('caremap_claim.col_submitted') }}</th>
                        <th>{{ __('caremap_claim.col_reviewed') }}</th>
                        <th>{{ __('caremap_claim.col_notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($claims as $claim)
                    @php
                        $badge = match($claim->claim_status) {
                            \App\Enums\FacilityClaimStatus::Approved => 'badge-success',
                            \App\Enums\FacilityClaimStatus::Rejected => 'badge-danger',
                            \App\Enums\FacilityClaimStatus::Revoked  => 'badge-neutral',
                            default                                  => 'badge-warning',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('caremap_claim.col_facility') }}">
                            <span class="td-strong">{{ $claim->careFacility?->facility_name ?? '—' }}</span>
                            @if($claim->careFacility)
                                <div class="td-muted">{{ $claim->careFacility->city }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('caremap_claim.col_status') }}">
                            <span class="badge {{ $badge }}">{{ __('caremap_claim.status_' . $claim->claim_status->value) }}</span>
                        </td>
                        <td data-label="{{ __('caremap_claim.col_submitted') }}" class="td-muted">
                            {{ optional($claim->submitted_at ?? $claim->created_at)->format('Y-m-d') ?? '—' }}
                        </td>
                        <td data-label="{{ __('caremap_claim.col_reviewed') }}" class="td-muted">
                            {{ optional($claim->reviewed_at)->format('Y-m-d') ?? '—' }}
                        </td>
                        <td data-label="{{ __('caremap_claim.col_notes') }}" class="td-muted">
                            {{ $claim->review_notes ?: '—' }}
                        </td>
                    </tr>
                    @if($claim->claim_status === \App\Enums\FacilityClaimStatus::PendingVerification)
                    <tr>
                        <td colspan="5">
                            <p class="td-muted">{{ __('caremap_claim.code_prompt', ['phone' => $claim->sms_phone_masked ?? '***']) }}</p>
                            <form method="POST" action="{{ route('portals.listing.claims.verify', $claim->id) }}" class="d-inline">
                                @csrf
                                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="12" required
                                       class="form-control d-inline" aria-label="{{ __('caremap_claim.code_label') }}" placeholder="{{ __('caremap_claim.code_label') }}">
                                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('caremap_claim.btn_verify_code') }}</button>
                            </form>
                            <form method="POST" action="{{ route('portals.listing.claims.resend', $claim->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="refresh-cw"></i> {{ __('caremap_claim.btn_resend_code') }}</button>
                            </form>
                        </td>
                    </tr>
                    @elseif($claim->claim_status === \App\Enums\FacilityClaimStatus::PendingReview && ! $claim->isPhoneVerified())
                    <tr>
                        <td colspan="5" class="td-muted">{{ __('caremap_claim.code_undelivered_note') }}</td>
                    </tr>
                    @elseif(isset($activations[$claim->id]))
                    <tr>
                        <td colspan="5">
                            <p class="td-muted">{{ __('caremap_claim.activate_prompt') }}</p>
                            <a href="{{ $activations[$claim->id] }}" class="btn btn-primary btn-sm"><i data-lucide="key-round"></i> {{ __('caremap_claim.btn_activate') }}</a>
                        </td>
                    </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
