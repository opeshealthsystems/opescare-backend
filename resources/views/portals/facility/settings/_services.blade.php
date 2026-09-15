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
@endphp

<div class="panel mb-6" id="public-services">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> {{ __('caremap_claim.section_services') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __('facility_settings.services_intro') }}</p>

        @if($svcRows->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="list-plus"></i></div>
                <p>{{ __('caremap_claim.empty_services') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.svc_name') }}</th>
                            <th>{{ __('caremap_claim.svc_category') }}</th>
                            <th>{{ __('caremap_claim.svc_availability') }}</th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($svcRows as $svc)
                        <tr>
                            <td data-label="{{ __('caremap_claim.svc_name') }}">
                                <span class="td-strong">{{ $svc->service_name }}</span>
                                @if($svc->specialty)<div class="td-muted">{{ $svc->specialty }}</div>@endif
                            </td>
                            <td data-label="{{ __('caremap_claim.svc_category') }}" class="td-muted">{{ $svcCats[$svc->service_category] ?? $svc->service_category }}</td>
                            <td data-label="{{ __('caremap_claim.svc_availability') }}" class="td-muted">{{ $svcStatuses[$svc->availability_status] ?? $svc->availability_status }}</td>
                            <td class="row-actions">
                                <form method="POST" action="{{ route('portals.facility.settings.services.destroy', $svc->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="trash-2"></i> {{ __('caremap_claim.btn_remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.facility.settings.services.store') }}" style="margin-top:var(--p-space-5)">
            @csrf
            <fieldset @class(['ps-locked' => ! $svcLinked]) @disabled(! $svcLinked)>
                <div class="lst-svc-grid">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="svc_service_name">{{ __('caremap_claim.svc_name') }}</label>
                        <input type="text" id="svc_service_name" name="service_name" class="form-control" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="svc_service_category">{{ __('caremap_claim.svc_category') }}</label>
                        <select id="svc_service_category" name="service_category" class="form-control" required>
                            @foreach($svcCats as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="svc_specialty">{{ __('caremap_claim.svc_specialty') }}</label>
                        <input type="text" id="svc_specialty" name="specialty" class="form-control" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="svc_availability_status">{{ __('caremap_claim.svc_availability') }}</label>
                        <select id="svc_availability_status" name="availability_status" class="form-control" required>
                            @foreach($svcStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="lst-svc-flags">
                    <label class="lst-check"><input type="checkbox" name="appointment_required" value="1"> {{ __('caremap_claim.svc_appointment') }}</label>
                    <label class="lst-check"><input type="checkbox" name="walk_in_allowed" value="1" checked> {{ __('caremap_claim.svc_walkin') }}</label>
                    <label class="lst-check"><input type="checkbox" name="telemedicine_available" value="1"> {{ __('caremap_claim.svc_telemedicine') }}</label>
                </div>
                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('caremap_claim.btn_add_service') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
