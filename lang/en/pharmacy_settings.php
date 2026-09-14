<?php

/*
|--------------------------------------------------------------------------
| "My pharmacy" settings — identity, location, opening hours, contacts
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/pharmacy_settings.php (scripts/i18n-audit.php).
*/

return [

    'title'    => "My pharmacy",
    'subtitle' => "What patients see about this pharmacy in the Medicine Finder: its name, where it is on the map, when it is open and how to reach it.",
    'nav'      => "My pharmacy",

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
    'not_linked_body'  => "This account (:name) is not connected to an entry in the public directory, so patients searching the Medicine Finder cannot see this pharmacy and nothing on this page can be published yet. Linking is done by an OpesCare administrator, never automatically: two different pharmacies must never be joined by mistake.",
    'not_linked_what'  => "What to do: find your pharmacy in the directory and claim it. Once the claim is reviewed and the listing linked, this page becomes editable.",
    'not_linked_cta'   => "Find and claim my listing",
    'not_linked_forms' => "Identity and location cannot be edited until the listing is linked.",

    // ── Warnings from the finder ──────────────────────────────────────────
    'warn_no_coordinates' => "Patients cannot find you until the map position is set. The Medicine Finder only returns pharmacies with a position, whatever stock they report.",
    'warn_not_listed'     => "This listing is not active (status: :status), so it does not appear in search. Contact OpesCare support if you believe this is wrong.",
    'review_note'         => "Changes to the name, licence, address and map position alter what the public directory says. They are published at once and recorded for review; a verified listing becomes self-reported until it is checked again.",

    // ── Identity ──────────────────────────────────────────────────────────
    'section_identity'     => "Identity",
    'label_name'           => "Pharmacy name",
    'hint_name'            => "As shown on the public card and on every page of this portal.",
    'label_license'        => "Licence number",
    'hint_license'         => "The MINSANTE authorisation number, if you have it. Leave blank otherwise.",
    'label_description'    => "Description",
    'hint_description'     => "A few lines patients will read: specialities, parking, on-call service.",
    'btn_save_identity'    => "Save identity",

    // ── Contacts ──────────────────────────────────────────────────────────
    'section_contacts'     => "Contacts",
    'label_phone_primary'  => "Main phone",
    'label_phone_secondary'=> "Second phone",
    'label_email'          => "Email",
    'label_website'        => "Website",
    'hint_phone'           => "The number patients call. The registry placeholder is never shown to them.",

    // ── Location ──────────────────────────────────────────────────────────
    'section_location'  => "Location",
    'location_intro'    => "Click the map or drag the pin to where the pharmacy entrance is. The coordinates fill in by themselves; you can also type them.",
    'label_address'     => "Street address",
    'label_city'        => "City",
    'label_region'      => "Region",
    'label_latitude'    => "Latitude",
    'label_longitude'   => "Longitude",
    'hint_coordinates'  => "Both or neither. Latitude −90 to 90, longitude −180 to 180.",
    'btn_save_location' => "Save location",
    'map_locate_hint'   => "Standing in the pharmacy? Use the locate button on the map.",

    // ── Hours ─────────────────────────────────────────────────────────────
    'section_hours'  => "Opening hours",
    'hours_intro'    => "A day with no time, not marked open all day and not marked closed, is left unstated.",
    'btn_save_hours' => "Save hours",
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
