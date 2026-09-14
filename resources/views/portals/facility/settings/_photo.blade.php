{{--
    Facility photo / logo. Shared by the pharmacy and the generic facility
    settings pages. The picture is private and served by a signed link
    (FacilityPhotoService::urlFor), so $photoUrl is null when there is none.

    Expects: $ns, $action (POST url), $listing (?CareFacility), $photoUrl (?string).
--}}
@php
    $linked = $listing !== null;
@endphp

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="image"></i> {{ __($ns . '.section_photo') }}</h3>
    </div>
    <div class="panel-body">
        @unless($linked)<p class="lst-note">{{ __($ns . '.not_linked_forms') }}</p>@endunless

        <div class="ps-photo">
            <div class="ps-photo__frame" aria-hidden="{{ $photoUrl ? 'false' : 'true' }}">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ __($ns . '.photo_alt') }}">
                @else
                    <i data-lucide="image-off"></i>
                    <span>{{ __($ns . '.photo_none') }}</span>
                @endif
            </div>

            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="ps-photo__form">
                @csrf
                <fieldset @class(['ps-locked' => ! $linked]) @disabled(! $linked)>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="photo">{{ __($ns . '.label_photo') }}</label>
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-hint">{{ __($ns . '.hint_photo') }}</div>
                        @error('photo')<div class="form-hint form-error">{{ $message }}</div>@enderror
                    </div>
                    <div style="margin-top:var(--p-space-4)">
                        <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="upload"></i> {{ __($ns . '.btn_save_photo') }}</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
