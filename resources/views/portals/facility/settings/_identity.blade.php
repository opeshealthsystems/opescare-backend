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

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="{{ $sectionIcon }}"></i> {{ __($ns . '.section_identity') }}</h3>
    </div>
    <div class="panel-body">
        @unless($linked)<p class="lst-note">{{ __($ns . '.not_linked_forms') }}</p>@endunless

        <form method="POST" action="{{ $action }}">
            @csrf
            <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="facility_name">{{ __($ns . '.label_name') }}</label>
                        <input type="text" id="facility_name" name="facility_name" class="form-control" maxlength="150" required
                               value="{{ old('facility_name', $listing?->facility_name ?? $facility->name) }}">
                        <div class="form-hint">{{ __($ns . '.hint_name') }}</div>
                        @error('facility_name')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="license_number">{{ __($ns . '.label_license') }}</label>
                        <input type="text" id="license_number" name="license_number" class="form-control" maxlength="80"
                               value="{{ old('license_number', $listing?->license_number ?? $facility->license_number) }}">
                        <div class="form-hint">{{ __($ns . '.hint_license') }}</div>
                        @error('license_number')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group" style="margin-top:var(--p-space-4)">
                    <label class="form-label" for="description">{{ __($ns . '.label_description') }}</label>
                    <textarea id="description" name="description" class="form-control" maxlength="2000">{{ old('description', $listing?->description) }}</textarea>
                    <div class="form-hint">{{ __($ns . '.hint_description') }}</div>
                    @error('description')<div class="form-hint form-error">{{ $message }}</div>@enderror
                </div>

                <h4 class="panel-title" style="margin-top:var(--p-space-5)"><i data-lucide="phone"></i> {{ __($ns . '.section_contacts') }}</h4>

                <div class="form-row" style="margin-top:var(--p-space-3)">
                    <div class="form-group">
                        <label class="form-label" for="phone_primary">{{ __($ns . '.label_phone_primary') }}</label>
                        <input type="tel" id="phone_primary" name="phone_primary" class="form-control" maxlength="40" inputmode="tel"
                               value="{{ old('phone_primary', $linked ? CareFacility::realValue($listing->phone_primary) : null) }}">
                        <div class="form-hint">{{ __($ns . '.hint_phone') }}</div>
                        @error('phone_primary')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone_secondary">{{ __($ns . '.label_phone_secondary') }}</label>
                        <input type="tel" id="phone_secondary" name="phone_secondary" class="form-control" maxlength="40" inputmode="tel"
                               value="{{ old('phone_secondary', $linked ? CareFacility::realValue($listing->phone_secondary) : null) }}">
                        @error('phone_secondary')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row" style="margin-top:var(--p-space-4)">
                    <div class="form-group">
                        <label class="form-label" for="email">{{ __($ns . '.label_email') }}</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="150"
                               value="{{ old('email', $listing?->email) }}">
                        @error('email')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="website">{{ __($ns . '.label_website') }}</label>
                        <input type="url" id="website" name="website" class="form-control" maxlength="255" placeholder="https://"
                               value="{{ old('website', $listing?->website) }}">
                        @error('website')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="margin-top:var(--p-space-5)">
                    <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="save"></i> {{ __($ns . '.btn_save_identity') }}</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
