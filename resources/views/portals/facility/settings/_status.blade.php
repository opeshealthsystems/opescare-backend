{{--
    Listing state — tenant line, badges, flash messages, the unlinked / no
    position / not listed banners, and the review note. Shared by the
    pharmacy and the generic facility settings pages.

    Expects: $ns (lang namespace: pharmacy_settings | facility_settings),
             $facility, $listing (?CareFacility), $issues (list<string>),
             $typeLabel (string shown next to "Type").
--}}
@php
    use App\Services\CareMap\FacilitySettingsService as Settings;

    $linked        = $listing !== null;
    $noCoordinates = in_array(Settings::ISSUE_NO_COORDINATES, $issues, true);
    $notListed     = in_array(Settings::ISSUE_NOT_LISTED, $issues, true);
    $verification  = $listing?->verification_status ?? 'unverified';
    $verificationKey = match ($verification) {
        'license_verified', 'government_verified' => 'badge_verified',
        'self_reported'                           => 'badge_self_reported',
        default                                   => 'badge_unverified',
    };
@endphp

<div class="ps-tenant">
    <span>{{ __($ns . '.tenant_label') }}: <strong>{{ $facility->name }}</strong></span>
    <span>{{ __($ns . '.tenant_type') }}: <strong>{{ $typeLabel }}</strong></span>
</div>

<div class="lst-badges">
    @if($linked)
        <span class="lst-badge lst-badge--good"><i data-lucide="link"></i> {{ __($ns . '.badge_linked') }}</span>
        @if($listing->isClaimed())
            <span class="lst-badge lst-badge--claimed"><i data-lucide="shield-check"></i> {{ __($ns . '.badge_claimed') }}</span>
        @endif
        <span class="lst-badge {{ $verificationKey === 'badge_verified' ? 'lst-badge--good' : 'lst-badge--unverified' }}">
            <i data-lucide="{{ $verificationKey === 'badge_verified' ? 'badge-check' : 'circle-dashed' }}"></i> {{ __($ns . '.' . $verificationKey) }}
        </span>
        @if($notListed)
            <span class="lst-badge lst-badge--warn"><i data-lucide="eye-off"></i> {{ __($ns . '.badge_not_listed') }}</span>
        @endif
        @if($noCoordinates)
            <span class="lst-badge lst-badge--warn"><i data-lucide="map-pin-off"></i> {{ __($ns . '.badge_no_coordinates') }}</span>
        @endif
    @else
        <span class="lst-badge lst-badge--warn"><i data-lucide="unlink"></i> {{ __($ns . '.not_linked_title') }}</span>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if(! $linked)
<div class="banner banner--danger">
    <i data-lucide="unlink"></i>
    <div>
        <strong>{{ __($ns . '.not_linked_title') }}</strong><br>
        {{ __($ns . '.not_linked_body', ['name' => $facility->name]) }}<br>
        {{ __($ns . '.not_linked_what') }}
        <div style="margin-top:var(--p-space-3)">
            <a href="{{ route('portals.listing.claims') }}" class="btn btn-primary btn-sm"><i data-lucide="map"></i> {{ __($ns . '.not_linked_cta') }}</a>
        </div>
    </div>
</div>
@else
    @if($noCoordinates)
    <div class="banner banner--danger">
        <i data-lucide="map-pin-off"></i>
        <div><strong>{{ __($ns . '.badge_no_coordinates') }}</strong><br>{{ __($ns . '.warn_no_coordinates') }}</div>
    </div>
    @endif
    @if($notListed)
    <div class="banner banner--danger">
        <i data-lucide="eye-off"></i>
        <div>{{ __($ns . '.warn_not_listed', ['status' => $listing->listing_status]) }}</div>
    </div>
    @endif
    <div class="fs-note"><i data-lucide="info"></i><div>{{ __($ns . '.review_note') }}</div></div>
@endif
