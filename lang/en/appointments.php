<?php

return [
    'book' => [
        'title'       => 'Book Appointment',
        'subtitle'    => 'Schedule a visit with your provider',
        'date'        => 'Date',
        'time'        => 'Time',
        'provider'    => 'Provider',
        'type'        => 'Appointment Type',
        'reason'      => 'Reason for Visit',
        'notes'       => 'Additional Notes',
        'submit'      => 'Confirm Booking',
        'success'     => 'Appointment booked successfully.',
        'conflict'    => 'This time slot is no longer available. Please select another.',
    ],
    'status' => [
        'scheduled'  => 'Scheduled',
        'confirmed'  => 'Confirmed',
        'checked_in' => 'Checked In',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'no_show'    => 'No Show',
        'rescheduled' => 'Rescheduled',
    ],
    'actions' => [
        'reschedule'  => 'Reschedule',
        'cancel'      => 'Cancel Appointment',
        'check_in'    => 'Check In',
        'no_show'     => 'Mark as No Show',
        'confirm'     => 'Confirm Appointment',
    ],
    'cancel_modal' => [
        'title'   => 'Cancel Appointment',
        'reason'  => 'Reason for Cancellation',
        'confirm' => 'Yes, Cancel',
        'back'    => 'Go Back',
    ],
    'reminders' => [
        'sent'    => 'Appointment reminder sent.',
        'pending' => 'Reminder scheduled for :time.',
    ],
    'no_show' => [
        'recorded' => 'No show recorded for :patient.',
        'fee_note' => 'A no-show fee may apply per facility policy.',
    ],
];
