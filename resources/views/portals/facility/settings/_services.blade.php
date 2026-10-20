{{--
    Public services — the care_facility_services rows the Finder profile lists
    under "Services". READ-ONLY since 2026-10-20 (owner's decision): the rows
    are derived from the service catalogue by PublicServicesSync, and the
    catalogue is the single editor.

    Expects: $listing (?CareFacility, services loaded).
--}}
@php
    use App\Http\Controllers\CareMap\FacilityListingController as ListingCtl;

    $svcLinked   = $listing !== null;
    $svcRows     = $svcLinked ? $listing->services->sortBy('service_name') : collect();
    $svcCats     = ListingCtl::serviceCategories();
    $svcStatuses = ListingCtl::availabilityStatuses();
    $svcTone     = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

<section class="od-card" id="public-services">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('caremap_claim.section_services') }} <span class="od-card__count">({{ $svcRows->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('facility_settings.services_from_catalogue') }}</p>
        <p><a href="{{ route('portals.facility.catalogue') }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="list-checks"></i> {{ __('facility_settings.services_open_catalogue') }}</a></p>

        @if($svcRows->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="list-plus"></i></span>
                <p class="od-empty__text">{{ __('caremap_claim.empty_services') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th class="od-table__num">#</th>
                            <th>{{ __('caremap_claim.svc_name') }}</th>
                            <th>{{ __('caremap_claim.svc_category') }}</th>
                            <th>{{ __('caremap_claim.svc_availability') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($svcRows as $svc)
                        <tr>
                            <td class="od-table__num">{{ $loop->iteration }}</td>
                            <td data-label="{{ __('caremap_claim.svc_name') }}">
                                <div class="od-table__person">
                                    <span class="od-avatar odc-ico-avatar od-tint--{{ $svcTone((string) $svc->service_name) }}" aria-hidden="true"><i data-lucide="stethoscope"></i></span>
                                    <div>
                                        <span class="od-table__name">{{ $svc->service_name }}</span>
                                        @if($svc->specialty)<span class="od-table__sub">{{ $svc->specialty }}</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('caremap_claim.svc_category') }}">{{ $svcCats[$svc->service_category] ?? $svc->service_category }}</td>
                            <td data-label="{{ __('caremap_claim.svc_availability') }}"><span class="od-pill od-pill--{{ $svc->availability_status === 'available' ? 'ready' : 'neutral' }}">{{ $svcStatuses[$svc->availability_status] ?? $svc->availability_status }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</section>
