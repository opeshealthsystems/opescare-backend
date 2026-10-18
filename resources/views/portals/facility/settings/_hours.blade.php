{{--
    Opening-hours grid (0 = Sunday, matching care_facility_hours). Shared by
    the pharmacy and the generic facility settings pages.

    Expects: $ns, $action (POST url), $listing (?CareFacility),
             $hours (Collection keyed by day_of_week), $dayLabels (0..6 => label).
--}}
@php $linked = $listing !== null; @endphp

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __($ns . '.section_hours') }}</h2>
        <span class="od-avatar odc-ico-avatar od-tint--amber" aria-hidden="true"><i data-lucide="clock"></i></span>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __($ns . '.hours_intro') }}</p>

        <form method="POST" action="{{ $action }}">
            @csrf
            <fieldset @class(['odc-fieldset', 'odc-locked' => ! $linked]) @disabled(! $linked)>
                <div class="od-table-wrap">
                    <table class="od-table odc-hours-table">
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
                                <td data-label="{{ __('caremap_claim.hours_day') }}"><span class="od-table__name">{{ $label }}</span></td>
                                <td data-label="{{ __('caremap_claim.hours_open') }}">
                                    <input type="time" name="hours[{{ $day }}][opens_at]" class="od-input" aria-label="{{ $label }} — {{ __('caremap_claim.hours_open') }}"
                                           value="{{ $h && $h->opens_at ? substr($h->opens_at, 0, 5) : '' }}">
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_close') }}">
                                    <input type="time" name="hours[{{ $day }}][closes_at]" class="od-input" aria-label="{{ $label }} — {{ __('caremap_claim.hours_close') }}"
                                           value="{{ $h && $h->closes_at ? substr($h->closes_at, 0, 5) : '' }}">
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_24') }}">
                                    <label class="odc-check"><input type="checkbox" name="hours[{{ $day }}][is_24_hours]" value="1" @checked($h?->is_24_hours) aria-label="{{ $label }} — {{ __('caremap_claim.hours_24') }}"></label>
                                </td>
                                <td data-label="{{ __('caremap_claim.hours_closed') }}">
                                    <label class="odc-check"><input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" @checked($h?->is_closed) aria-label="{{ $label }} — {{ __('caremap_claim.hours_closed') }}"></label>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="odc-form-foot">
                    <button type="submit" class="od-btn od-btn--primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_hours') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</section>
