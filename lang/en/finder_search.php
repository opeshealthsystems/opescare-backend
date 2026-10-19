<?php

/*
|--------------------------------------------------------------------------
| Finder task pages — medicine search, blood search, emergency
|--------------------------------------------------------------------------
| Copy for the three public task screens. Two rules every string here
| honours:
|   1. Honesty about provenance — the platform relays what facilities
|      report; it never checks a shelf, so no string may imply it did,
|      and nothing here claims anything is "verified".
|   2. EN/FR stay 1:1 (php scripts/i18n-audit.php must report 0).
*/

return [

    'common' => [
        'brand_home'        => 'OpesCare',
        'directory_link'    => 'All facilities',
        'emergency_link'    => 'Emergency',
        'call'              => 'Call',
        'directions'        => 'Directions',
        'view_profile'      => 'View profile',
        'no_phone'          => 'No phone number on record',
        'distance_km'       => ':distance km',
        'use_my_location'   => 'Use my location',
        'locating'          => 'Finding your location…',
        'location_failed'   => 'Location unavailable — results are not sorted by distance.',
        'location_active'   => 'Results are sorted by distance from your location.',
        'info_may_change'   => 'Information may change. Call the facility before travelling.',
        'showing_first'     => 'Showing the first :count results.',
        'search'            => 'Search',
    ],

    'medicine' => [
        'intro'             => 'Find a pharmacy that has your medicine: type its name to see pharmacies across Cameroon that have reported it in stock, with the report time and a number to call. OpesCare does not check shelves — every result is the pharmacy’s own report.',
        'search_label'      => 'What medicine do you need?',
        'search_placeholder' => 'e.g. Paracetamol, Amoxicillin…',
        'results_count'     => '{1} :count pharmacy has reported this medicine.|[2,*] :count pharmacies have reported this medicine.',
        'reported_line'     => 'Reported by the pharmacy · :time',
        'status_in_stock'   => 'Reported in stock',
        'status_low'        => 'Reported low stock',
        'status_out'        => 'Reported out of stock',
        'status_unknown'    => 'No status reported',
        'price_fcfa'        => ':price FCFA',
        'pack_of'           => 'Pack of :size',
        'quantity_range'    => 'About :range packs reported',
        'before_you_travel' => 'A report is not a reservation — stock moves. Check the report time and call the pharmacy before travelling.',
        'empty_title'       => 'No pharmacy has reported this medicine’s stock yet.',
        'empty_body'        => 'That does not mean it is unavailable — only that no pharmacy on the network has reported it. Call a nearby pharmacy directly.',
        'empty_cta'         => 'Find nearby pharmacies',
    ],

    'blood' => [
        'intro'             => 'Find blood by group: choose a blood group to see hospitals and blood banks that have reported holding it, with a number to call before you travel. OpesCare relays their reports — it does not count units itself.',
        'group_label'       => 'Select a blood group',
        'component_label'   => 'Component',
        'component_whole_blood' => 'Whole blood',
        'component_red_cells'   => 'Red cells',
        'component_plasma'      => 'Plasma',
        'component_platelets'   => 'Platelets',
        'search_button'     => 'Search availability',
        'results_count'     => '{1} :count facility has published availability for :group.|[2,*] :count facilities have published availability for :group.',
        'units_reported'    => 'Reported units: :range',
        'reported_line'     => 'Reported by the facility · :time',
        'before_you_travel' => 'Availability can change within hours. Call the facility before travelling — do not act on this page alone.',
        'empty_title'       => 'No blood bank has published availability for :group right now.',
        'empty_body'        => 'Absence here does not mean absence everywhere — it means no facility on the network has published it. Call nearby hospitals and blood banks directly.',
        'empty_emergency_cta' => 'Emergency guidance',
        'request_title'     => 'Need blood urgently?',
        'request_body'      => 'A blood request is sent to a facility from the OpesCare patient app, or by calling the blood bank directly. In a life-threatening situation, use the emergency page.',
        'request_app_cta'   => 'About the patient app',
        'clinical_note'     => 'OpesCare does not cross-match and does not clear a unit for transfusion. Compatibility testing remains the responsibility of qualified professionals.',
    ],

    'emergency' => [
        'page_title'        => 'Emergency — call first | OpesCare',
        'call_now_title'    => 'In a life-threatening emergency, call now',
        'call_now_sub'      => 'Do not rely on this page alone — a phone call is faster than any list.',
        'dial_112'          => 'Call 112',
        'dial_119'          => 'Call 119',
        'dial_hint'         => 'National emergency numbers',
        'nearest_title'     => 'Hospitals near you',
        'nearest_sub'       => 'Sorted by distance when your location is on',
        'call_before'       => 'Call before travelling',
        'back_to_directory' => 'All facilities',
    ],

];
