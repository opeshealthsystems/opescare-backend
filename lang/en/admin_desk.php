<?php

/*
|--------------------------------------------------------------------------
| Admin console — completeness pass (2026-10-18)
|--------------------------------------------------------------------------
|
| Strings added when every admin list gained a detail page and every
| destructive action a confirmation. Own file, 1:1 with lang/fr/admin_desk.php,
| so `php scripts/i18n-audit.php` parity is obvious at a glance.
|
*/

return [

    // ── Shared across the detail pages ──
    'back_to_list'     => 'Back to list',
    'details'          => 'Details',
    'reference'        => 'Reference',
    'created_at'       => 'Created',
    'reviewed_by'      => 'Reviewed by',
    'reviewed_at'      => 'Reviewed on',
    'name'             => 'Name',
    'email'            => 'Email',
    'phone'            => 'Phone',
    'role'             => 'Role',
    'notes'            => 'Notes',

    // ── Confirmations on irreversible actions ──
    'confirm_cancel_task'        => 'Cancel this task? It stays on record as cancelled and cannot be reopened.',
    'confirm_reject_pair'        => 'Declare these two records different people? The pair leaves the review queue.',
    'confirm_revoke_claim'       => 'Revoke this claim? The facility loses its link to the listing until a new claim is approved.',
    'confirm_merge_import'       => 'Merge this imported record into the existing listing?',
    'confirm_suspend_facility'   => 'Suspend this facility? Its staff lose portal access until it is reactivated.',
    'confirm_suspend_user'       => 'Suspend this user? They are signed out and cannot sign in until reactivated.',
    'confirm_close_breach'       => 'Close this breach record? Closing is final.',
    'confirm_reject_mapping'     => 'Reject this mapping? The code pair is marked as not equivalent.',
    'confirm_reject_credential'  => 'Reject this credential? The professional will see it as suspended with your note.',

    // ── Tasks ──
    'task_detail'       => 'Task',
    'no_description'    => 'No description was given.',
    'acknowledged_at'   => 'Acknowledged',
    'completed_at'      => 'Completed',
    'task_closed_hint'  => 'This task is closed. Closed tasks are kept on record and cannot be reopened; create a new task if the work is still needed.',

    // ── Credential verification ──
    'credential_detail'        => 'Credential',
    'verified'                 => 'Verified',
    'awaiting_review'          => 'Awaiting review',
    'open_user'                => 'Open the user record',
    'issued_on'                => 'Issued on',
    'expires_on'               => 'Expires on',
    'no_expiry'                => 'No expiry',
    'document'                 => 'Document',
    'document_on_file'         => 'On file',
    'document_none'            => 'None uploaded',
    'decision'                 => 'Decision',
    'verified_at'              => 'Decided on',
    'credential_decided_hint'  => 'This credential has already been decided. The decision, who took it and when are shown above.',

    // ── Leads ──
    'lead_detail'        => 'Lead',
    'lead_message'       => 'Message as sent',
    'lead_no_message'    => 'The form was sent without a message.',
    'lead_notes'         => 'Notes and status history',
    'lead_no_notes'      => 'No notes yet. Each status change with a note is added here with its date.',
    'lead_tenant'        => 'Converted to',
    'lead_converted_at'  => 'Converted on',

    // ── Identity pairs ──
    'mpi_pair_detail'    => 'Identity pair',
    'mpi_pair_subtitle'  => 'The two records side by side, the evidence for and against, and the decision taken — or still to take.',
    'mpi_open_pair'      => 'Open this pair',

    // ── Security incidents ──
    'incident_detail'    => 'Incident',
    'opened_by'          => 'Opened by',

];
