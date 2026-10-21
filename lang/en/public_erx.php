<?php

// Public page /e-prescription (resources/views/public/e-prescription.blade.php).
// Every claim here is backed by code: PrescriberController::store (care-relationship
// gate + interaction warning), PrescriptionService, MobilePrescriptionController::share
// + PrescriptionAccessService (rx_ token, /verify/rx/{token}), PharmacyPortalController::
// prescriptionLookup (Health ID or reference), PharmacyDeskService (claim, ready, full or
// partial dispense, patient notifications), the controlled register
// (/pharmacy/controlled, /pharmacy/controlled/register). Do not add capabilities the
// code does not have.
return [
    'meta_title' => 'E-prescriptions in Cameroon',
    'meta_desc' => 'How an OpesCare e-prescription travels from the doctor to the patient\'s app to the pharmacy counter in Cameroon: written on the Health ID, verified at the counter, dispensed in full or in part, and recorded.',
    'breadcrumb' => 'E-prescriptions',

    'hero' => [
        'kicker' => 'E-prescriptions',
        'title_1' => 'From the doctor to the pharmacy,',
        'title_2' => 'on the patient\'s Health ID.',
        'lead' => 'An OpesCare e-prescription is written by the clinician on the patient\'s Health ID, arrives in the patient\'s app, and is checked and dispensed at the pharmacy counter of the patient\'s choice. No illegible handwriting, no lost paper, and every step is recorded.',
        'cta_primary' => 'Get your Health ID',
        'cta_secondary' => 'Find a medicine',
    ],

    'net' => [
        'label' => 'An e-prescription connecting the doctor, the patient and the pharmacy',
        'core' => 'E-prescription',
        'core_sub' => 'Health ID · reference · QR',
        'doctor' => 'Doctor',
        'doctor_sub' => 'Writes it on the desk',
        'patient' => 'Patient app',
        'patient_sub' => 'Holds the code',
        'pharmacy' => 'Pharmacy',
        'pharmacy_sub' => 'Verifies and dispenses',
        'record' => 'Record',
        'record_sub' => 'Status kept up to date',
    ],

    'what' => [
        'eyebrow' => 'What it is',
        'title' => 'An e-prescription, in plain language.',
        'sub' => 'It is the same prescription a doctor would write on paper, but created on OpesCare and filed on the patient\'s Health ID. Each one has a short reference, a list of medicines with dose and quantity, a prescriber, a facility and an end date.',
        'b1_title' => 'Written by an authorised clinician',
        'b1_desc' => 'Only doctors and medical supervisors can prescribe, and only for a patient they have a care relationship with.',
        'b2_title' => 'Held by the patient',
        'b2_desc' => 'It appears in the patient\'s app. The patient decides when to show a code to a pharmacy, and can stop sharing it.',
        'b3_title' => 'Dispensed once, then closed',
        'b3_desc' => 'A prescription is dispensed in full or in part, and never overwritten. Corrections go through an amendment or a void.',
        'panel_label' => 'Paper prescription compared with an e-prescription',
        'panel_title' => 'What changes',
        'row1' => 'Handwriting the pharmacist must guess',
        'row1_pill' => 'Typed medicine, dose, quantity',
        'row2' => 'A paper slip that can be copied',
        'row2_pill' => 'Code checked at the counter',
        'row3' => 'No idea what was handed over',
        'row3_pill' => 'Full or partial, recorded',
    ],

    'flow' => [
        'eyebrow' => 'How it works',
        'title' => 'One prescription, from consultation to counter.',
        'sub' => 'A patient sees a doctor, receives an e-prescription and collects the medicine at a pharmacy. This is what happens at each step.',
        'label' => 'The e-prescription journey',
        'pause' => 'Pause',
        'play' => 'Play',
        'prev' => 'Previous step',
        'next' => 'Next step',
        'step_of' => 'Step :n of :total',
        'steps' => [
            ['who' => 'Patient', 'title' => 'The patient is seen', 'desc' => 'The patient attends an appointment or a visit. That care relationship, or a consent grant the patient approved in the app, is what allows the doctor to prescribe.', 'chip' => 'Care relationship'],
            ['who' => 'Doctor', 'title' => 'The doctor prescribes', 'desc' => 'On the prescriber desk the doctor finds the patient by Health ID and adds each medicine with dose, frequency and quantity. OpesCare checks the care relationship first and shows a drug-interaction warning when one applies.', 'chip' => 'Prescription issued'],
            ['who' => 'Patient app', 'title' => 'It reaches the patient', 'desc' => 'The patient is notified and sees the prescription in the app. When ready to collect, they ask the app for a code: a short reference and a QR that opens a verification page.', 'chip' => 'Reference + QR'],
            ['who' => 'Patient', 'title' => 'The patient picks a pharmacy', 'desc' => 'The medicine finder shows pharmacies reporting the medicine in stock near them. The patient chooses where to go and can reserve the medicine there.', 'chip' => 'Pharmacy chosen'],
            ['who' => 'Pharmacy', 'title' => 'The counter verifies', 'desc' => 'The pharmacist looks the script up by the patient\'s Health ID or by the prescription reference, or scans the QR. The verification page shows the medicines, the patient name and only the last group of the Health ID.', 'chip' => 'Script verified'],
            ['who' => 'Pharmacy + patient', 'title' => 'Dispensed and recorded', 'desc' => 'The pharmacist hands over the whole prescription or only some lines. The status becomes dispensed or partially dispensed, the patient is told which, and the dispensing is recorded.', 'chip' => 'Full or partial'],
        ],
    ],

    'counter' => [
        'eyebrow' => 'At the pharmacy',
        'title' => 'What happens behind the counter.',
        'sub' => 'The pharmacy side of the same prescription, as the pharmacy desk handles it.',
        'label' => 'The pharmacy counter steps',
        'pause' => 'Pause',
        'play' => 'Play',
        'prev' => 'Previous step',
        'next' => 'Next step',
        'step_of' => 'Step :n of :total',
        'steps' => [
            ['who' => 'Pharmacist', 'title' => 'One search box', 'desc' => 'Type the Health ID from the card or the reference from the patient\'s screen. A Health ID that was merged into another still finds the right patient.', 'chip' => 'Script found'],
            ['who' => 'OpesCare', 'title' => 'Claimed for this counter', 'desc' => 'The first pharmacy to work on a prescription claims it. Another pharmacy can no longer fill the same script.', 'chip' => 'One pharmacy'],
            ['who' => 'Pharmacist', 'title' => 'Prepared', 'desc' => 'The counter accepts the script and marks it ready. The patient is notified that the prescription is ready to collect.', 'chip' => 'Patient notified'],
            ['who' => 'Pharmacist', 'title' => 'Handed over', 'desc' => 'Choose the lines handed over and note the quantity. Anything not handed over stays open on the prescription.', 'chip' => 'Lines recorded'],
            ['who' => 'Record', 'title' => 'Closed out', 'desc' => 'The prescription status, the dispensing register and the patient\'s app all show what was given and by which pharmacy.', 'chip' => 'Status updated'],
        ],
    ],

    'how' => [
        'eyebrow' => 'How OpesCare makes it possible',
        'title' => 'The pieces behind every prescription.',
        'sub' => 'Each of these is part of OpesCare today.',
        'cards' => [
            'desk' => ['title' => 'A prescriber desk', 'desc' => 'Doctors write prescriptions on the web desk or in the clinician app, with dose, frequency, duration and quantity for each medicine.'],
            'gate' => ['title' => 'A care-relationship check', 'desc' => 'A prescription is refused unless the doctor has a care relationship with the patient: an appointment, the care team, the treating clinician, a recent episode at the facility, or a consent grant.'],
            'interact' => ['title' => 'Interaction warnings', 'desc' => 'When two medicines are known to interact, the doctor sees a warning. It is a warning, not a block: what was shown, and whether the doctor acknowledged it, is recorded.'],
            'code' => ['title' => 'A code the patient controls', 'desc' => 'The app issues a reference and a QR only when the patient asks. The patient can withdraw every live code at once, and a voided or expired prescription cannot be shared.'],
            'finder' => ['title' => 'Real stock in the medicine finder', 'desc' => 'The finder shows only stock that pharmacies report themselves. If no pharmacy has reported a medicine, the finder says so instead of guessing.'],
            'lookup' => ['title' => 'Counter lookup by Health ID or reference', 'desc' => 'The pharmacy desk finds open prescriptions from the Health ID on the card or from the prescription reference.'],
            'partial' => ['title' => 'Full or partial dispensing', 'desc' => 'The pharmacist can hand over the whole prescription or only some lines. The patient is told exactly which.'],
            'print' => ['title' => 'A printable copy that verifies', 'desc' => 'A printed prescription carries a QR that opens the same verification page, so paper and phone are checked the same way.'],
        ],
    ],

    'means' => [
        'eyebrow' => 'What it means',
        'title' => 'For patients, doctors and pharmacies.',
        'patients' => 'For patients',
        'facilities' => 'For doctors and pharmacies',
        'p1_title' => 'Your prescriptions in one place',
        'p1_desc' => 'Every prescription you receive is in your app, with its status: active, partially dispensed or dispensed.',
        'p2_title' => 'You choose the pharmacy',
        'p2_desc' => 'Look for the medicine near you, then show your code at the counter you choose.',
        'p3_title' => 'You decide when to share',
        'p3_desc' => 'A code exists only when you ask for one, and you can stop sharing it at any time.',
        'f1_title' => 'Doctors: a readable, traceable script',
        'f1_desc' => 'Typed medicines, interaction warnings, and a record of what was prescribed and why access was allowed.',
        'f2_title' => 'Pharmacies: verify before you dispense',
        'f2_desc' => 'Find the script by Health ID or reference, see exactly what was prescribed, and record what you handed over.',
        'f3_title' => 'Both: one prescription, one pharmacy',
        'f3_desc' => 'Once a pharmacy claims a prescription, no other counter can fill the same script.',
    ],

    'safety' => [
        'eyebrow' => 'Safety',
        'title' => 'Built so one script is filled once, by the right counter.',
        'sub' => 'The protections that apply to every e-prescription.',
        'items' => [
            'verify' => ['title' => 'A verification page for every code', 'desc' => 'The QR opens /verify/rx. It shows what is needed to dispense safely: the medicines, the prescriber, the facility, the patient name and only the last group of the Health ID. No address, no phone, no other prescriptions.'],
            'oracle' => ['title' => 'One answer for every failed check', 'desc' => 'An unknown, expired or withdrawn code all show the same message, so a code cannot be guessed by trying.'],
            'controlled' => ['title' => 'A controlled-medicines register', 'desc' => 'Pharmacies keep a register of controlled medicines in stock, and can export the month\'s register as a secure PDF or a CSV file.'],
            'expiry' => ['title' => 'Prescriptions expire', 'desc' => 'Each prescription has an end date, 30 days by default. After it, or after a void, it can no longer be dispensed or shared.'],
        ],
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Frequently asked questions',
        'categories' => [
            ['items' => [
                ['q' => 'What is an e-prescription?', 'a' => 'It is a prescription created on OpesCare and filed on your Health ID instead of written on paper. It lists each medicine with dose and quantity, the prescriber, the facility and an end date, and it appears in your app.'],
                ['q' => 'Who can write an e-prescription?', 'a' => 'Doctors and medical supervisors at a facility on OpesCare, and only for a patient they have a care relationship with, such as an appointment, or for whom the patient has approved a consent grant.'],
                ['q' => 'How do I collect my medicine?', 'a' => 'Open the prescription in your app and ask for a code. Show the reference or the QR at the pharmacy, or give your Health ID. The pharmacist checks the prescription and hands the medicine over.'],
                ['q' => 'Can I choose my pharmacy?', 'a' => 'Yes. Use the medicine finder to see pharmacies reporting the medicine in stock, and go to the one you choose. Once a pharmacy starts working on the prescription, it stays with that pharmacy.'],
                ['q' => 'What if the pharmacy only has part of my prescription?', 'a' => 'The pharmacist can hand over only some lines. The prescription then shows as partially dispensed in your app, and the rest stays open.'],
                ['q' => 'Can someone else use my code?', 'a' => 'A code is created only when you ask for one, and you can withdraw every live code from the app at any time. A voided or expired prescription cannot be shared or dispensed.'],
                ['q' => 'Is OpesCare available in French?', 'a' => 'Yes. Every page, the app and the facility desks work in English and French.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Get your prescriptions on your Health ID.',
        'sub' => 'Create your Health ID and download the app to receive e-prescriptions and find your medicine nearby.',
        'primary' => 'Get your Health ID',
        'secondary' => 'Download the app',
    ],
];
