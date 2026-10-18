{{--
    Facility photo / logo. Shared by the pharmacy and the generic facility
    settings pages. The picture is private and served by a signed link
    (FacilityPhotoService::urlFor), so $photoUrl is null when there is none.

    Expects: $ns, $action (POST url), $listing (?CareFacility), $photoUrl (?string).
--}}
@php
    $linked = $listing !== null;
@endphp

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __($ns . '.section_photo') }}</h2>
    </header>
    <div class="od-card__body">
        @unless($linked)<p class="odc-muted">{{ __($ns . '.not_linked_forms') }}</p>@endunless

        <div class="odc-photo-row">
            <div class="odc-photo-frame" aria-hidden="{{ $photoUrl ? 'false' : 'true' }}">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ __($ns . '.photo_alt') }}">
                @else
                    <i data-lucide="image-off"></i>
                    <span>{{ __($ns . '.photo_none') }}</span>
                @endif
            </div>

            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="odc-photo-form">
                @csrf
                <fieldset @class(['odc-fieldset', 'odc-locked' => ! $linked]) @disabled(! $linked)>
                    <label class="odc-label" for="photo">{{ __($ns . '.label_photo') }}</label>
                    <input type="file" id="photo" name="photo" class="od-input odc-file" accept="image/jpeg,image/png,image/webp" required>
                    <p class="odc-hint">{{ __($ns . '.hint_photo') }}</p>
                    @error('photo')<p class="odc-error">{{ $message }}</p>@enderror
                    <div class="odc-form-foot">
                        <button type="submit" class="od-btn od-btn--primary fs-tap"><i data-lucide="upload"></i> {{ __($ns . '.btn_save_photo') }}</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</section>
