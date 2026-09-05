<?php

/*
|--------------------------------------------------------------------------
| Facility register — what a directory listing can actually do
|--------------------------------------------------------------------------
|
| OpesCare keeps two facility registers:
|
|   care_facilities — the public directory a patient browses. A row means
|                     "this place exists and can be found".
|   facilities      — the operational tenant. A row means "care can happen
|                     here, inside this system".
|
| A directory listing is linked to a tenant by care_facilities.facility_id.
| Only a linked listing can publish a clinician, open an appointment slot or
| accept a booking. Most listings are not linked, and these strings exist so
| the app can say that plainly instead of showing a patient a dead end.
|
| Every string here is deliberately about what the FACILITY has done — not
| about the patient's search failing.
|
*/

return [

    'roster' => [
        // No facilities row behind the listing. Nothing will be published here
        // until the operator joins OpesCare.
        'not_connected' => 'This place is in the public directory only. It has not joined OpesCare, so it publishes no clinicians and cannot take an online booking. You can still call it directly.',

        // Linked, but no clinical staff profiles and no open slots.
        'none_published' => 'This facility is on OpesCare but has not published any clinician profiles yet.',

        // Linked, no roster, but the calendar is open. A slot belongs to a
        // facility, not to a named clinician, so booking still works.
        'slots_without_roster' => 'This facility has not published clinician profiles, but its appointment calendar is open — you can book here without choosing a doctor first.',

        // Linked, with a roster.
        'published' => ':count clinician published by this facility.|:count clinicians published by this facility.',
    ],

    'summary' => [
        'title'                => 'Facility register',
        'directory_total'      => 'Listings in the public directory',
        'connected_total'      => 'Connected to OpesCare',
        'bookable_total'       => 'Accepting online bookings',
        'with_providers_total' => 'Publishing clinician profiles',
        'explainer'            => 'Every listing here can be found, called and visited. Only the connected ones can also publish clinicians, open appointment slots and add a visit to your OpesCare record.',
    ],

    'filters' => [
        'connected_only' => 'Connected only',
        'all_listings'   => 'All listings',
        'bookable_only'  => 'Bookable now',
        'showing'        => 'Showing :shown of :total',
    ],

];
