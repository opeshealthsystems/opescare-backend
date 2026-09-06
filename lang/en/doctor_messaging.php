<?php

/*
| Clinician mobile API — messaging and the clinician's own profile.
|
| API strings, so they are deliberately plain: they are read on a phone by
| someone who is with a patient, and they must say what happened without
| naming a table, a status code or a thread that may not be theirs.
*/

return [

    // ── Messaging ────────────────────────────────────────────────────────
    // One message for "no such thread" and for "not yours". A distinct
    // "you may not read this one" would confirm the conversation exists,
    // which is exactly what an enumerating caller is looking for.
    'thread_not_found' => 'This conversation is not available.',
    'send_failed'      => 'Your reply could not be sent. The conversation may have been closed.',

    // Opening a conversation WITH a patient (store). The patient refusal is
    // the chart's rule in the chart's words — one rule about what a care
    // relationship is, whether a clinician wants to read or to write.
    'patient_not_found'   => 'No patient record matches that identifier.',
    'no_relationship'     => 'You do not have a care relationship with this patient, so a conversation cannot be opened with them. Ask the patient to grant your facility access, or open the chart from your own schedule.',
    // A thread the clinician's own inbox could never list is worse than a
    // refusal: the patient would believe they had been written to.
    'no_delivery_context' => 'This patient has no appointment at your facility, so a conversation opened here would not reach your inbox. Book or record an appointment for them first.',
    'thread_not_created'  => 'The conversation could not be opened. Please try again.',

    // ── Profile photo ────────────────────────────────────────────────────
    'photo_not_an_image' => 'That file is not an image. Upload a JPEG, PNG or WebP photo.',
    'photo_dimensions'   => 'That image is too large. The longest side may be up to :max pixels.',
    'photo_not_found'    => 'This photo is no longer available.',

    // ── Session ──────────────────────────────────────────────────────────
    'unauthenticated' => 'Your session has ended. Sign in again.',

];
