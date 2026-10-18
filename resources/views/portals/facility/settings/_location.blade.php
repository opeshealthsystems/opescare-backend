{{--
    Location form with the Leaflet pick-mode map. Shared by the pharmacy and
    the generic facility settings pages.

    Expects: $ns, $action (POST url), $listing (?CareFacility),
             $mapId (unique DOM id), $pickType (finder-map pin colour key).
--}}
@php $linked = $listing !== null; @endphp

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __($ns . '.section_location') }}</h2>
        <span class="od-avatar odc-ico-avatar od-tint--teal" aria-hidden="true"><i data-lucide="map-pin"></i></span>
    </header>
    <div class="od-card__body">
        @unless($linked)<p class="odc-muted">{{ __($ns . '.not_linked_forms') }}</p>@endunless
        <p class="odc-muted">{{ __($ns . '.location_intro') }} {{ __($ns . '.map_locate_hint') }}</p>

        <form method="POST" action="{{ $action }}">
            @csrf
            <fieldset @class(['odc-fieldset', 'odc-locked' => ! $linked]) @disabled(! $linked)>
                <div class="odc-form odc-form--2">
                    <div class="odc-span">
                        <label class="odc-label" for="address">{{ __($ns . '.label_address') }}</label>
                        <input type="text" id="address" name="address" class="od-input" maxlength="255" required
                               value="{{ old('address', $listing?->address) }}">
                        @error('address')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="city">{{ __($ns . '.label_city') }}</label>
                        <input type="text" id="city" name="city" class="od-input" maxlength="120" required
                               value="{{ old('city', $listing?->city) }}">
                        @error('city')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="region">{{ __($ns . '.label_region') }}</label>
                        <input type="text" id="region" name="region" class="od-input" maxlength="120"
                               value="{{ old('region', $listing?->region) }}">
                        @error('region')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>

                    @if($linked)
                    <div class="odc-span odc-map">
                        @include('partials.finder.map', [
                            'mapId'    => $mapId,
                            'height'   => '360px',
                            'pick'     => ['lat' => 'latitude', 'lng' => 'longitude'],
                            'pickType' => $pickType,
                        ])
                    </div>
                    @endif

                    <div>
                        <label class="odc-label" for="latitude">{{ __($ns . '.label_latitude') }}</label>
                        <input type="text" id="latitude" name="latitude" class="od-input fs-mono" inputmode="decimal"
                               value="{{ old('latitude', $listing?->latitude) }}">
                        @error('latitude')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="longitude">{{ __($ns . '.label_longitude') }}</label>
                        <input type="text" id="longitude" name="longitude" class="od-input fs-mono" inputmode="decimal"
                               value="{{ old('longitude', $listing?->longitude) }}">
                        @error('longitude')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <p class="odc-hint odc-span">{{ __($ns . '.hint_coordinates') }}</p>
                </div>

                <div class="odc-form-foot">
                    <button type="submit" class="od-btn od-btn--primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_location') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</section>
