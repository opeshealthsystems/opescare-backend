<?php

/*
|--------------------------------------------------------------------------
| Mobile Family / Dependents API
|--------------------------------------------------------------------------
|
| Messages returned by MobileFamilyController and the ActAsDependent
| middleware, and the in-app notifications a family invitation writes.
| Keep 1:1 with lang/fr/family_api.php (php scripts/i18n-audit.php).
|
*/

return [
    // ── Refusals ────────────────────────────────────────────────────────────
    'dependent_forbidden'  => "You do not have access to this family member's record.",
    'member_not_found'     => 'Family member not found.',
    'dependent_is_adult'   => 'This person is :age or older. Adults keep their own Health ID — send them an invitation instead.',
    'invitation_not_sent'  => 'No invitation could be sent to that contact. Check the details, or ask them to share their Health ID.',
    'invitation_not_found' => 'This invitation is no longer available.',

    // ── Outcomes ────────────────────────────────────────────────────────────
    'invitation_accepted'  => 'Invitation accepted. :name can now see your record.',
    'invitation_declined'  => 'Invitation declined.',

    // ── Labels ──────────────────────────────────────────────────────────────
    'unknown_contact' => 'Unknown contact',
    'someone'         => 'Someone',

    'access' => [
        'full'      => 'full access',
        'read_only' => 'read only',
    ],

    // The guardian's role toward the dependent ("linked to your record as your …").
    'relationship' => [
        'parent'         => 'parent',
        'grandparent'    => 'grandparent',
        'child'          => 'child',
        'spouse'         => 'spouse',
        'sibling'        => 'sibling',
        'caregiver'      => 'caregiver',
        'legal_guardian' => 'legal guardian',
        'other'          => 'family member',
    ],

    // ── In-app notifications ────────────────────────────────────────────────
    'notification_invite_title'    => ':guardian wants to be linked to your record',
    'notification_invite_message'  => ':guardian asked to be linked to your OpesCare record as your :relationship (:access). Open Family to accept or decline.',
    'notification_invite_action'   => 'Review invitation',

    'notification_accepted_title'   => ':name accepted your invitation',
    'notification_accepted_message' => "You can now see :name's record from Family.",
    'notification_declined_title'   => ':name declined your invitation',
    'notification_declined_message' => 'No link was created. You can send a new invitation later.',
    'notification_answer_action'    => 'Open Family',
    'invitation_queued' => 'If this person holds an OpesCare Health ID, they will receive your invitation in their app. It will appear under your pending invitations once sent.',
];
