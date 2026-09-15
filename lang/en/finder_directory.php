<?php

/**
 * Public Care Map directory + facility profile (care_map/directory.blade.php,
 * care_map/profile.blade.php).
 *
 * Truthfulness contract for every string in this file:
 *  - Nothing may describe the directory as verified; per-facility badges render
 *    only when that facility's own verification_status says so.
 *  - Availability language is "reported", never "in stock as a fact" — empty
 *    means "not reported", and the copy must say so.
 */
return [

    // ── Page chrome ─────────────────────────────────────────────────────
    'meta_title'       => 'Care Map — Find Health Services Near You | OpesCare',
    'meta_description' => "Find hospitals, clinics, pharmacies and laboratories near you, listed from Cameroon's national health facility registry and open map data.",
    'brand_sub'        => 'Care Map',
    'emergency'        => 'Emergency',
    'back_to_directory' => 'Back to directory',

    // ── Search & filters ────────────────────────────────────────────────
    'search_label'       => 'Search health facilities',
    'search_placeholder' => 'Search by name, city or type…',
    'search_button'      => 'Search',
    'near_me'            => 'Near me',
    'filter_by_type'     => 'Filter by facility type',
    'filter_by_region'   => 'Filter by region',
    'all_types'          => 'All facilities',
    'all_regions'        => 'All regions',
    'clear_filters'      => 'Clear filters',

    // ── Results & pagination ────────────────────────────────────────────
    'results_count' => '{0}No facilities found|{1}:count facility found|[2,*]:count facilities found',
    'page_of'       => 'Page :current of :last',
    'previous'      => 'Previous',
    'next'          => 'Next',
    'distance_km'   => ':km km',
    'no_results_title' => 'No facilities found',
    'no_results_sub'   => 'Try a different search term, or clear the filters to browse the full directory.',

    // ── Facility card / badges ──────────────────────────────────────────
    'open_24h'            => 'Open 24/7',
    'open_now'            => 'Open now',
    'closed_now'          => 'Closed now',
    'license_verified'    => 'License Verified',
    'government_verified' => 'Government Verified',
    'call'                => 'Call',
    'directions'          => 'Directions',
    'view_profile'        => 'View profile',
    'book_appointment'    => 'Book appointment',

    // ── Map ─────────────────────────────────────────────────────────────
    'show_map'      => 'Show map',
    'hide_map'      => 'Hide map',
    'map_label'     => 'Map of the facilities on this page',
    'map_page_note' => 'The map shows the :count facilities on this page.',
    'locate_me'     => 'Use my location',
    'you_are_here'  => 'You are here',
    'geo_unsupported' => 'Your browser does not support location. You can still search by city or region.',
    'geo_denied'      => 'We could not read your location. Check that location permission is enabled, or search by city instead.',

    // ── Truthfulness / provenance ───────────────────────────────────────
    'disclaimer'          => 'Information may change. Always confirm with the facility by phone before travelling, especially in an emergency.',
    'provenance_heading'  => 'About this listing',
    'provenance_registry' => "This listing comes from Cameroon's national health facility registry.",
    'provenance_source'   => 'Listing source: :source',
    'no_availability'     => 'This facility has not reported any live availability. An empty section means "not reported" — not "not available".',
    'reported_time'       => 'Reported :time',
    'not_dated'           => 'No report date — treat as out of date',

    // ── Profile sections ────────────────────────────────────────────────
    'about_heading'        => 'About',
    'contact_heading'      => 'Contact & location',
    'hours_heading'        => 'Opening hours',
    'price_list_heading'  => 'Published prices',
    'price_list_note'     => 'This practice\'s own price list, in whole francs. A price is information, never a condition of care; confirm with the facility before your visit.',
    'price_xaf'           => ':amount FCFA',
    'price_not_published' => 'Price not published',
    'services_heading'     => 'Services',
    'availability_heading' => 'Reported availability',
    'pharmacy_stock_heading' => 'Medicine stock',
    'blood_heading'        => 'Blood availability',
    'lab_tests_heading'    => 'Lab tests',
    'insurance_heading'    => 'Insurance accepted',
    'address_label'        => 'Address',
    'phone_label'          => 'Phone',
    'email_label'          => 'Email',
    'website_label'        => 'Website',
    'region_label'         => 'Region',
    'appointment_required' => 'Appointment required',
    'requires_doctor_order' => "Requires a clinician's request",
    'turnaround_label'     => 'Turnaround: :time',
    'closed_day'           => 'Closed',
    'booking_hint'         => 'This facility accepts appointment requests through OpesCare.',
    'claim_question'       => 'Do you work at this facility?',
    'claim_cta'            => 'Claim this listing',
    'services_more'        => '+:count more',

    // ── Availability statuses (rendered only for reported rows) ─────────
    'statuses' => [
        'in_stock'     => 'Reported in stock',
        'low_stock'    => 'Reported low stock',
        'out_of_stock' => 'Reported out of stock',
        'available'    => 'Reported available',
        'limited'      => 'Reported limited',
        'unavailable'  => 'Reported unavailable',
        'unknown'      => 'Not reported',
    ],

    // ── Facility types ──────────────────────────────────────────────────
    'types' => [
        'hospital'          => 'Hospital',
        'clinic'            => 'Clinic',
        'pharmacy'          => 'Pharmacy',
        'health_center'     => 'Health Center',
        'laboratory'        => 'Laboratory',
        'dental'            => 'Dental Clinic',
        'imaging_center'    => 'Imaging Center',
        'eye_clinic'        => 'Eye Clinic',
        'specialist'        => 'Specialist',
        'diagnostic_center' => 'Diagnostic Center',
        'maternity'         => 'Maternity',
        'dispensary'        => 'Dispensary',
        'blood_bank'        => 'Blood Bank',
        'other'             => 'Facility',
    ],

    // ── Days of week (0 = Sunday, matching care_facility_hours) ─────────
    'days' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ],
];
