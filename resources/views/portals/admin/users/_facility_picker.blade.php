{{--
    Primary facility control.

    The options are supplied already bounded and already authorised by
    AdminUserManagementController: a platform-tier admin sees a capped,
    search-narrowed slice of the register, a facility-tier admin sees the single
    facility they administer. The view never queries facilities itself, so there
    is no path by which this control can grow into a dump of the whole register.

    Rendered as a full-width cell of an .oda-form grid.
    Expects: $facilityOptions, $facilityPickerOpen, $facilityPickerCap,
             $selected (current facility id or null).
--}}
<div class="oda-label oda-form__full">
    <label class="oda-label__text" for="primary_facility_id">{{ __('admin_extra.users_lbl_facility') }}</label>
    <select name="primary_facility_id" id="primary_facility_id" class="od-select">
        <option value="">{{ __('admin_extra.users_ph_facility') }}</option>
        @foreach($facilityOptions as $facilityOption)
        <option value="{{ $facilityOption->id }}" @selected(old('primary_facility_id', $selected ?? null) == $facilityOption->id)>{{ $facilityOption->name }}</option>
        @endforeach
    </select>
    @error('primary_facility_id')<span class="oda-error">{{ $message }}</span>@enderror
    <p class="oda-help">
        {{ __('admin_extra.users_facility_help') }}
        @if(! $facilityPickerOpen)
            {{ __('admin_extra.users_facility_locked') }}
        @elseif($facilityOptions->isEmpty())
            {{ __('admin_extra.users_facility_no_match') }}
        @elseif($facilityOptions->count() >= $facilityPickerCap)
            {{ __('admin_extra.users_facility_hint', ['n' => $facilityPickerCap]) }}
        @endif
    </p>
</div>
