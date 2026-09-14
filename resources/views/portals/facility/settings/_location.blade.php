{{--
    Location form with the Leaflet pick-mode map. Shared by the pharmacy and
    the generic facility settings pages.

    Expects: $ns, $action (POST url), $listing (?CareFacility),
             $mapId (unique DOM id), $pickType (finder-map pin colour key).
--}}
@php $linked = $listing !== null; @endphp

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="map-pin"></i> {{ __($ns . '.section_location') }}</h3>
    </div>
    <div class="panel-body">
        @unless($linked)<p class="lst-note">{{ __($ns . '.not_linked_forms') }}</p>@endunless
        <p class="lst-note">{{ __($ns . '.location_intro') }} {{ __($ns . '.map_locate_hint') }}</p>

        <form method="POST" action="{{ $action }}">
            @csrf
            <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                <div class="form-group">
                    <label class="form-label form-label-required" for="address">{{ __($ns . '.label_address') }}</label>
                    <input type="text" id="address" name="address" class="form-control" maxlength="255" required
                           value="{{ old('address', $listing?->address) }}">
                    @error('address')<div class="form-hint form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row" style="margin-top:var(--p-space-4)">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="city">{{ __($ns . '.label_city') }}</label>
                        <input type="text" id="city" name="city" class="form-control" maxlength="120" required
                               value="{{ old('city', $listing?->city) }}">
                        @error('city')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="region">{{ __($ns . '.label_region') }}</label>
                        <input type="text" id="region" name="region" class="form-control" maxlength="120"
                               value="{{ old('region', $listing?->region) }}">
                        @error('region')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($linked)
                <div class="ps-map">
                    @include('partials.finder.map', [
                        'mapId'    => $mapId,
                        'height'   => '360px',
                        'pick'     => ['lat' => 'latitude', 'lng' => 'longitude'],
                        'pickType' => $pickType,
                    ])
                </div>
                @endif

                <div class="ps-coords">
                    <div class="form-group">
                        <label class="form-label" for="latitude">{{ __($ns . '.label_latitude') }}</label>
                        <input type="text" id="latitude" name="latitude" class="form-control fs-mono" inputmode="decimal"
                               value="{{ old('latitude', $listing?->latitude) }}">
                        @error('latitude')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="longitude">{{ __($ns . '.label_longitude') }}</label>
                        <input type="text" id="longitude" name="longitude" class="form-control fs-mono" inputmode="decimal"
                               value="{{ old('longitude', $listing?->longitude) }}">
                        @error('longitude')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-hint">{{ __($ns . '.hint_coordinates') }}</div>

                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_location') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
