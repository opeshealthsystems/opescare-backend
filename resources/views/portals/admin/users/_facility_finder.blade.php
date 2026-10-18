{{--
    Facility finder — the search half of the bounded facility picker.

    It has to be a sibling of the create/edit form rather than a control inside
    it, because HTML forms do not nest: narrowing the list is a GET on the same
    screen, while assigning the facility is the POST/PUT. Any filters already
    applied to the page are carried through as hidden fields so searching for a
    facility does not silently reset the user list behind it.

    Expects: $searchAction (route), $facilityQuery (string), $carry (array).
--}}
@if($facilityPickerOpen)
<form method="GET" action="{{ $searchAction }}" class="oda-toolbar">
    @foreach(($carry ?? []) as $carryKey => $carryValue)
        @if($carryValue !== null && $carryValue !== '')
        <input type="hidden" name="{{ $carryKey }}" value="{{ $carryValue }}">
        @endif
    @endforeach
    <label class="od-field">
        <i data-lucide="search"></i>
        <input type="text" name="facility_q" value="{{ $facilityQuery }}"
               placeholder="{{ __('admin_extra.users_facility_find_ph') }}"
               aria-label="{{ __('admin_extra.users_facility_find') }}">
    </label>
    <button type="submit" class="od-btn od-btn--secondary">
        <i data-lucide="search"></i> {{ __('admin_extra.users_facility_find_btn') }}
    </button>
</form>
@endif
