<?php

// Appointment waitlist — patient portal + facility desk.

return [
    'nav'                  => "Waitlist",
    'title'                => "Appointment Waitlist",
    'subtitle'             => "Ask to be told when a slot frees up at a facility.",
    'join_title'           => "Join the waitlist",
    'join_help'            => "When an appointment is cancelled at this facility, the next patient in line is notified and can book the slot.",
    'field_facility'       => "Facility",
    'field_type'           => "Type of appointment",
    'field_earliest'       => "Earliest date",
    'field_latest'         => "Latest date",
    'window_help'          => "Both dates are optional. Leave them blank to accept any date.",
    'field_urgency'        => "Urgency",
    'field_notes'          => "Notes for the facility",
    'notes_ph'             => "Anything the desk should know (optional)",
    'btn_join'             => "Join the waitlist",
    'btn_leave'            => "Leave the waitlist",
    'my_entries'           => "My waitlist entries",
    'empty_title'          => "You are not on any waitlist",
    'empty_desc'           => "Join one above and we will tell you when a slot opens.",
    'any_date'             => "Any date",
    'joined_on'            => "Joined :date",
    'notified_hint'        => "A slot has opened — book it now.",
    'flash_joined'         => "You have joined the waitlist. We will notify you when a slot opens.",
    'flash_cancelled'      => "You have left the waitlist.",
    'flash_not_cancellable' => "This waitlist entry is already closed.",
    'notify_title'         => "A slot has opened",
    'notify_body'          => "A slot opened at :facility on :date — book now.",

    // Facility desk
    'staff_title'          => "Waitlist",
    'staff_subtitle'       => "Patients waiting for a slot at this facility, most urgent first.",
    'staff_empty_title'    => "Nobody is waiting",
    'staff_empty_desc'     => "No patient has joined this facility's waitlist.",
    'col_patient'          => "Patient",
    'col_window'           => "Preferred window",
    'col_urgency'          => "Urgency",
    'col_joined'           => "Joined",
    'btn_book'             => "Book",
    'booking_from'         => "Booking from the waitlist for :health_id — the entry is closed once this appointment is saved.",

    // Console
    'expired_count'        => ":count waitlist entry(ies) expired.",
];
