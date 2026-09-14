{{--
    Opening-hours grid (0 = Sunday, matching care_facility_hours). Shared by
    the pharmacy and the generic facility settings pages.

    Expects: $ns, $action (POST url), $listing (?CareFacility),
             $hours (Collection keyed by day_of_week), $dayLabels (0..6 => label).
--}}
@php $linked = $listing !== null; @endphp

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ __($ns . '.section_hours') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __($ns . '.hours_intro') }}</p>

        <form method="POST" action="{{ $action }}">
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
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_hours') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
