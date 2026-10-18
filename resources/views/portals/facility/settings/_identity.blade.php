{{--
    Identity + contacts form. Shared by the pharmacy and the generic facility
    settings pages.

    Expects: $ns, $action (POST url), $facility, $listing (?CareFacility),
             $sectionIcon (Lucide name for the panel title).
--}}
@php
    use App\Models\CareFacility;

    $linked = $listing !== null;
@endphp

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __($ns . '.section_identity') }}</h2>
        <span class="od-avatar odc-ico-avatar od-tint--blue" aria-hidden="true"><i data-lucide="{{ $sectionIcon }}"></i></span>
    </header>
    <div class="od-card__body">
        @unless($linked)<p class="odc-muted">{{ __($ns . '.not_linked_forms') }}</p>@endunless

        <form method="POST" action="{{ $action }}">
            @csrf
            <fieldset @class(['odc-fieldset', 'odc-locked' => ! $linked]) @disabled(! $linked)>
                <div class="odc-form odc-form--2">
                    <div>
                        <label class="odc-label" for="facility_name">{{ __($ns . '.label_name') }}</label>
                        <input type="text" id="facility_name" name="facility_name" class="od-input" maxlength="150" required
                               value="{{ old('facility_name', $listing?->facility_name ?? $facility->name) }}">
                        <p class="odc-hint">{{ __($ns . '.hint_name') }}</p>
                        @error('facility_name')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="license_number">{{ __($ns . '.label_license') }}</label>
                        <input type="text" id="license_number" name="license_number" class="od-input" maxlength="80"
                               value="{{ old('license_number', $listing?->license_number ?? $facility->license_number) }}">
                        <p class="odc-hint">{{ __($ns . '.hint_license') }}</p>
                        @error('license_number')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="odc-span">
                        <label class="odc-label" for="description">{{ __($ns . '.label_description') }}</label>
                        <textarea id="description" name="description" class="od-input odc-textarea" maxlength="2000">{{ old('description', $listing?->description) }}</textarea>
                        <p class="odc-hint">{{ __($ns . '.hint_description') }}</p>
                        @error('description')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>

                    <h3 class="odc-subtitle odc-span">{{ __($ns . '.section_contacts') }}</h3>

                    <div>
                        <label class="odc-label" for="phone_primary">{{ __($ns . '.label_phone_primary') }}</label>
                        <input type="tel" id="phone_primary" name="phone_primary" class="od-input" maxlength="40" inputmode="tel"
                               value="{{ old('phone_primary', $linked ? CareFacility::realValue($listing->phone_primary) : null) }}">
                        <p class="odc-hint">{{ __($ns . '.hint_phone') }}</p>
                        @error('phone_primary')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="phone_secondary">{{ __($ns . '.label_phone_secondary') }}</label>
                        <input type="tel" id="phone_secondary" name="phone_secondary" class="od-input" maxlength="40" inputmode="tel"
                               value="{{ old('phone_secondary', $linked ? CareFacility::realValue($listing->phone_secondary) : null) }}">
                        @error('phone_secondary')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="email">{{ __($ns . '.label_email') }}</label>
                        <input type="email" id="email" name="email" class="od-input" maxlength="150"
                               value="{{ old('email', $listing?->email) }}">
                        @error('email')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="odc-label" for="website">{{ __($ns . '.label_website') }}</label>
                        <input type="url" id="website" name="website" class="od-input" maxlength="255" placeholder="https://"
                               value="{{ old('website', $listing?->website) }}">
                        @error('website')<p class="odc-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="odc-form-foot">
                    <button type="submit" class="od-btn od-btn--primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_identity') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</section>
