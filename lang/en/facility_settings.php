<?php

/*
|--------------------------------------------------------------------------
| "My facility" settings — identity, location, opening hours, contacts
|--------------------------------------------------------------------------
| The shared page for every facility type except pharmacies (which use
| pharmacy_settings.php). Must stay 1:1 with lang/fr/facility_settings.php
| (scripts/i18n-audit.php).
*/

return [

    'title'           => "My facility",
    'subtitle'        => "What patients see about this facility in the care directory: its name, where it is on the map, when it is open and how to reach it.",
    'nav'             => "My facility",
    'breadcrumb_home' => "Dashboard",

    // Page title by public facility type; falls back to `title`.
    'title_by_type' => [
        'hospital'          => "My hospital",
        'clinic'            => "My clinic",
        'health_center'     => "My health centre",
        'laboratory'        => "My laboratory",
        'dental'            => "My dental clinic",
        'imaging_center'    => "My imaging centre",
        'eye_clinic'        => "My eye clinic",
        'specialist'        => "My practice",
        'diagnostic_center' => "My diagnostic centre",
        'maternity'         => "My maternity",
        'dispensary'        => "My dispensary",
        'blood_bank'        => "My blood bank",
        'pharmacy'          => "My pharmacy",
    ],

    // ── Listing state ─────────────────────────────────────────────────────
    'badge_linked'         => "Public listing linked",
    'badge_claimed'        => "Claimed",
    'badge_verified'       => "Verified",
    'badge_self_reported'  => "Self-reported",
    'badge_unverified'     => "Not yet verified",
    'badge_not_listed'     => "Not shown in search",
    'badge_no_coordinates' => "No map position",

    'tenant_label' => "Account",
    'tenant_type'  => "Type",

    // ── Not linked ────────────────────────────────────────────────────────
    'not_linked_title' => "Your public listing is not linked yet",
    'not_linked_body'  => "This account (:name) is not connected to an entry in the public directory, so patients searching for care cannot see this facility and nothing on this page can be published yet. Linking is done by an OpesCare administrator, never automatically: two different facilities must never be joined by mistake.",
    'not_linked_what'  => "What to do: find your facility in the directory and claim it. Once the claim is reviewed and the listing linked, this page becomes editable.",
    'not_linked_cta'   => "Find and claim my listing",
    'not_linked_forms' => "Identity and location cannot be edited until the listing is linked.",

    // ── Warnings from the directory ───────────────────────────────────────
    'warn_no_coordinates' => "Patients cannot find you until the map position is set. The directory only places facilities with a position on the map and in distance-sorted results.",
    'warn_not_listed'     => "This listing is not active (status: :status), so it does not appear in search. Contact OpesCare support if you believe this is wrong.",
    'review_note'         => "Changes to the name, licence, address and map position alter what the public directory says. They are published at once and recorded for review; a verified listing becomes self-reported until it is checked again.",

    // ── Identity ──────────────────────────────────────────────────────────
    'section_identity'  => "Identity",
    'label_name'        => "Facility name",
    'hint_name'         => "As shown on the public card and on every page of this portal.",
    'label_license'     => "Licence number",
    'hint_license'      => "The MINSANTE authorisation number, if you have it. Leave blank otherwise.",
    'label_description' => "Description",
    'hint_description'  => "A few lines patients will read: departments, specialities, emergency service, parking.",
    'btn_save_identity' => "Save identity",

    // ── Contacts ──────────────────────────────────────────────────────────
    'section_contacts'      => "Contacts",
    'label_phone_primary'   => "Main phone",
    'label_phone_secondary' => "Second phone",
    'label_email'           => "Email",
    'label_website'         => "Website",
    'hint_phone'            => "The number patients call. The registry placeholder is never shown to them.",

    // ── Location ──────────────────────────────────────────────────────────
    'section_location'  => "Location",
    'location_intro'    => "Click the map or drag the pin to where the main entrance is. The coordinates fill in by themselves; you can also type them.",
    'label_address'     => "Street address",
    'label_city'        => "City",
    'label_region'      => "Region",
    'label_latitude'    => "Latitude",
    'label_longitude'   => "Longitude",
    'hint_coordinates'  => "Both or neither. Latitude −90 to 90, longitude −180 to 180.",
    'btn_save_location' => "Save location",
    'map_locate_hint'   => "Standing at the facility? Use the locate button on the map.",

    // ── Hours ─────────────────────────────────────────────────────────────
    'section_hours'  => "Opening hours",
    'hours_intro'    => "A day with no time, not marked open all day and not marked closed, is left unstated.",
    'btn_save_hours' => "Save hours",
    // ── Public services ───────────────────────────────────────────────────
    'services_intro' => "The services patients see on this facility's public card in the Finder — for example child immunisation, antenatal care, malaria rapid test or family planning. The priced list of coded services is kept separately under Our services.",
    // ── Photo / logo ──────────────────────────────────────────────────────
    'section_photo'      => "Photo or logo",
    'label_photo'        => "Picture",
    'hint_photo'         => "JPEG, PNG or WebP, at most 2 MB, at least 200 × 200 pixels. It replaces the current picture.",
    'photo_none'         => "No picture yet",
    'photo_alt'          => "Facility picture",
    'btn_save_photo'     => "Upload picture",
    'photo_not_an_image' => "The file could not be read as an image.",
    'flash_photo_saved'  => "Picture updated.",


    // ── Flash ─────────────────────────────────────────────────────────────
    'flash_saved'       => ":count field(s) updated.",
    'flash_no_changes'  => "Nothing changed.",
    'flash_hours_saved' => "Opening hours saved for :count day(s).",
];
