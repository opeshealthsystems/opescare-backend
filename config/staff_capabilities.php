<?php

/*
|--------------------------------------------------------------------------
| Staff capabilities — the ONE role → capability map for professional accounts
|--------------------------------------------------------------------------
|
| Every surface (web portal routes and sidebars, the clinician API, the
| clinician app) decides what a professional may see from THIS map, through
| App\Support\StaffCapabilities and the `capability:` middleware. Role names
| are the `roles.name` values seeded by RolesSeeder. A role absent from a
| capability simply does not have it.
|
| Capabilities are coarse on purpose: they gate SURFACES ("may open the
| prescriber desk"), not records. Record-level access is still decided by
| consent grants, clinical relationships and facility context.
*/

$doctors = ['doctor', 'multi_doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor'];
$nurses = ['nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor'];
$trainees = ['student_doctor', 'student_nurse', 'intern'];
$supervisors = ['medical_supervisor'];
$frontDesk = ['receptionist', 'front_desk', 'appointment_coordinator', 'queue_manager', 'records_officer'];
$lab = ['labtech', 'lab_scientist', 'lab_manager', 'lab_validator'];
$labCollection = ['sample_collection'];
$pharmacy = ['pharmacist', 'pharmacy_technician', 'pharmacy_manager', 'dispensing_officer'];
$pharmacyStock = ['medicine_stock'];
$billing = ['cashier', 'billing_officer', 'finance_manager', 'finance', 'refund_approver', 'wallet_ops'];
$facilityAdmins = ['facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo', 'branch_admin'];

$clinical = [...$doctors, ...$nurses, ...$trainees, ...$supervisors];

return [
    'capabilities' => [
        // Identify a patient by exact Health ID or by scanning their code —
        // demographics only. Every professional at a facility needs this.
        'identify_patient' => [...$clinical, ...$frontDesk, ...$lab, ...$labCollection, ...$pharmacy, ...$pharmacyStock],

        // Read a patient's clinical record (chart, vitals, diagnoses, results)
        // — subject to consent / clinical relationship.
        'clinical_chart' => $clinical,

        // Ask a patient for access to their record (consent request).
        'request_access' => [...$clinical, ...$pharmacy],

        // Write, amend or void prescriptions (the prescriber desk).
        'prescribe' => [...$doctors, ...$supervisors],

        // Emergency (break-glass) access to an emergency profile.
        'emergency_access' => [...$doctors, ...$nurses, ...$supervisors],

        // Perform a triage assessment, re-triage a waiting patient, and read
        // the triage queue (the /facility/triage desk).
        //
        // Nurses because triage is a nursing act — `triage_nurse` is a seeded
        // role that until now had no surface of its own. Doctors and medical
        // supervisors because the attending clinician must be able to READ
        // what the nurse recorded and, when the patient in front of them has
        // deteriorated, re-triage. Deliberately NOT the front desk: booking a
        // patient in and assigning them a clinical priority are different
        // jobs, and the second one is not a receptionist's. Deliberately NOT
        // students/interns either — assigning an acuity is an unsupervised
        // clinical decision.
        'triage' => [...$nurses, ...$doctors, ...$supervisors],

        // Run the waiting room: check a patient in, read the live queue,
        // call / start / complete / no-show / cancel a ticket, and read the
        // day's measured wait times (the /facility/queue desk).
        //
        // The front desk because check-in is their act. Nurses, doctors and
        // supervisors because the people who call "next" are the people about
        // to see the patient. Facility administrators because the day's wait
        // figures are the number they answer for. Deliberately NOT lab,
        // pharmacy or billing: their counters have their own desks, and a
        // consultation queue is not theirs to reorder. Deliberately NOT
        // students/interns.
        //
        // Note what this capability does NOT grant: a clinical priority. The
        // desk records an ARRIVAL priority only; the moment a triage record
        // exists for the visit, the triage acuity outranks it in the sort.
        'queue' => [...$frontDesk, ...$nurses, ...$doctors, ...$supervisors, ...$facilityAdmins],

        // Dispense against prescriptions at the pharmacy counter.
        'dispense' => $pharmacy,

        // Pharmacy stock, controlled substances register, stock reports.
        'pharmacy_stock' => [...$pharmacy, ...$pharmacyStock],

        // Enter, validate and acknowledge laboratory results and orders.
        'lab_results' => $lab,

        // Sample collection workflow.
        'sample_collection' => [...$lab, ...$labCollection],

        // Appointment book, check-in, queue, front-desk operations.
        'front_desk' => $frontDesk,

        // See a schedule: clinicians see their own, front desk sees the facility's.
        'schedule' => [...$clinical, ...$frontDesk],

        // Secure messaging with patients and colleagues.
        'messaging' => [...$clinical, ...$frontDesk, ...$lab, ...$labCollection, ...$pharmacy],

        // Blood bank inventory.
        'blood_inventory' => [...$doctors, ...$nurses, ...$supervisors, ...$frontDesk, ...$lab],

        // Billing, payments, invoices.
        'billing' => [...$billing, ...$frontDesk],

        // Facility team management (the /facility/team pages).
        'facility_team' => $facilityAdmins,

        // Curate the facility's own service list — which dictionary entries
        // (tests, procedures, services) this facility actually offers, what it
        // calls them locally and what it charges in whole francs.
        //
        // Deliberately NOT every facility role. This publishes a claim about
        // the institution to patients, so it belongs to the people who answer
        // for the institution: its administrators, plus the lab and pharmacy
        // managers who own their own menus. A dispensing technician or a ward
        // nurse has no business setting the hospital's published prices.
        'service_catalogue' => [...$facilityAdmins, 'lab_manager', 'pharmacy_manager'],
    ],
];
