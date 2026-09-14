@extends('layouts.portal')
@section('title', __('pharmacy_settings.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_settings.title'))

@php
    use App\Models\CareFacility;
    use App\Modules\Pharmacy\Services\PharmacyStockReportService as Report;

    $linked        = $listing !== null;
    $noCoordinates = in_array(Report::ISSUE_NO_COORDINATES, $issues, true);
    $notListed     = in_array(Report::ISSUE_NOT_LISTED, $issues, true);
    $phonePrimary  = $linked ? CareFacility::realValue($listing->phone_primary) : null;
    $verification  = $listing?->verification_status ?? 'unverified';
    $verificationKey = match ($verification) {
        'license_verified', 'government_verified' => 'badge_verified',
        'self_reported'                           => 'badge_self_reported',
        default                                   => 'badge_unverified',
    };
@endphp

@section('head')
@include('portals.pharmacy._supply_styles')
<style>
  .lst-badges{display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:var(--p-space-3)}
  .lst-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700}
  .lst-badge i,.lst-badge svg{width:12px;height:12px}
  .lst-badge--claimed{background:rgba(15,76,129,.1);color:#0F4C81;border:1px solid rgba(15,76,129,.25)}
  .lst-badge--good{background:var(--f-good-wash,#E7F4EE);color:var(--f-good,#15803D);border:1px solid var(--f-good,#15803D)}
  .lst-badge--warn{background:var(--f-warn-wash,#FBF0E5);color:var(--f-warn,#B45309);border:1px solid var(--f-warn,#B45309)}
  .lst-badge--unverified{background:var(--f-wash,#F1F5F9);color:var(--f-muted,#64748B);border:1px solid var(--f-line,#E2E8F0)}
  .lst-note{font-size:.8rem;color:var(--p-text-muted);line-height:1.55;margin-bottom:var(--p-space-4)}
  .lst-hours-table td{padding:.4rem .5rem;vertical-align:middle}
  .lst-hours-table .form-control{padding:.35rem .5rem;font-size:.8rem}
  .lst-check{display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--p-text-2);white-space:nowrap}
  .ps-tenant{display:flex;gap:1.25rem;flex-wrap:wrap;font-size:.82rem;color:var(--p-text-2);margin-bottom:var(--p-space-4)}
  .ps-tenant strong{color:var(--p-text)}
  .ps-map{margin:var(--p-space-4) 0}
  .ps-coords{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--p-space-4)}
  fieldset.ps-locked{border:0;padding:0;margin:0;opacity:.55}
  .form-error{color:var(--p-danger)}
</style>
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_settings.title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_settings.subtitle') }}</p>
</div>

<div class="ps-tenant">
    <span>{{ __('pharmacy_settings.tenant_label') }}: <strong>{{ $facility->name }}</strong></span>
    <span>{{ __('pharmacy_settings.tenant_type') }}: <strong>{{ $facility->type }}</strong></span>
</div>

<div class="lst-badges">
    @if($linked)
        <span class="lst-badge lst-badge--good"><i data-lucide="link"></i> {{ __('pharmacy_settings.badge_linked') }}</span>
        @if($listing->isClaimed())
            <span class="lst-badge lst-badge--claimed"><i data-lucide="shield-check"></i> {{ __('pharmacy_settings.badge_claimed') }}</span>
        @endif
        <span class="lst-badge {{ $verificationKey === 'badge_verified' ? 'lst-badge--good' : 'lst-badge--unverified' }}">
            <i data-lucide="{{ $verificationKey === 'badge_verified' ? 'badge-check' : 'circle-dashed' }}"></i> {{ __('pharmacy_settings.' . $verificationKey) }}
        </span>
        @if($notListed)
            <span class="lst-badge lst-badge--warn"><i data-lucide="eye-off"></i> {{ __('pharmacy_settings.badge_not_listed') }}</span>
        @endif
        @if($noCoordinates)
            <span class="lst-badge lst-badge--warn"><i data-lucide="map-pin-off"></i> {{ __('pharmacy_settings.badge_no_coordinates') }}</span>
        @endif
    @else
        <span class="lst-badge lst-badge--warn"><i data-lucide="unlink"></i> {{ __('pharmacy_settings.not_linked_title') }}</span>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if(! $linked)
<div class="banner banner--danger">
    <i data-lucide="unlink"></i>
    <div>
        <strong>{{ __('pharmacy_settings.not_linked_title') }}</strong><br>
        {{ __('pharmacy_settings.not_linked_body', ['name' => $facility->name]) }}<br>
        {{ __('pharmacy_settings.not_linked_what') }}
        <div style="margin-top:var(--p-space-3)">
            <a href="{{ route('portals.listing.claims') }}" class="btn btn-primary btn-sm"><i data-lucide="map"></i> {{ __('pharmacy_settings.not_linked_cta') }}</a>
        </div>
    </div>
</div>
@else
    @if($noCoordinates)
    <div class="banner banner--danger">
        <i data-lucide="map-pin-off"></i>
        <div><strong>{{ __('pharmacy_settings.badge_no_coordinates') }}</strong><br>{{ __('pharmacy_settings.warn_no_coordinates') }}</div>
    </div>
    @endif
    @if($notListed)
    <div class="banner banner--danger">
        <i data-lucide="eye-off"></i>
        <div>{{ __('pharmacy_settings.warn_not_listed', ['status' => $listing->listing_status]) }}</div>
    </div>
    @endif
    <div class="fs-note"><i data-lucide="info"></i><div>{{ __('pharmacy_settings.review_note') }}</div></div>
@endif

{{-- ══ Identity + contacts ═══════════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="store"></i> {{ __('pharmacy_settings.section_identity') }}</h3>
    </div>
    <div class="panel-body">
        @unless($linked)<p class="lst-note">{{ __('pharmacy_settings.not_linked_forms') }}</p>@endunless

        <form method="POST" action="{{ route('portals.pharmacy.settings.identity') }}">
            @csrf
            <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="facility_name">{{ __('pharmacy_settings.label_name') }}</label>
                        <input type="text" id="facility_name" name="facility_name" class="form-control" maxlength="150" required
                               value="{{ old('facility_name', $listing?->facility_name ?? $facility->name) }}">
                        <div class="form-hint">{{ __('pharmacy_settings.hint_name') }}</div>
                        @error('facility_name')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="license_number">{{ __('pharmacy_settings.label_license') }}</label>
                        <input type="text" id="license_number" name="license_number" class="form-control" maxlength="80"
                               value="{{ old('license_number', $listing?->license_number ?? $facility->license_number) }}">
                        <div class="form-hint">{{ __('pharmacy_settings.hint_license') }}</div>
                        @error('license_number')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group" style="margin-top:var(--p-space-4)">
                    <label class="form-label" for="description">{{ __('pharmacy_settings.label_description') }}</label>
                    <textarea id="description" name="description" class="form-control" maxlength="2000">{{ old('description', $listing?->description) }}</textarea>
                    <div class="form-hint">{{ __('pharmacy_settings.hint_description') }}</div>
                    @error('description')<div class="form-hint form-error">{{ $message }}</div>@enderror
                </div>

                <h4 class="panel-title" style="margin-top:var(--p-space-5)"><i data-lucide="phone"></i> {{ __('pharmacy_settings.section_contacts') }}</h4>

                <div class="form-row" style="margin-top:var(--p-space-3)">
                    <div class="form-group">
                        <label class="form-label" for="phone_primary">{{ __('pharmacy_settings.label_phone_primary') }}</label>
                        <input type="tel" id="phone_primary" name="phone_primary" class="form-control" maxlength="40" inputmode="tel"
                               value="{{ old('phone_primary', $phonePrimary) }}">
                        <div class="form-hint">{{ __('pharmacy_settings.hint_phone') }}</div>
                        @error('phone_primary')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone_secondary">{{ __('pharmacy_settings.label_phone_secondary') }}</label>
                        <input type="tel" id="phone_secondary" name="phone_secondary" class="form-control" maxlength="40" inputmode="tel"
                               value="{{ old('phone_secondary', $linked ? CareFacility::realValue($listing->phone_secondary) : null) }}">
                        @error('phone_secondary')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row" style="margin-top:var(--p-space-4)">
                    <div class="form-group">
                        <label class="form-label" for="email">{{ __('pharmacy_settings.label_email') }}</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150"
                               value="{{ old('email', $listing?->email) }}">
                        @error('email')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="website">{{ __('pharmacy_settings.label_website') }}</label>
                        <input type="url" id="website" name="website" class="form-control" maxlength="255" placeholder="https://"
                               value="{{ old('website', $listing?->website) }}">
                        @error('website')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __('pharmacy_settings.btn_save_identity') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

{{-- ══ Location ══════════════════════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="map-pin"></i> {{ __('pharmacy_settings.section_location') }}</h3>
    </div>
    <div class="panel-body">
        @unless($linked)<p class="lst-note">{{ __('pharmacy_settings.not_linked_forms') }}</p>@endunless
        <p class="lst-note">{{ __('pharmacy_settings.location_intro') }} {{ __('pharmacy_settings.map_locate_hint') }}</p>

        <form method="POST" action="{{ route('portals.pharmacy.settings.location') }}">
            @csrf
            <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                <div class="form-group">
                    <label class="form-label form-label-required" for="address">{{ __('pharmacy_settings.label_address') }}</label>
                    <input type="text" id="address" name="address" class="form-control" maxlength="255" required
                           value="{{ old('address', $listing?->address) }}">
                    @error('address')<div class="form-hint form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row" style="margin-top:var(--p-space-4)">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="city">{{ __('pharmacy_settings.label_city') }}</label>
                        <input type="text" id="city" name="city" class="form-control" maxlength="120" required
                               value="{{ old('city', $listing?->city) }}">
                        @error('city')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="region">{{ __('pharmacy_settings.label_region') }}</label>
                        <input type="text" id="region" name="region" class="form-control" maxlength="120"
                               value="{{ old('region', $listing?->region) }}">
                        @error('region')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($linked)
                <div class="ps-map">
                    @include('partials.finder.map', [
                        'mapId'    => 'pharmacy-settings-map',
                        'height'   => '360px',
                        'pick'     => ['lat' => 'latitude', 'lng' => 'longitude'],
                        'pickType' => 'pharmacy',
                    ])
                </div>
                @endif

                <div class="ps-coords">
                    <div class="form-group">
                        <label class="form-label" for="latitude">{{ __('pharmacy_settings.label_latitude') }}</label>
                        <input type="text" id="latitude" name="latitude" class="form-control fs-mono" inputmode="decimal"
                               value="{{ old('latitude', $listing?->latitude) }}">
                        @error('latitude')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="longitude">{{ __('pharmacy_settings.label_longitude') }}</label>
                        <input type="text" id="longitude" name="longitude" class="form-control fs-mono" inputmode="decimal"
                               value="{{ old('longitude', $listing?->longitude) }}">
                        @error('longitude')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-hint">{{ __('pharmacy_settings.hint_coordinates') }}</div>

                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __('pharmacy_settings.btn_save_location') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

{{-- ══ Opening hours ═════════════════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ __('pharmacy_settings.section_hours') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __('pharmacy_settings.hours_intro') }}</p>

        <form method="POST" action="{{ route('portals.pharmacy.settings.hours') }}">
            @csrf
            <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                <div class="table-wrapper">
                    <table class="data-table lst-hours-table">
                        <thead>
                            <tr>
                                <th>{{ __('caremap_claim.hours_day') }}</th>
                                <th>{{ __('caremap_claim.hours_open') }}</th>
                                <th>{{ __('caremap_claim.hours_close') }}</th>
                                <th>{{ __('caremap_claim.hours_24') }}</th>
                                <th>{{ __('caremap_claim.hours_closed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($dayLabels as $day => $label)
                            @php $h = $hours->get($day); @endphp
                            <tr>
                                <td data-label="{{ __('caremap_claim.hours_day') }}" class="td-strong">{{ $label }}</td>
                                <td data-label="{{ __('caremap_claim.hours_open') }}">
                                    <input type="time" name="hours[{{ $day }}][opens_at]" class="form-control"
                                           value="{{ $h && $h->opens_at ? substr($h->opens_at, 0, 5) : '' }}">
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_close') }}">
                                    <input type="time" name="hours[{{ $day }}][closes_at]" class="form-control"
                                           value="{{ $h && $h->closes_at ? substr($h->closes_at, 0, 5) : '' }}">
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_24') }}">
                                    <label class="lst-check"><input type="checkbox" name="hours[{{ $day }}][is_24_hours]" value="1" @checked($h?->is_24_hours)></label>
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_closed') }}">
                                    <label class="lst-check"><input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" @checked($h?->is_closed)></label>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __('pharmacy_settings.btn_save_hours') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

@endsection
