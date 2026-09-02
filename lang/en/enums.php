<?php

/**
 * Centralised translations for DB enum / status values surfaced in the UI.
 * Rendered via the @enum() Blade directive and App\Support\Enums::label().
 *
 * Keys are the raw column values (hyphens normalised to underscores). Values
 * not listed here fall back to a title-cased version of the raw value, so this
 * file only needs the values we want polished — new values degrade gracefully.
 *
 * EN and FR MUST keep identical keys (enforced by scripts/i18n-audit.php).
 */
return [

    // General lifecycle / clinical / workflow / billing statuses.
    'status' => [
        'active'                    => 'Active',
        'inactive'                  => 'Inactive',
        'pending'                   => 'Pending',
        'draft'                     => 'Draft',
        'submitted'                 => 'Submitted',
        'under_review'              => 'Under Review',
        'approved'                  => 'Approved',
        'rejected'                  => 'Rejected',
        'cancelled'                 => 'Cancelled',
        'completed'                 => 'Completed',
        'in_progress'               => 'In Progress',
        'scheduled'                 => 'Scheduled',
        'confirmed'                 => 'Confirmed',
        'rescheduled'               => 'Rescheduled',
        'checked_in'                => 'Checked In',
        'no_show'                   => 'No Show',
        'open'                      => 'Open',
        'closed'                    => 'Closed',
        'escalated'                 => 'Escalated',
        'acknowledged'              => 'Acknowledged',
        'resolved'                  => 'Resolved',
        'dismissed'                 => 'Dismissed',
        'disputed'                  => 'Disputed',
        'expired'                   => 'Expired',
        // Prescription lifecycle — a clinical event is corrected, never overwritten.
        'dispensed'                 => 'Dispensed',
        'partially_dispensed'       => 'Partially Dispensed',
        'amended'                   => 'Amended',
        'voided'                    => 'Voided',
        'entered_in_error'          => 'Entered in Error',
        'suspended'                 => 'Suspended',
        'terminated'                => 'Terminated',
        'archived'                  => 'Archived',
        'verified'                  => 'Verified',
        'unverified'                => 'Unverified',
        'provisional'               => 'Provisional',
        'merged'                    => 'Merged',
        'deceased'                  => 'Deceased',
        'sent'                      => 'Sent',
        'accepted'                  => 'Accepted',
        'withdrawn'                 => 'Withdrawn',
        'on_leave'                  => 'On Leave',
        'planned'                   => 'Planned',
        'issued'                    => 'Issued',
        'paid'                      => 'Paid',
        'partially_paid'            => 'Partially Paid',
        'refunded'                  => 'Refunded',
        'partially_refunded'        => 'Partially Refunded',
        'successful'                => 'Successful',
        'failed'                    => 'Failed',
        'balanced'                  => 'Balanced',
        'discrepancy'               => 'Discrepancy',
        'partially_approved'        => 'Partially Approved',
        'more_information_required' => 'More Information Required',
        'trialing'                  => 'Trialing',
        'past_due'                  => 'Past Due',
        'paused'                    => 'Paused',
        'payment_failed'            => 'Payment Failed',
        'payment_required'          => 'Payment Required',
        'collected'                 => 'Collected',
        'processing'                => 'Processing',
        'resulted'                  => 'Resulted',
        'new_signal'                => 'New Signal',
        'validated'                 => 'Validated',
        'validation_failed'         => 'Validation Failed',
        'mapping_required'          => 'Mapping Required',
        'preview_ready'             => 'Preview Ready',
        'approved_for_import'       => 'Approved for Import',
        'importing'                 => 'Importing',
        'rolled_back'               => 'Rolled Back',
        'requires_manual_review'    => 'Requires Manual Review',
        'completed_with_errors'     => 'Completed with Errors',
        'queued'                    => 'Queued',
        'applied'                   => 'Applied',
        'conflict'                  => 'Conflict',
        'revoked'                   => 'Revoked',
        'published'                 => 'Published',
        'waiting'                   => 'Waiting',
        'called'                   => 'Called',
        'in_service'                => 'In Service',
        'ready'                     => 'Ready',
        'flagged'                   => 'Flagged',
        'partial'                   => 'Partial',
        'overdue'                   => 'Overdue',
        'unpaid'                    => 'Unpaid',
    ],

    'severity' => [
        'info'             => 'Info',
        'low'              => 'Low',
        'mild'             => 'Mild',
        'moderate'         => 'Moderate',
        'medium'           => 'Medium',
        'warning'          => 'Warning',
        'high'             => 'High',
        'severe'           => 'Severe',
        'critical'         => 'Critical',
        'life_threatening' => 'Life-Threatening',
    ],

    'urgency' => [
        'routine'   => 'Routine',
        'urgent'    => 'Urgent',
        'emergency' => 'Emergency',
        'stat'      => 'STAT',
    ],

    'priority' => [
        'low'      => 'Low',
        'normal'   => 'Normal',
        'medium'   => 'Medium',
        'high'     => 'High',
        'urgent'   => 'Urgent',
        'critical' => 'Critical',
    ],

    'decision' => [
        'approved'                  => 'Approved',
        'rejected'                  => 'Rejected',
        'deferred'                  => 'Deferred',
        'pending'                   => 'Pending',
        'more_info_needed'          => 'More Info Needed',
        'more_information_required' => 'More Information Required',
        'partially_approved'        => 'Partially Approved',
    ],

    'environment' => [
        'sandbox'    => 'Sandbox',
        'staging'    => 'Staging',
        'production' => 'Production',
    ],

    'stock_status' => [
        'in_stock'     => 'In Stock',
        'low_stock'    => 'Low Stock',
        'out_of_stock' => 'Out of Stock',
        'expired'      => 'Expired',
        // PharmacyStockStatus::Unknown — reported stock has a real "not known"
        // state and must never render as available.
        'unknown'      => 'Stock Unknown',
    ],

    'level' => [
        'bronze'   => 'Bronze',
        'silver'   => 'Silver',
        'gold'     => 'Gold',
        'platinum' => 'Platinum',
    ],

    'platform' => [
        'ios'     => 'iOS',
        'android' => 'Android',
        'web'     => 'Web',
        'windows' => 'Windows',
        'linux'   => 'Linux',
        'macos'   => 'macOS',
    ],

    'verification' => [
        'unverified'       => 'Unverified',
        'license_verified' => 'License Verified',
        'partner_verified' => 'Partner Verified',
        'pending'          => 'Pending',
        'provisional'      => 'Provisional',
        'verified'         => 'Verified',
        'suspended'        => 'Suspended',
        'deceased'         => 'Deceased',
        'merged'           => 'Merged',
        'active'           => 'Active',
    ],

    'leave_type' => [
        'annual'    => 'Annual Leave',
        'sick'      => 'Sick Leave',
        'emergency' => 'Emergency',
        'maternity' => 'Maternity',
        'paternity' => 'Paternity',
        'study'     => 'Study',
        'unpaid'    => 'Unpaid',
    ],

    'staff_category' => [
        'clinical'       => 'Clinical',
        'administrative' => 'Administrative',
        'support'        => 'Support',
        'management'     => 'Management',
    ],

    'blood_component' => [
        'whole_blood'         => 'Whole Blood',
        'packed_red_cells'    => 'Packed Red Cells',
        'fresh_frozen_plasma' => 'Fresh Frozen Plasma',
        'platelets'           => 'Platelets',
        'cryoprecipitate'     => 'Cryoprecipitate',
    ],

    // Routes of administration offered on the prescribing form.
    'medication_route' => [
        'oral'       => 'Oral',
        'iv'         => 'IV (Intravenous)',
        'im'         => 'IM (Intramuscular)',
        'sc'         => 'SC (Subcutaneous)',
        'topical'    => 'Topical',
        'inhalation' => 'Inhalation',
        'rectal'     => 'Rectal',
        'ophthalmic' => 'Ophthalmic',
        'otic'       => 'Otic (ear)',
        'nasal'      => 'Nasal',
    ],

    'resource_type' => [
        'patient'        => 'Patient',
        'visit'          => 'Visit',
        'triage_record'  => 'Triage Record',
        'clinical_note'  => 'Clinical Note',
        'invoice'        => 'Invoice',
        'support_ticket' => 'Support Ticket',
        'prescription'   => 'Prescription',
        'lab_order'      => 'Lab Order',
        'appointment'    => 'Appointment',
    ],
];
