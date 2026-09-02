@extends('layouts.portal')
@section('title', __('caremap_claim.edit_title'))
@section('breadcrumb_section', __('caremap_claim.edit_title'))

@section('content')

<div class="page-head">
    <h2>{{ __('caremap_claim.edit_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="map-pin"></i></div>
            <p>{{ __('caremap_claim.none_title') }}</p>
            <p class="td-muted">{{ __('caremap_claim.none_body') }}</p>
            <div style="margin-top:1rem;display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
                <a href="{{ route('public.care-map') }}" class="btn btn-primary btn-sm">
                    <i data-lucide="map"></i> {{ __('caremap_claim.btn_find_listing') }}
                </a>
                @if($claims->isNotEmpty())
                    <a href="{{ route('portals.listing.claims') }}" class="btn btn-secondary btn-sm">
                        <i data-lucide="list-checks"></i> {{ __('caremap_claim.my_claims_title') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
