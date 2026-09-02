{{--
    Primary facility control.

    The options are supplied already bounded and already authorised by
    AdminUserManagementController: a platform-tier admin sees a capped,
    search-narrowed slice of the register, a facility-tier admin sees the single
    facility they administer. The view never queries facilities itself, so there
    is no path by which this control can grow into a dump of the whole register.

    Expects: $facilityOptions, $facilityPickerOpen, $facilityPickerCap,
             $selected (current facility id or null).
--}}
<div class="form-group mt-6">
    <label class="form-label" for="primary_facility_id">{{ __('admin_extra.users_lbl_facility') }}</label>
    <select name="primary_facility_id" id="primary_facility_id" class="form-control">
        <option value="">{{ __('admin_extra.users_ph_facility') }}</option>
        @foreach($facilityOptions as $facilityOption)
        <option value="{{ $facilityOption->id }}" @selected(old('primary_facility_id', $selected ?? null) == $facilityOption->id)>{{ $facilityOption->name }}</option>
        @endforeach
    </select>
    @error('primary_facility_id')<div class="form-hint">{{ $message }}</div>@enderror
    <div class="form-hint">
        {{ __('admin_extra.users_facility_help') }}
        @if(! $facilityPickerOpen)
            {{ __('admin_extra.users_facility_locked') }}
        @elseif($facilityOptions->isEmpty())
            {{ __('admin_extra.users_facility_no_match') }}
        @elseif($facilityOptions->count() >= $facilityPickerCap)
            {{ __('admin_extra.users_facility_hint', ['n' => $facilityPickerCap]) }}
        @endif
    </div>
</div>
