@extends('layouts.finder')
@section('title', __('caremap_claim.page_title'))
@section('description', __('caremap_claim.claim_intro'))

@push('head')
    @include('public.partials._od_pages_css')
@endpush

{{-- Onboarding visual system (design 2026-10-21) — loads after the page CSS. --}}
@push('head')
<link rel="stylesheet" href="{{ asset('css/onboarding.css') }}?v={{ @filemtime(public_path('css/onboarding.css')) ?: '1' }}">
@endpush

@section('content')
<div class="ob-wrap ob-wrap--narrow">

  <a href="{{ route('public.care-map.profile', $facility->id) }}" class="ob-back">
    @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16]) {{ __('caremap_claim.back_to_listing') }}
  </a>

  <div class="ob-panel">
    <div class="ob-head">
      <div class="ob-eyebrow">{{ __('onboarding_flow.claim.eyebrow') }}</div>
      <h1 class="ob-h1">{{ __('caremap_claim.claim_heading') }}</h1>
      <p class="ob-sub">{{ __('caremap_claim.claim_subheading', ['facility' => $facility->facility_name]) }}</p>
    </div>

    {{-- What happens after submit — official process, three steps, no surprises. --}}
    <ol class="ob-stepline" aria-label="{{ __('finder_supply.claim_how_title') }}">
      <li><span class="ob-dot">1</span> {{ __('finder_supply.claim_step_submit') }}</li>
      <li><span class="ob-dot">2</span> {{ __('finder_supply.claim_step_review') }}</li>
      <li><span class="ob-dot">3</span> {{ __('finder_supply.claim_step_edit') }}</li>
    </ol>

    <div class="ob-facility">
      <span class="ob-ic">@include('partials.finder.icon', ['name' => 'building-2', 'size' => 20])</span>
      <div>
        <b>{{ $facility->facility_name }}</b>
        <span>
          {{ $facility->city }}@if($facility->region), {{ $facility->region }}@endif
          @if($facility->dialablePhone()) &bull; {{ $facility->dialablePhone() }} @endif
        </span>
      </div>
    </div>

    @if(session('error'))
      <div class="ob-alert" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])<div>{{ session('error') }}</div>
      </div>
    @endif

    @if($existing && $existing->claim_status->isOpen())
      <div class="ob-note">
        @include('partials.finder.icon', ['name' => 'clock', 'size' => 18])<div>{{ __('caremap_claim.already_pending') }}</div>
      </div>
      <div class="ob-actions">
        <a href="{{ route('portals.listing.claims') }}" class="ob-btn ob-btn--primary">
          @include('partials.finder.icon', ['name' => 'check', 'size' => 18]) {{ __('caremap_claim.my_claims_title') }}
        </a>
      </div>
    @elseif($existing && $existing->claim_status->grantsEditAccess())
      <div class="ob-note">
        @include('partials.finder.icon', ['name' => 'check', 'size' => 18])<div>{{ __('caremap_claim.already_approved') }}</div>
      </div>
      <div class="ob-actions">
        <a href="{{ route('portals.listing.edit') }}" class="ob-btn ob-btn--primary">
          @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 18]) {{ __('caremap_claim.btn_manage_listing') }}
        </a>
      </div>
    @else

      <p class="ob-sub">{{ __('caremap_claim.claim_intro') }}</p>

      <div class="ob-note">
        @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18])
        <div>{{ __('caremap_claim.claim_review_notice') }}</div>
      </div>

      <div class="ob-note">
        @include('partials.finder.icon', ['name' => 'smartphone', 'size' => 18])
        <div>{{ __('caremap_claim.proof_notice') }}</div>
      </div>

      <form method="POST" action="{{ route('public.care-map.claim.store', $facility->id) }}" enctype="multipart/form-data">
        @csrf

        <fieldset class="ob-fs">
          <legend class="ob-legend">{{ __('onboarding_flow.claim.you') }}</legend>
          <p class="ob-legend-sub">{{ __('onboarding_flow.claim.you_help') }}</p>
          <div class="ob-grid">
            <div>
              <label class="ob-label" for="claimant_name">{{ __('caremap_claim.field_name') }} <em>*</em></label>
              <input type="text" id="claimant_name" name="claimant_name" class="ob-input" maxlength="150" autocomplete="name"
                     value="{{ old('claimant_name', auth()->user()->name ?? '') }}" required>
              @error('claimant_name')<div class="ob-error">{{ $message }}</div>@enderror
            </div>

            <div>
              <label class="ob-label" for="claimant_role">{{ __('caremap_claim.field_role') }} <em>*</em></label>
              <select id="claimant_role" name="claimant_role" class="ob-input" required>
                <option value="">{{ __('caremap_claim.role_select') }}</option>
                <option value="owner" @selected(old('claimant_role') === 'owner')>{{ __('caremap_claim.role_owner') }}</option>
                <option value="manager" @selected(old('claimant_role') === 'manager')>{{ __('caremap_claim.role_manager') }}</option>
                <option value="authorized_rep" @selected(old('claimant_role') === 'authorized_rep')>{{ __('caremap_claim.role_authorized_rep') }}</option>
                <option value="admin_staff" @selected(old('claimant_role') === 'admin_staff')>{{ __('caremap_claim.role_admin_staff') }}</option>
              </select>
              @error('claimant_role')<div class="ob-error">{{ $message }}</div>@enderror
            </div>

            <div>
              <label class="ob-label" for="claimant_email">{{ __('caremap_claim.field_email') }} <em>*</em></label>
              <input type="email" id="claimant_email" name="claimant_email" class="ob-input" maxlength="150" autocomplete="email"
                     value="{{ old('claimant_email', auth()->user()->email ?? '') }}" required>
              @error('claimant_email')<div class="ob-error">{{ $message }}</div>@enderror
            </div>

            <div>
              <label class="ob-label" for="claimant_phone">{{ __('caremap_claim.field_phone') }} <em>*</em></label>
              <input type="tel" id="claimant_phone" name="claimant_phone" class="ob-input" maxlength="40" inputmode="tel" autocomplete="tel"
                     value="{{ old('claimant_phone') }}" required>
              @error('claimant_phone')<div class="ob-error">{{ $message }}</div>@enderror
            </div>

            <div class="ob-full">
              <label class="ob-label" for="claim_reason">
                {{ __('caremap_claim.field_reason') }}
                <span class="ob-legend-sub">({{ __('caremap_claim.field_optional') }})</span>
              </label>
              <textarea id="claim_reason" name="claim_reason" class="ob-input" maxlength="2000" aria-describedby="claim_reason_help">{{ old('claim_reason') }}</textarea>
              <div class="ob-help" id="claim_reason_help">{{ __('caremap_claim.field_reason_hint') }}</div>
              @error('claim_reason')<div class="ob-error">{{ $message }}</div>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="ob-fs">
          <legend class="ob-legend">{{ __('onboarding_flow.claim.proof') }}</legend>
          <p class="ob-legend-sub">{{ __('onboarding_flow.claim.proof_help') }}</p>
          <label class="ob-label" for="document">{{ __('caremap_claim.field_document') }} <em>*</em></label>
          <div class="ob-file">
            <input type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required aria-describedby="document_help">
          </div>
          <div class="ob-help" id="document_help">{{ __('caremap_claim.field_document_hint') }}</div>
          @error('document')<div class="ob-error">{{ $message }}</div>@enderror
        </fieldset>

        <div class="ob-note">
          @include('partials.finder.icon', ['name' => 'smartphone', 'size' => 18])
          <div>{{ __('onboarding_flow.claim.after') }}</div>
        </div>

        <p class="ob-help">{{ __('caremap_claim.hint_false_claim') }}</p>

        <div class="ob-actions">
          <button type="submit" class="ob-btn ob-btn--primary">
            @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18]) {{ __('caremap_claim.btn_submit_claim') }}
          </button>
          <a href="{{ route('public.care-map.profile', $facility->id) }}" class="ob-btn">
            {{ __('caremap_claim.btn_cancel') }}
          </a>
        </div>
      </form>

    @endif
  </div>
</div>
@endsection
