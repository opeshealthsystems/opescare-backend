<?php

// Drug-interaction warnings on the prescriber desk (imported from the platform,
// docs/PLATFORM_IMPORTS.md group C). Advisory only — never a block.
return [
    'panel_title'   => 'Possible drug interactions',
    'panel_hint'    => 'These are warnings, not a block. Review them against the patient before issuing; the prescription will still save.',
    'acknowledge'   => 'I have reviewed these interaction warnings',
    'coverage_note' => 'The interaction check covers a short reference list only. No warning does not mean a combination is safe.',
    'saved_with_warnings' => '{1} Prescription saved with 1 interaction warning, recorded in the audit trail.|[2,*] Prescription saved with :count interaction warnings, recorded in the audit trail.',

    'severity_minor'           => 'Minor',
    'severity_moderate'        => 'Moderate',
    'severity_major'           => 'Major',
    'severity_contraindicated' => 'Contraindicated',

    'pair_aspirin_warfarin'           => 'Concurrent use significantly increases bleeding risk.',
    'pair_clopidogrel_warfarin'       => 'Combined anticoagulation/antiplatelet therapy increases haemorrhage risk.',
    'pair_clarithromycin_simvastatin' => 'CYP3A4 inhibition raises simvastatin plasma levels — myopathy risk.',
    'pair_methotrexate_nsaids'        => 'NSAIDs reduce methotrexate clearance — severe toxicity risk.',
];
