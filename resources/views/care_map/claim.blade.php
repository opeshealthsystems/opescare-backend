@extends('layouts.finder')
@section('title', __('caremap_claim.page_title'))
@section('description', __('caremap_claim.claim_intro'))

@push('head')
<style>
  /* Claim flow — official-but-simple, on foundation tokens only. */
  .clm-wrap{max-width:720px;margin:0 auto;padding:2.5rem 1rem 4rem}
  .clm-back{display:inline-flex;align-items:center;gap:.35rem;min-height:44px;font-size:.875rem;color:var(--f-muted);text-decoration:none;margin-bottom:.75rem}
  .clm-back:hover{color:var(--f-brand)}
  .clm-head h1{font-size:clamp(1.45rem,3vw + .9rem,1.75rem);font-weight:800;letter-spacing:-.015em;color:var(--f-ink);margin-bottom:.35rem}
  .clm-head p{font-size:.9375rem;color:var(--f-ink-soft);line-height:1.6;margin:0}
  .clm-steps{display:grid;grid-template-columns:1fr;gap:.5rem;margin-top:1.25rem}
  @media(min-width:640px){.clm-steps{grid-template-columns:repeat(3,1fr)}}
  .clm-step{display:flex;align-items:center;gap:.6rem;padding:.7rem .85rem;background:var(--f-surface);border:1px solid var(--f-line);border-radius:var(--f-r);font-size:.8125rem;font-weight:600;color:var(--f-ink-soft)}
  .clm-step__n{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:999px;background:var(--f-wash);color:var(--f-brand);font-size:.75rem;font-weight:750;flex-shrink:0}
  .clm-card{background:var(--f-surface);border:1px solid var(--f-line);border-radius:var(--f-r);padding:1.5rem;margin-top:1.25rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
  .clm-facility{display:flex;align-items:flex-start;gap:.9rem;padding-bottom:1.1rem;margin-bottom:1.25rem;border-bottom:1px solid var(--f-line)}
  .clm-facility-icon{width:44px;height:44px;border-radius:.7rem;background:var(--f-wash);color:var(--f-brand);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .clm-facility-name{font-size:1rem;font-weight:700;color:var(--f-ink)}
  .clm-facility-meta{font-size:.8125rem;color:var(--f-muted);margin-top:.15rem}
  .clm-notice{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;background:var(--f-warn-wash);border:1px solid var(--f-warn);color:var(--f-warn);font-size:.8125rem;line-height:1.55;margin-bottom:1.25rem}
  .clm-notice .f-icon{flex-shrink:0;margin-top:.15rem}
  .clm-alert{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;font-size:.875rem;margin-bottom:1.25rem;line-height:1.55}
  .clm-alert .f-icon{flex-shrink:0;margin-top:.15rem}
  .clm-alert--error{background:var(--f-danger-wash);border:1px solid var(--f-danger);color:var(--f-danger)}
  .clm-alert--info{background:var(--f-wash);border:1px solid var(--f-brand);color:var(--f-brand-ink)}
  .clm-actions{display:flex;gap:.6rem;align-items:center;margin-top:1.5rem;flex-wrap:wrap}
  .clm-row{display:grid;grid-template-columns:1fr;gap:0 1rem}
  @media(min-width:640px){.clm-row{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="clm-wrap">

  <a href="{{ route('public.care-map.profile', $facility->id) }}" class="clm-back">
    @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16]) {{ __('caremap_claim.back_to_listing') }}
  </a>

  <div class="clm-head">
    <h1>{{ __('caremap_claim.claim_heading') }}</h1>
    <p>{{ __('caremap_claim.claim_subheading', ['facility' => $facility->facility_name]) }}</p>
  </div>

  {{-- What happens after submit — official process, three steps, no surprises. --}}
  <div class="clm-steps" aria-label="{{ __('finder_supply.claim_how_title') }}">
    <div class="clm-step"><span class="clm-step__n">1</span> {{ __('finder_supply.claim_step_submit') }}</div>
    <div class="clm-step"><span class="clm-step__n">2</span> {{ __('finder_supply.claim_step_review') }}</div>
    <div class="clm-step"><span class="clm-step__n">3</span> {{ __('finder_supply.claim_step_edit') }}</div>
  </div>

  <div class="clm-card">

    <div class="clm-facility">
      <div class="clm-facility-icon">@include('partials.finder.icon', ['name' => 'building-2', 'size' => 20])</div>
      <div>
        <div class="clm-facility-name">{{ $facility->facility_name }}</div>
        <div class="clm-facility-meta">
          {{ $facility->city }}@if($facility->region), {{ $facility->region }}@endif
          @if($facility->dialablePhone()) &bull; {{ $facility->dialablePhone() }} @endif
        </div>
      </div>
    </div>

    @if(session('error'))
      <div class="clm-alert clm-alert--error">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16])<div>{{ session('error') }}</div>
      </div>
    @endif

    @if($existing && $existing->claim_status->isOpen())
      <div class="clm-alert clm-alert--info">
        @include('partials.finder.icon', ['name' => 'clock', 'size' => 16])<div>{{ __('caremap_claim.already_pending') }}</div>
      </div>
      <div class="clm-actions">
        <a href="{{ route('portals.listing.claims') }}" class="f-btn f-btn--primary">
          @include('partials.finder.icon', ['name' => 'check', 'size' => 16]) {{ __('caremap_claim.my_claims_title') }}
        </a>
      </div>
    @elseif($existing && $existing->claim_status->grantsEditAccess())
      <div class="clm-alert clm-alert--info">
        @include('partials.finder.icon', ['name' => 'check', 'size' => 16])<div>{{ __('caremap_claim.already_approved') }}</div>
      </div>
      <div class="clm-actions">
        <a href="{{ route('portals.listing.edit') }}" class="f-btn f-btn--primary">
          @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16]) {{ __('caremap_claim.btn_manage_listing') }}
        </a>
      </div>
    @else

      <p class="f-hint" style="margin-bottom:1.1rem">{{ __('caremap_claim.claim_intro') }}</p>

      <div class="clm-notice">
        @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
        <div>{{ __('caremap_claim.claim_review_notice') }}</div>
      </div>

      <form method="POST" action="{{ route('public.care-map.claim.store', $facility->id) }}">
        @csrf

        <div class="clm-row">
          <div class="f-field">
            <label class="f-label" for="claimant_name">{{ __('caremap_claim.field_name') }}</label>
            <input type="text" id="claimant_name" name="claimant_name" class="f-input" maxlength="150"
                   value="{{ old('claimant_name', auth()->user()->name ?? '') }}" required>
            @error('claimant_name')<div class="f-error">{{ $message }}</div>@enderror
          </div>

          <div class="f-field">
            <label class="f-label" for="claimant_role">{{ __('caremap_claim.field_role') }}</label>
            <select id="claimant_role" name="claimant_role" class="f-input" required>
              <option value="">{{ __('caremap_claim.role_select') }}</option>
              <option value="owner" @selected(old('claimant_role') === 'owner')>{{ __('caremap_claim.role_owner') }}</option>
              <option value="manager" @selected(old('claimant_role') === 'manager')>{{ __('caremap_claim.role_manager') }}</option>
              <option value="authorized_rep" @selected(old('claimant_role') === 'authorized_rep')>{{ __('caremap_claim.role_authorized_rep') }}</option>
              <option value="admin_staff" @selected(old('claimant_role') === 'admin_staff')>{{ __('caremap_claim.role_admin_staff') }}</option>
            </select>
            @error('claimant_role')<div class="f-error">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="clm-row">
          <div class="f-field">
            <label class="f-label" for="claimant_email">{{ __('caremap_claim.field_email') }}</label>
            <input type="email" id="claimant_email" name="claimant_email" class="f-input" maxlength="150"
                   value="{{ old('claimant_email', auth()->user()->email ?? '') }}" required>
            @error('claimant_email')<div class="f-error">{{ $message }}</div>@enderror
          </div>

          <div class="f-field">
            <label class="f-label" for="claimant_phone">{{ __('caremap_claim.field_phone') }}</label>
            <input type="tel" id="claimant_phone" name="claimant_phone" class="f-input" maxlength="40" inputmode="tel"
                   value="{{ old('claimant_phone') }}" required>
            @error('claimant_phone')<div class="f-error">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="f-field">
          <label class="f-label" for="claim_reason">
            {{ __('caremap_claim.field_reason') }}
            <span class="f-muted" style="font-weight:400">({{ __('caremap_claim.field_optional') }})</span>
          </label>
          <textarea id="claim_reason" name="claim_reason" class="f-input" maxlength="2000">{{ old('claim_reason') }}</textarea>
          <div class="f-hint">{{ __('caremap_claim.field_reason_hint') }}</div>
          @error('claim_reason')<div class="f-error">{{ $message }}</div>@enderror
        </div>

        <div class="f-hint">{{ __('caremap_claim.hint_false_claim') }}</div>

        <div class="clm-actions">
          <button type="submit" class="f-btn f-btn--primary">
            @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16]) {{ __('caremap_claim.btn_submit_claim') }}
          </button>
          <a href="{{ route('public.care-map.profile', $facility->id) }}" class="f-btn">
            {{ __('caremap_claim.btn_cancel') }}
          </a>
        </div>
      </form>

    @endif

  </div>
</div>
@endsection
