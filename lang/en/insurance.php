<?php

return [
    'eligibility' => [
        'check'        => 'Check Eligibility',
        'eligible'     => 'Patient is eligible for coverage.',
        'not_eligible' => 'Patient is not currently eligible for this plan.',
        'pending'      => 'Eligibility check pending.',
        'expired'      => 'Coverage has expired.',
    ],
    'preauth' => [
        'title'        => 'Preauthorization Request',
        'service'      => 'Service Description',
        'cost'         => 'Estimated Cost',
        'urgency'      => 'Urgency',
        'submit'       => 'Submit Request',
        'approved'     => 'Preauthorization approved.',
        'rejected'     => 'Preauthorization rejected.',
        'pending'      => 'Awaiting payer decision.',
        'required'     => 'Preauthorization is required before this service can be performed.',
    ],
    'claims' => [
        'title'        => 'Insurance Claim',
        'create'       => 'Create Claim',
        'submit'       => 'Submit Claim',
        'status'       => [
            'draft'      => 'Draft',
            'submitted'  => 'Submitted',
            'under_review' => 'Under Review',
            'approved'   => 'Approved',
            'rejected'   => 'Rejected',
            'partial'    => 'Partially Approved',
            'paid'       => 'Paid',
        ],
        'minimum_data' => 'Insurance reviewers see only minimum necessary data for claim review.',
        'approved_msg' => 'Claim approved for :amount.',
        'rejected_msg' => 'Claim rejected. Reason: :reason.',
    ],
];
