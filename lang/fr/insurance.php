<?php

return [
    'eligibility' => [
        'check'        => 'Vérifier l\'éligibilité',
        'eligible'     => 'Le patient est éligible à la couverture.',
        'not_eligible' => 'Le patient n\'est pas actuellement éligible à ce régime.',
        'pending'      => 'Vérification d\'éligibilité en cours.',
        'expired'      => 'La couverture a expiré.',
    ],
    'preauth' => [
        'title'        => 'Demande de préautorisation',
        'service'      => 'Description de la prestation',
        'cost'         => 'Coût estimé',
        'urgency'      => 'Urgence',
        'submit'       => 'Soumettre la demande',
        'approved'     => 'Préautorisation accordée.',
        'rejected'     => 'Préautorisation refusée.',
        'pending'      => 'En attente de décision de l\'assureur.',
        'required'     => 'Une préautorisation est requise avant de réaliser cette prestation.',
    ],
    'claims' => [
        'title'        => 'Demande de remboursement',
        'create'       => 'Créer une demande',
        'submit'       => 'Soumettre la demande',
        'status'       => [
            'draft'        => 'Brouillon',
            'submitted'    => 'Soumis',
            'under_review' => 'En cours d\'examen',
            'approved'     => 'Approuvé',
            'rejected'     => 'Refusé',
            'partial'      => 'Partiellement approuvé',
            'paid'         => 'Payé',
        ],
        'minimum_data' => 'Les examinateurs d\'assurance ne voient que les données minimales nécessaires à l\'examen de la demande.',
        'approved_msg' => 'Demande approuvée pour :amount.',
        'rejected_msg' => 'Demande refusée. Motif : :reason.',
    ],
];
