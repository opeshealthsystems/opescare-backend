{{--
    Public services — the care_facility_services rows the Finder profile lists
    under "Services". Only the facility settings page renders this. The form is
    locked until the tenant is linked to a listing, like the other sections.

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
        <p class="odc-muted">{{ __('facility_settings.services_intro') }}</p>

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
                            <th aria-hidden="true"></th>
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
                            <td class="od-table__actions">
                                <form method="POST" action="{{ route('portals.facility.settings.services.destroy', $svc->id) }}" class="odc-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="trash-2"></i> {{ __('caremap_claim.btn_remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.facility.settings.services.store') }}" class="odc-links odc-block">
            @csrf
            <fieldset @class(['odc-fieldset', 'odc-locked' => ! $svcLinked]) @disabled(! $svcLinked)>
                <div class="odc-form">
                    <div>
                        <label class="odc-label" for="svc_service_name">{{ __('caremap_claim.svc_name') }}</label>
                        <input type="text" id="svc_service_name" name="service_name" class="od-input" maxlength="150" required>
                    </div>
                    <div>
                        <label class="odc-label" for="svc_service_category">{{ __('caremap_claim.svc_category') }}</label>
                        <select id="svc_service_category" name="service_category" class="od-select" required>
                            @foreach($svcCats as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="odc-label" for="svc_specialty">{{ __('caremap_claim.svc_specialty') }}</label>
                        <input type="text" id="svc_specialty" name="specialty" class="od-input" maxlength="120">
                    </div>
                    <div>
                        <label class="odc-label" for="svc_availability_status">{{ __('caremap_claim.svc_availability') }}</label>
                        <select id="svc_availability_status" name="availability_status" class="od-select" required>
                            @foreach($svcStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="odc-row-actions odc-links">
                    <label class="odc-check"><input type="checkbox" name="appointment_required" value="1"> {{ __('caremap_claim.svc_appointment') }}</label>
                    <label class="odc-check"><input type="checkbox" name="walk_in_allowed" value="1" checked> {{ __('caremap_claim.svc_walkin') }}</label>
                    <label class="odc-check"><input type="checkbox" name="telemedicine_available" value="1"> {{ __('caremap_claim.svc_telemedicine') }}</label>
                </div>
                <div class="odc-form-foot">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('caremap_claim.btn_add_service') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</section>
