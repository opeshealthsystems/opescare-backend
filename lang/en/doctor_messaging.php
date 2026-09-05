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

    // ── Profile photo ────────────────────────────────────────────────────
    'photo_not_an_image' => 'That file is not an image. Upload a JPEG, PNG or WebP photo.',
    'photo_dimensions'   => 'That image is too large. The longest side may be up to :max pixels.',
    'photo_not_found'    => 'This photo is no longer available.',

    // ── Session ──────────────────────────────────────────────────────────
    'unauthenticated' => 'Your session has ended. Sign in again.',

];
