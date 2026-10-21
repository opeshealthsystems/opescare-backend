<?php

// Public page /master-patient-index (resources/views/public/master-patient-index.blade.php).
// Backed by: Patient::findExistingIdentity(), MasterPatientIndexService (blocking,
// scoring, thresholds), PatientMergeService (reversible merge, aliases, LIFO unmerge),
// AdminIdentityReviewController (/admin/mpi, reason required), routes/console.php
// (mpi:detect nightly + weekly). Nothing auto-merges. Do not add claims the code lacks.
return [
    'meta_title' => 'Master Patient Index: one person, one Health ID',
    'meta_desc' => 'Why the same patient ends up with several records in Cameroon, how OpesCare finds likely duplicates across facilities, and how a person reviews and merges them into one Health ID, reversibly.',
    'breadcrumb' => 'Master Patient Index',

    'hero' => [
        'kicker' => 'Master Patient Index',
        'title_1' => 'One person.',
        'title_2' => 'One Health ID.',
        'lead' => 'The same patient is often registered at a hospital, a clinic and a laboratory, under slightly different names. The OpesCare Master Patient Index finds those records, puts them in front of a person to review, and joins them into one Health ID in a way that can be undone.',
        'cta_register' => 'Register your facility',
        'cta_solutions' => 'See the systems',
    ],

    'vis' => [
        'label' => 'Three sample records from different facilities joined into one patient',
        'sample' => 'Sample records',
        'rec1_src' => 'Hospital · 2019',
        'rec2_src' => 'Clinic · 2023',
        'rec3_src' => 'Laboratory · 2026',
        'one' => 'One patient',
        'linked' => 'Linked records',
        'facilities' => 'Facilities',
        'reviewed' => 'Reviewed by',
        'reviewed_v' => 'A person',
        'merge' => 'Merge',
        'merge_v' => 'Reversible',
    ],

    'what' => [
        'eyebrow' => 'What it is',
        'title' => 'What a Master Patient Index does.',
        'sub' => 'A Master Patient Index (MPI) is the register that knows which records belong to the same human being. Without one, a patient\'s history is split across files, and a clinician sees only part of it: an allergy recorded at one facility is missing at another.',
        'causes_title' => 'Why duplicates happen in Cameroon',
        'causes' => [
            'order' => ['title' => 'Name order', 'desc' => 'Family name first at one desk, last at another: "DONGMO Amara" and "Amara Dongmo".'],
            'spelling' => ['title' => 'Spelling, accents and capitals', 'desc' => '"Nshomé" and "Nshome", capitals, extra spaces, a middle initial added or left out.'],
            'dob' => ['title' => 'Missing or estimated birth dates', 'desc' => 'Many adults know only their birth year, so a record holds an estimate, or nothing.'],
            'facilities' => ['title' => 'Several facilities, several files', 'desc' => 'Each hospital, clinic and laboratory registers the patient again under its own number.'],
            'phones' => ['title' => 'More than one phone number', 'desc' => 'Two SIM cards are common, so one person can give a different number at each visit.'],
        ],
    ],

    'match' => [
        'eyebrow' => 'How matching works',
        'title' => 'Evidence for, evidence against.',
        'sub' => 'Matching is careful by design: it is better to ask a person than to join two different people.',
        'items' => [
            'check' => ['title' => 'A check at every registration', 'desc' => 'Every path that creates a patient asks one question first: does this person already hold a Health ID? It compares the national ID, the phone number (as a keyed hash, never in clear) and the name with the date of birth, ignoring case, spacing and date format. A match is returned to the person registering; nothing is merged.'],
            'blocking' => ['title' => 'Candidates from every facility', 'desc' => 'Scans compare records that share a phone number, a national ID, the same first and last name with accents folded, or an external identifier, across every facility on OpesCare.'],
            'score' => ['title' => 'A score with its reasons', 'desc' => 'Agreement on name, phone, date of birth, sex, national ID or identifier adds to the score. A different sex, date of birth or national ID takes away a lot; a different phone takes away only a little. The reviewer sees the reasons, such as "name matched, phones differ".'],
            'limit' => ['title' => 'What it does not catch on its own', 'desc' => 'A swapped name order is not matched on the name alone. Those pairs surface through a shared phone, national ID or identifier, or a reviewer raises the pair by hand.'],
            'scans' => ['title' => 'Nightly and weekly scans', 'desc' => 'Every night, records created or changed recently are compared with the whole register; once a week a wider pass runs. A pair whose records changed is scored again.'],
        ],
    ],

    'flow' => [
        'eyebrow' => 'How it works',
        'title' => 'From several records to one Health ID.',
        'sub' => 'The path every duplicate takes, from detection to a merge that can be undone.',
        'label' => 'The merge journey',
        'pause' => 'Pause',
        'play' => 'Play',
        'prev' => 'Previous step',
        'next' => 'Next step',
        'step_of' => 'Step :n of :total',
        'steps' => [
            ['who' => 'Facilities', 'title' => 'Several records', 'desc' => 'The same person is registered at a hospital, a clinic and a laboratory, with small differences in name, birth date or phone.', 'chip' => 'Three files'],
            ['who' => 'OpesCare', 'title' => 'A candidate pair is found', 'desc' => 'A scan finds two records that share a key and scores them, or a reviewer raises the pair by hand. Pairs below the threshold are not queued.', 'chip' => 'Score and reasons'],
            ['who' => 'Records reviewer', 'title' => 'Reviewed side by side', 'desc' => 'On the OpesCare review desk, a person compares the two records and their evidence, and decides: the same person, or two people.', 'chip' => 'Human decision'],
            ['who' => 'Records reviewer', 'title' => 'Merged into one Health ID', 'desc' => 'The reviewer chooses which record survives and writes a reason. Every linked record moves to it; the other Health ID becomes an alias that still opens the record. The survivor\'s details are never overwritten, only blanks filled.', 'chip' => 'One Health ID'],
            ['who' => 'Audit', 'title' => 'Reversible and audited', 'desc' => 'Each merge records who, when and why, and can be undone exactly, newest first, with a written reason. It cannot be undone once a record has been deleted at the patient\'s request.', 'chip' => 'Undo available'],
        ],
    ],

    'how' => [
        'eyebrow' => 'How OpesCare makes it possible',
        'title' => 'Built so that nothing merges itself.',
        'sub' => 'Each of these is part of OpesCare today.',
        'cards' => [
            'check' => ['title' => 'One identity check', 'desc' => 'The same duplicate check runs when a patient registers in the app, adds a family member or completes a profile, and when a facility front desk registers a patient.'],
            'cross' => ['title' => 'Across every facility', 'desc' => 'Detection looks at the whole register, not one facility at a time, newest records first.'],
            'reasons' => ['title' => 'Scores that explain themselves', 'desc' => 'Each candidate carries its score, a tier (deterministic, probable or possible) and the reasons behind it.'],
            'human' => ['title' => 'A person decides, every time', 'desc' => 'Even a pair that shares a national ID waits for a reviewer. No merge happens without a chosen survivor and a written reason.'],
            'merge' => ['title' => 'Complete, reversible merges', 'desc' => 'Every table that points at a patient is found at run time, so a merge moves everything, and the record of what moved makes the undo exact.'],
            'alias' => ['title' => 'Old Health IDs keep working', 'desc' => 'A card issued under the retired Health ID still opens the patient\'s record, and lookups follow the merge.'],
        ],
    ],

    'means' => [
        'eyebrow' => 'What it means',
        'title' => 'For patients and for facilities.',
        'patients' => 'For patients',
        'facilities' => 'For facilities',
        'p1_title' => 'One history, wherever you were seen',
        'p1_desc' => 'Allergies, results and prescriptions from every facility sit on one Health ID.',
        'p2_title' => 'Your old card still works',
        'p2_desc' => 'If two of your records are joined, the Health ID you were given first still opens your record.',
        'p3_title' => 'No one is joined by a computer alone',
        'p3_desc' => 'A person checks every pair, and a mistake can be undone.',
        'f1_title' => 'Fewer duplicate files',
        'f1_desc' => 'A returning patient is recognised at registration instead of being registered again.',
        'f2_title' => 'Results on the right person',
        'f2_desc' => 'Results and referrals follow the merge to the surviving record.',
        'f3_title' => 'Identity decisions on the record',
        'f3_desc' => 'Every merge, rejection and undo is logged with who made it and why.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Frequently asked questions',
        'categories' => [
            ['items' => [
                ['q' => 'What is a Master Patient Index?', 'a' => 'It is the register that knows which patient records belong to the same person, so that one person has one record, here one Health ID, across every facility.'],
                ['q' => 'Does OpesCare merge records automatically?', 'a' => 'No. Scans only suggest pairs. Every merge is made by a person on the OpesCare review desk, who chooses the surviving record and writes a reason, even when the two records share a national ID.'],
                ['q' => 'What if two different people are merged by mistake?', 'a' => 'The merge can be undone. OpesCare keeps a record of every row that moved, so the undo puts back exactly those rows. Merges are undone newest first, and an undo also needs a written reason.'],
                ['q' => 'My name is written differently at two hospitals. Will I get two Health IDs?', 'a' => 'Registration checks for an existing Health ID by national ID, phone number, and name with date of birth. If two records are created anyway, the scans can pair them through a shared phone, national ID or identifier, and a reviewer can raise a pair by hand when names are written in a different order.'],
                ['q' => 'Will my old Health ID card still work after a merge?', 'a' => 'Yes. The retired Health ID becomes an alias of the surviving one, and scanning or entering it opens your record.'],
                ['q' => 'Who can see the review desk?', 'a' => 'Only OpesCare platform administrators. Facility staff do not see other facilities\' patients through it.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Bring your patients onto one Health ID.',
        'sub' => 'Register your facility and every patient you see is checked against the national register before a new Health ID is made.',
    ],
];
