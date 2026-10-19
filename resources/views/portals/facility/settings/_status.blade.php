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

<section class="od-card odc-status">
    <div class="od-card__body odc-status__body">
        <div class="od-detail__facts">
            <span class="od-detail__fact"><i data-lucide="building-2"></i> {{ __($ns . '.tenant_label') }}: <span class="odc-strong">{{ $facility->name }}</span></span>
            <span class="od-detail__fact"><i data-lucide="tag"></i> {{ __($ns . '.tenant_type') }}: <span class="odc-strong">{{ $typeLabel }}</span></span>
        </div>
        <div class="odc-row-actions">
            @if($linked)
                <span class="od-pill od-pill--ready">{{ __($ns . '.badge_linked') }}</span>
                @if($listing->isClaimed())
                    <span class="od-pill od-pill--normal">{{ __($ns . '.badge_claimed') }}</span>
                @endif
                <span class="od-pill {{ $verificationKey === 'badge_verified' ? 'od-pill--ready' : 'od-pill--neutral' }}">{{ __($ns . '.' . $verificationKey) }}</span>
                @if($notListed)
                    <span class="od-pill od-pill--pending">{{ __($ns . '.badge_not_listed') }}</span>
                @endif
                @if($noCoordinates)
                    <span class="od-pill od-pill--pending">{{ __($ns . '.badge_no_coordinates') }}</span>
                @endif
            @else
                <span class="od-pill od-pill--urgent">{{ __($ns . '.not_linked_title') }}</span>
            @endif
        </div>
    </div>
</section>

@if(session('success'))<div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>@endif

@if(! $linked)
<div class="odc-note odc-note--error">
    <i data-lucide="unlink"></i>
    <div class="odc-note__text">
        <span class="odc-strong">{{ __($ns . '.not_linked_title') }}</span><br>
        {{ __($ns . '.not_linked_body', ['name' => $facility->name]) }}<br>
        {{ __($ns . '.not_linked_what') }}
        <div class="odc-links">
            <a href="{{ route('portals.listing.claims') }}" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="map"></i> {{ __($ns . '.not_linked_cta') }}</a>
        </div>
    </div>
</div>
@else
    @if($noCoordinates)
    <div class="odc-note odc-note--error">
        <i data-lucide="map-pin-off"></i>
        <div class="odc-note__text"><span class="odc-strong">{{ __($ns . '.badge_no_coordinates') }}</span><br>{{ __($ns . '.warn_no_coordinates') }}</div>
    </div>
    @endif
    @if($notListed)
    <div class="odc-note odc-note--warn">
        <i data-lucide="eye-off"></i>
        <div class="odc-note__text">{{ __($ns . '.warn_not_listed', ['status' => \App\Support\Enums::label($listing->listing_status)]) }}</div>
    </div>
    @endif
    <div class="odc-note odc-note--info"><i data-lucide="info"></i><div class="odc-note__text">{{ __($ns . '.review_note') }}</div></div>
@endif
