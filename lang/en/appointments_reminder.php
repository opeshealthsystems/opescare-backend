<?php

/*
 * What an appointment reminder says.
 *
 * Written for the patient's own bell and push, in the patient's language —
 * App\Modules\Appointments\Services\AppointmentReminderService::deliver()
 * resolves the locale from the patient's app settings, not from the request,
 * because a reminder is sent by a cron with no user in front of it.
 *
 * The old SMS text these replace ended "Reply CANCEL to cancel". Nothing on
 * this platform reads inbound SMS, so that sentence was a promise the software
 * could not keep; the notification links to the appointment instead.
 */

return [

    'title_48h' => 'Appointment in two days',
    'title_24h' => 'Appointment tomorrow',
    'title_2h'  => 'Appointment in two hours',

    'body' => 'Your appointment at :facility is on :date at :time.',

    // Used when the appointment's facility cannot be named — the reminder is
    // still true and still worth sending.
    'the_facility' => 'your facility',

    'action' => 'View appointment',

];
