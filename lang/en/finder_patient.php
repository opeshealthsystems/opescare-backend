<?php

/*
|--------------------------------------------------------------------------
| Finder — patient portal (conversion path)
|--------------------------------------------------------------------------
| Strings introduced by the patient-portal content redesign: the booking
| wizard, appointment cards, and dashboard. Anything that already existed
| keeps its old key in public.php / notifications.php / messaging.php.
| Must stay 1:1 with lang/fr/finder_patient.php.
*/

return [

    // Dashboard
    'greeting'          => 'Hello, :name',
    'greeting_generic'  => 'Hello',
    'greeting_sub'      => 'Your health, in one place.',
    'quick_actions'     => 'Quick actions',
    'next_appointment'  => 'Next appointment',
    'no_upcoming_title' => 'No upcoming appointments',
    'no_upcoming_body'  => 'Book an appointment at a facility near you.',
    'with_provider'     => 'With :name',

    // Booking wizard
    'steps_label'     => 'Step :current of :total',
    'step_facility'   => 'Facility',
    'step_type'       => 'Appointment type',
    'step_time'       => 'Date & time',
    'step_confirm'    => 'Confirm',
    'btn_continue'    => 'Continue',
    'btn_back'        => 'Back',
    'choose_facility' => 'Choose a facility…',
    'facility_help'   => 'Choose the hospital, clinic or health centre you want to visit.',
    'type_help'       => 'What is this appointment for?',
    'time_help'       => 'Pick your preferred date and time. The facility may propose another slot when it confirms.',
    'step_required'   => 'Please fill in this step before continuing.',
    'confirm_title'   => 'Confirm your request',
    'confirm_check'   => 'Please check the details below before sending.',
    'confirm_facility' => 'Facility',
    'confirm_type'    => 'Type',
    'confirm_time'    => 'Preferred time',
    'confirm_reason'  => 'Reason',
    'not_provided'    => 'Not provided',
    'lifecycle_note'  => 'You are sending a request — not a final booking. The facility will review it and confirm your appointment, and you can follow the status in your appointments list.',

    // Appointment cards & lifecycle
    'requested_hint'  => 'Waiting for the facility to confirm.',
    'reschedule_note' => 'After rescheduling, the appointment goes back to "requested" until the facility confirms the new time.',
    'current_appointment' => 'Current appointment',

    // Messaging / notifications helpers
    'open_conversation' => 'Open conversation',

];
