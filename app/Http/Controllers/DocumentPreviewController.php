<?php

namespace App\Http\Controllers;

class DocumentPreviewController extends Controller
{
    /** GET /document-preview — gallery listing all document types */
    public function index()
    {
        $types = $this->documentTypes();
        return view('documents.preview-gallery', compact('types'));
    }

    /** GET /document-preview/{type} — render a specific document with rich dummy data */
    public function show(string $type)
    {
        $map = [
            // ── Original 15 ──────────────────────────────────────────────────
            'prescription'           => 'documents.prescription',
            'lab-result'             => 'documents.lab_result_report',
            'invoice'                => 'documents.invoice',
            'receipt'                => 'documents.receipt',
            'discharge-summary'      => 'documents.discharge_summary',
            'referral-letter'        => 'documents.referral_letter',
            'medical-certificate'    => 'documents.medical_certificate',
            'radiology-report'       => 'documents.radiology_report',
            'antenatal-card'         => 'documents.antenatal_card',
            'immunization-cert'      => 'documents.immunization_certificate',
            'surgical-report'        => 'documents.surgical_report',
            'consent-form'           => 'documents.consent_form',
            'preauth-letter'         => 'documents.preauthorization_letter',
            'birth-notification'     => 'documents.birth_notification',
            'care-plan'              => 'documents.care_plan_print',
            // ── New 13 ───────────────────────────────────────────────────────
            'narcotic-prescription'  => 'documents.narcotic_prescription',
            'death-certificate'      => 'documents.death_certificate',
            'death-summary'          => 'documents.death_summary',
            'transfer-letter'        => 'documents.transfer_letter',
            'pathology-report'       => 'documents.pathology_report',
            'arv-card'               => 'documents.arv_card',
            'tb-dots-card'           => 'documents.tb_dots_card',
            'psychiatric-assessment' => 'documents.psychiatric_assessment',
            'opd-summary'            => 'documents.opd_summary',
            'insurance-claim'        => 'documents.insurance_claim',
            'fitness-certificate'    => 'documents.fitness_certificate',
            'blood-transfusion'      => 'documents.blood_transfusion',
            'nursing-chart'          => 'documents.nursing_chart',
            // ── Final 22 ─────────────────────────────────────────────────────
            'anaesthesia-record'     => 'documents.anaesthesia_record',
            'lama-form'              => 'documents.lama_form',
            'aer-report'             => 'documents.aer_report',
            'medicolegal-report'     => 'documents.medicolegal_report',
            'autopsy-report'         => 'documents.autopsy_report',
            'partograph'             => 'documents.partograph',
            'newborn-assessment'     => 'documents.newborn_assessment',
            'child-health-card'      => 'documents.child_health_card',
            'dialysis-record'        => 'documents.dialysis_record',
            'chemotherapy-record'    => 'documents.chemotherapy_record',
            'echo-report'            => 'documents.echo_report',
            'endoscopy-report'       => 'documents.endoscopy_report',
            'physio-report'          => 'documents.physio_report',
            'medication-reconciliation' => 'documents.medication_reconciliation',
            'incident-report'        => 'documents.incident_report',
            'wound-care-chart'       => 'documents.wound_care_chart',
            'postnatal-record'       => 'documents.postnatal_record',
            'referral-acknowledgement' => 'documents.referral_acknowledgement',
            'admission-form'         => 'documents.admission_form',
            'pharmacy-record'        => 'documents.pharmacy_record',
            'adr-report'             => 'documents.adr_report',
            'growth-chart'           => 'documents.growth_chart',
            // ── Batch A — Core Inpatient ──────────────────────────────────────
            'medication-administration-record' => 'documents.medication_administration_record',
            'daily-progress-note'    => 'documents.daily_progress_note',
            'surgical-safety-checklist' => 'documents.surgical_safety_checklist',
            'icu-flowsheet'          => 'documents.icu_flowsheet',
            'investigation-request'  => 'documents.investigation_request',
            'nursing-admission-assessment' => 'documents.nursing_admission_assessment',
            // ── Batch B — Legal / Programmatic ───────────────────────────────
            'stillbirth-certificate' => 'documents.stillbirth_certificate',
            'aefi-report'            => 'documents.aefi_report',
            'notifiable-disease-report' => 'documents.notifiable_disease_report',
            'malaria-report'         => 'documents.malaria_report',
            'hiv-counselling-record' => 'documents.hiv_counselling_record',
            'blood-bank-request'     => 'documents.blood_bank_request',
            'postop-recovery-record' => 'documents.postop_recovery_record',
            // ── Batch C — Clinical ───────────────────────────────────────────
            'ecg-report'             => 'documents.ecg_report',
            'fall-risk-assessment'   => 'documents.fall_risk_assessment',
            'pressure-ulcer-assessment' => 'documents.pressure_ulcer_assessment',
            'glucose-log'            => 'documents.glucose_log',
            'handover-note'          => 'documents.handover_note',
            'mental-health-involuntary' => 'documents.mental_health_involuntary',
            // ── Batch D — Specialist ─────────────────────────────────────────
            'dnr-order'              => 'documents.dnr_order',
            'palliative-care-plan'   => 'documents.palliative_care_plan',
            'occupational-therapy'   => 'documents.occupational_therapy',
            'speech-therapy-report'  => 'documents.speech_therapy_report',
            'nutritional-assessment' => 'documents.nutritional_assessment',
            'social-work-assessment' => 'documents.social_work_assessment',
            // ── Batch E — Specialist continued ───────────────────────────────
            'orthopaedic-chart'      => 'documents.orthopaedic_chart',
            'resuscitation-record'   => 'documents.resuscitation_record',
            'nicu-chart'             => 'documents.nicu_chart',
            'patient-complaint'      => 'documents.patient_complaint',
            'procedure-consent'      => 'documents.procedure_consent',
            // ── Batch F — Mortuary ───────────────────────────────────────────
            'mortuary-admission'     => 'documents.mortuary_admission',
            'body-release'           => 'documents.body_release',
            'autopsy-consent'        => 'documents.autopsy_consent',
            'embalming-record'       => 'documents.embalming_record',
            'burial-permit'          => 'documents.burial_permit',
            'clinical-autopsy-report' => 'documents.clinical_autopsy_report',
            'forensic-autopsy-report' => 'documents.forensic_autopsy_report',
            // ── Batch G — Death Review / Mortuary continued ──────────────────
            'maternal-death-review'  => 'documents.maternal_death_review',
            'perinatal-mortality-review' => 'documents.perinatal_mortality_review',
            'coroners-notification'  => 'documents.coroners_notification',
            'verbal-autopsy'         => 'documents.verbal_autopsy',
            'mortuary-storage-log'   => 'documents.mortuary_storage_log',
            'body-identification'    => 'documents.body_identification',
        ];

        if (!array_key_exists($type, $map)) {
            abort(404, 'Document type not found.');
        }

        $data            = $this->base($type);
        $data['payload'] = $this->payload($type);

        return view($map[$type], $data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function base(string $type): array
    {
        $titles = [
            'prescription'           => 'Medical Prescription / Ordonnance Médicale',
            'lab-result'             => 'Laboratory Investigation Report',
            'invoice'                => 'Medical Invoice / Facture Médicale',
            'receipt'                => 'Payment Receipt / Reçu de Paiement',
            'discharge-summary'      => 'Discharge Summary / Résumé de Sortie',
            'referral-letter'        => 'Medical Referral Letter',
            'medical-certificate'    => 'Medical Certificate / Certificat Médical',
            'radiology-report'       => 'Radiology Report / Compte-Rendu Radiologique',
            'antenatal-card'         => 'Antenatal Care Card / Carnet de Suivi Prénatal',
            'immunization-cert'      => 'Immunization Certificate / Certificat de Vaccination',
            'surgical-report'        => 'Operative / Surgical Report',
            'consent-form'           => 'Informed Consent Form',
            'preauth-letter'         => 'Pre-Authorization Letter / Lettre de Pré-Autorisation',
            'birth-notification'     => 'Birth Notification / Déclaration de Naissance',
            'care-plan'              => 'Patient Care Plan',
            'narcotic-prescription'  => 'Controlled Substance Prescription / Ordonnance de Stupéfiant',
            'death-certificate'      => 'Medical Certificate of Death / Certificat Médical de Décès',
            'death-summary'          => 'Clinical Death Summary / Résumé Clinique de Décès',
            'transfer-letter'        => 'Patient Transfer Letter / Fiche de Transfert',
            'pathology-report'       => 'Histopathology Report / Compte-Rendu Anatomopathologique',
            'arv-card'               => 'HIV/ART Treatment Card / Carnet de Traitement ARV',
            'tb-dots-card'           => 'TB Treatment Card — DOTS Programme',
            'psychiatric-assessment' => 'Psychiatric Assessment Report',
            'opd-summary'            => 'Outpatient Consultation Summary / Résumé de Consultation',
            'insurance-claim'        => 'Insurance Claim Form / Formulaire de Demande de Remboursement',
            'fitness-certificate'    => 'Medical Fitness Certificate / Certificat de Bonne Santé',
            'blood-transfusion'      => 'Blood Transfusion Record / Fiche de Transfusion Sanguine',
            'nursing-chart'          => 'Nursing Observation Chart / Feuille de Surveillance',
            'anaesthesia-record'     => 'Anaesthesia Record / Fiche d\'Anesthésie',
            'lama-form'              => 'Leave Against Medical Advice / Départ Contre Avis Médical',
            'aer-report'             => 'Emergency Department Visit Report / Fiche de Passage aux Urgences',
            'medicolegal-report'     => 'Medicolegal Report / Rapport Médico-Légal',
            'autopsy-report'         => 'Post-Mortem Examination Report / Rapport d\'Autopsie',
            'partograph'             => 'Partograph / Partogramme — Labour Chart',
            'newborn-assessment'     => 'Newborn Assessment Sheet / Fiche d\'Examen du Nouveau-Né',
            'child-health-card'      => 'Child Health Card (Under-5) / Carnet de Santé de l\'Enfant',
            'dialysis-record'        => 'Haemodialysis Session Record / Fiche de Dialyse',
            'chemotherapy-record'    => 'Chemotherapy Protocol Record / Fiche de Chimiothérapie',
            'echo-report'            => 'Echocardiography Report / Compte-Rendu Échocardiographique',
            'endoscopy-report'       => 'Endoscopy Report / Compte-Rendu Endoscopique',
            'physio-report'          => 'Physiotherapy Assessment Report / Rapport de Kinésithérapie',
            'medication-reconciliation' => 'Medication Reconciliation Form / Fiche de Réconciliation Médicamenteuse',
            'incident-report'        => 'Clinical Incident Report / Rapport d\'Incident Clinique (INTERNE)',
            'wound-care-chart'       => 'Wound Care Chart / Fiche de Soins de Plaie',
            'postnatal-record'       => 'Postnatal Care Visit Record / Carnet de Suivi Postnatal',
            'referral-acknowledgement' => 'Referral Acknowledgement Letter / Accusé de Réception de Transfert',
            'admission-form'         => 'Patient Admission Form / Fiche d\'Admission du Patient',
            'pharmacy-record'        => 'Drug Dispensing Record / Fiche de Dispensation Pharmaceutique',
            'adr-report'             => 'Adverse Drug Reaction Report / Rapport de Réaction Indésirable',
            'growth-chart'           => 'Paediatric Growth Chart / Courbe de Croissance Pédiatrique',
            // ── Batch A ──────────────────────────────────────────────────────
            'medication-administration-record' => 'Medication Administration Record / Feuille d\'Administration des Médicaments',
            'daily-progress-note'    => 'Daily Progress Note / Note d\'Évolution Quotidienne',
            'surgical-safety-checklist' => 'Surgical Safety Checklist / Liste de Contrôle de Sécurité Chirurgicale',
            'icu-flowsheet'          => 'Intensive Care Unit Flowsheet / Feuille de Surveillance Réanimation',
            'investigation-request'  => 'Investigation Request Form / Demande d\'Examens Complémentaires',
            'nursing-admission-assessment' => 'Nursing Admission Assessment / Évaluation Infirmière à l\'Admission',
            // ── Batch B ──────────────────────────────────────────────────────
            'stillbirth-certificate' => 'Stillbirth Certificate / Certificat de Mort-Né',
            'aefi-report'            => 'Adverse Event Following Immunisation Report / Rapport MAPI',
            'notifiable-disease-report' => 'Notifiable Disease Report / Déclaration de Maladie à Déclaration Obligatoire',
            'malaria-report'         => 'Malaria RDT/Diagnostic Report / Rapport Diagnostic Paludisme',
            'hiv-counselling-record' => 'HIV Pre/Post-Test Counselling Record / Fiche de Conseil VIH',
            'blood-bank-request'     => 'Blood Bank Request Form / Demande de Produit Sanguin',
            'postop-recovery-record' => 'Post-Operative Recovery Record / Fiche de Salle de Réveil',
            // ── Batch C ──────────────────────────────────────────────────────
            'ecg-report'             => 'ECG Report / Compte-Rendu Électrocardiogramme',
            'fall-risk-assessment'   => 'Fall Risk Assessment / Évaluation du Risque de Chute (Morse)',
            'pressure-ulcer-assessment' => 'Pressure Ulcer Risk Assessment / Évaluation Risque d\'Escarre (Braden)',
            'glucose-log'            => 'Blood Glucose Monitoring Log / Journal de Glycémie Capillaire',
            'handover-note'          => 'Clinical Handover Note / Note de Transmission Inter-Équipes',
            'mental-health-involuntary' => 'Involuntary Psychiatric Admission Form / Fiche d\'Hospitalisation Sous Contrainte',
            // ── Batch D ──────────────────────────────────────────────────────
            'dnr-order'              => 'Do Not Resuscitate Order / Ordre de Non-Réanimation',
            'palliative-care-plan'   => 'Palliative Care Plan / Plan de Soins Palliatifs',
            'occupational-therapy'   => 'Occupational Therapy Assessment / Évaluation en Ergothérapie',
            'speech-therapy-report'  => 'Speech & Language Therapy Report / Rapport d\'Orthophonie',
            'nutritional-assessment' => 'Nutritional Assessment / Évaluation Nutritionnelle',
            'social-work-assessment' => 'Social Work Assessment / Évaluation Sociale',
            // ── Batch E ──────────────────────────────────────────────────────
            'orthopaedic-chart'      => 'Orthopaedic Assessment Chart / Fiche d\'Évaluation Orthopédique',
            'resuscitation-record'   => 'Resuscitation Record / Fiche d\'Arrêt Cardio-Respiratoire',
            'nicu-chart'             => 'Neonatal Intensive Care Chart / Fiche de Réanimation Néonatale',
            'patient-complaint'      => 'Patient Complaint Form / Formulaire de Plainte du Patient',
            'procedure-consent'      => 'Procedure-Specific Consent Form / Consentement Éclairé pour Acte Médical',
            // ── Batch F ──────────────────────────────────────────────────────
            'mortuary-admission'     => 'Mortuary Admission Form / Fiche d\'Admission à la Morgue',
            'body-release'           => 'Body Release Form / Formulaire de Remise du Corps',
            'autopsy-consent'        => 'Consent for Post-Mortem Examination / Consentement à l\'Autopsie',
            'embalming-record'       => 'Embalming Record / Fiche d\'Embaumement',
            'burial-permit'          => 'Burial Permit / Permis d\'Inhumation',
            'clinical-autopsy-report' => 'Clinical Autopsy Report / Rapport d\'Autopsie Clinique',
            'forensic-autopsy-report' => 'Forensic Autopsy Report / Rapport d\'Autopsie Médico-Légale',
            // ── Batch G ──────────────────────────────────────────────────────
            'maternal-death-review'  => 'Maternal Death Review / Revue de Décès Maternels',
            'perinatal-mortality-review' => 'Perinatal Mortality Review / Revue de Mortalité Périnatale',
            'coroners-notification'  => 'Coroner\'s Notification Form / Déclaration au Médecin Légiste',
            'verbal-autopsy'         => 'Verbal Autopsy Questionnaire / Autopsie Verbale',
            'mortuary-storage-log'   => 'Mortuary Cold Storage Log / Registre de Stockage à la Morgue',
            'body-identification'    => 'Body Identification Record / Fiche d\'Identification du Corps',
        ];

        $codes = [
            'prescription'           => ['num' => 'OC-RX-2026-088201',  'vcode' => 'X4K2-MBRE-7291'],
            'lab-result'             => ['num' => 'OC-LAB-2026-033847', 'vcode' => 'X7P9-LABS-3841'],
            'invoice'                => ['num' => 'OC-INV-2026-088421', 'vcode' => 'X1N4-FINC-9912'],
            'receipt'                => ['num' => 'OC-REC-2026-055631', 'vcode' => 'X3R8-PYMT-1472'],
            'discharge-summary'      => ['num' => 'OC-DIS-2026-010029', 'vcode' => 'X9D2-DISC-6637'],
            'referral-letter'        => ['num' => 'OC-REF-2026-022184', 'vcode' => 'X2F6-REFL-8823'],
            'medical-certificate'    => ['num' => 'OC-MCD-2026-044712', 'vcode' => 'X5C3-CERT-3340'],
            'radiology-report'       => ['num' => 'OC-RAD-2026-019483', 'vcode' => 'X8A1-RADS-7719'],
            'antenatal-card'         => ['num' => 'OC-ANC-2026-007261', 'vcode' => 'X6M5-ANCR-2284'],
            'immunization-cert'      => ['num' => 'OC-VAX-2026-031004', 'vcode' => 'X1V7-VACC-5510'],
            'surgical-report'        => ['num' => 'OC-SUR-2026-008834', 'vcode' => 'X4S9-SURG-0093'],
            'consent-form'           => ['num' => 'OC-CNS-2026-066201', 'vcode' => 'X7C2-CONS-4421'],
            'preauth-letter'         => ['num' => 'OC-PAL-2026-014399', 'vcode' => 'X3P8-PREA-8862'],
            'birth-notification'     => ['num' => 'OC-BNF-2026-000193', 'vcode' => 'X9B4-BRTH-2237'],
            'care-plan'              => ['num' => 'OC-CPL-2026-019220', 'vcode' => 'X2P6-CARE-9901'],
            'narcotic-prescription'  => ['num' => 'OC-NRX-2026-000441', 'vcode' => 'N8K3-NARC-4401'],
            'death-certificate'      => ['num' => 'OC-DTH-2026-000088', 'vcode' => 'D2M9-DETH-8812'],
            'death-summary'          => ['num' => 'OC-DSU-2026-000089', 'vcode' => 'D4S1-DSUM-3390'],
            'transfer-letter'        => ['num' => 'OC-TRF-2026-003341', 'vcode' => 'T6R2-TRNF-7723'],
            'pathology-report'       => ['num' => 'OC-PATH-2026-008841','vcode' => 'P3A7-PATH-9910'],
            'arv-card'               => ['num' => 'OC-ARV-2026-001204', 'vcode' => 'A5V8-ARVT-2241'],
            'tb-dots-card'           => ['num' => 'OC-DOTS-2026-000312','vcode' => 'T1B4-DOTS-6637'],
            'psychiatric-assessment' => ['num' => 'OC-PSY-2026-002817', 'vcode' => 'P9S3-PSYC-1180'],
            'opd-summary'            => ['num' => 'OC-OPD-2026-041209', 'vcode' => 'O2P6-OPDV-5514'],
            'insurance-claim'        => ['num' => 'OC-CLM-2026-041892', 'vcode' => 'C7L4-CLMF-8821'],
            'fitness-certificate'    => ['num' => 'OC-FIT-2026-009341', 'vcode' => 'F3T1-FITC-2290'],
            'blood-transfusion'      => ['num' => 'OC-BTR-2026-000941', 'vcode' => 'B8R5-BLDT-4473'],
            'nursing-chart'          => ['num' => 'OC-NRS-2026-012204', 'vcode' => 'N4S6-NRSC-7701'],
            'anaesthesia-record'     => ['num' => 'OC-ANS-2026-008834', 'vcode' => 'A3N1-ANES-0041'],
            'lama-form'              => ['num' => 'OC-LAM-2026-000312', 'vcode' => 'L5A2-LAMA-9901'],
            'aer-report'             => ['num' => 'OC-AER-2026-041209', 'vcode' => 'E7R4-AERV-2284'],
            'medicolegal-report'     => ['num' => 'OC-MLR-2026-000441', 'vcode' => 'M1L6-MEDL-5510'],
            'autopsy-report'         => ['num' => 'OC-PMR-2026-000089', 'vcode' => 'P8M3-AUTO-8812'],
            'partograph'             => ['num' => 'OC-PTG-2026-000193', 'vcode' => 'P2T7-PART-3390'],
            'newborn-assessment'     => ['num' => 'OC-NBA-2026-000194', 'vcode' => 'N9B1-NWBN-7723'],
            'child-health-card'      => ['num' => 'OC-CHC-2026-009341', 'vcode' => 'C4H8-CHLD-9910'],
            'dialysis-record'        => ['num' => 'OC-DLY-2026-004700', 'vcode' => 'D6L5-DIAL-2241'],
            'chemotherapy-record'    => ['num' => 'OC-CTX-2026-002817', 'vcode' => 'C3T9-CHMO-6637'],
            'echo-report'            => ['num' => 'OC-ECH-2026-019483', 'vcode' => 'E2C4-ECHO-1180'],
            'endoscopy-report'       => ['num' => 'OC-END-2026-008201', 'vcode' => 'E5D7-ENDO-5514'],
            'physio-report'          => ['num' => 'OC-PHY-2026-003341', 'vcode' => 'P1H2-PHYS-8821'],
            'medication-reconciliation' => ['num' => 'OC-MRC-2026-010029','vcode' => 'M7R6-MEDR-2290'],
            'incident-report'        => ['num' => 'OC-INC-2026-000088', 'vcode' => 'I4N1-INCR-4473'],
            'wound-care-chart'       => ['num' => 'OC-WND-2026-007261', 'vcode' => 'W8N5-WNDV-7701'],
            'postnatal-record'       => ['num' => 'OC-PNC-2026-000312', 'vcode' => 'P3N4-PNTR-0041'],
            'referral-acknowledgement' => ['num' => 'OC-RAL-2026-022185','vcode' => 'R6A2-RALA-9901'],
            'admission-form'         => ['num' => 'OC-ADM-2026-041892', 'vcode' => 'A7D4-ADMN-2284'],
            'pharmacy-record'        => ['num' => 'OC-DPR-2026-088202', 'vcode' => 'D5P1-PHRM-5510'],
            'adr-report'             => ['num' => 'OC-ADR-2026-000041', 'vcode' => 'A9D8-ADRP-8812'],
            'growth-chart'           => ['num' => 'OC-GCH-2026-009342', 'vcode' => 'G2C7-GRWT-3390'],
            // ── Batch A ──────────────────────────────────────────────────────
            'medication-administration-record' => ['num' => 'OC-MAR-2026-088301', 'vcode' => 'M3A1-MARX-4401'],
            'daily-progress-note'    => ['num' => 'OC-PRG-2026-041310', 'vcode' => 'D7P2-PROG-8812'],
            'surgical-safety-checklist' => ['num' => 'OC-SSC-2026-008935', 'vcode' => 'S1S4-SSCH-3390'],
            'icu-flowsheet'          => ['num' => 'OC-ICU-2026-004700', 'vcode' => 'I6C3-ICUF-7723'],
            'investigation-request'  => ['num' => 'OC-REQ-2026-088202', 'vcode' => 'R2E5-REQF-9910'],
            'nursing-admission-assessment' => ['num' => 'OC-NAA-2026-041893', 'vcode' => 'N8A4-NAAS-2241'],
            // ── Batch B ──────────────────────────────────────────────────────
            'stillbirth-certificate' => ['num' => 'OC-SBC-2026-000090', 'vcode' => 'S4B7-STLB-6637'],
            'aefi-report'            => ['num' => 'OC-AEF-2026-000042', 'vcode' => 'A6E1-AEFI-1180'],
            'notifiable-disease-report' => ['num' => 'OC-NDR-2026-000312', 'vcode' => 'N5D8-NDIS-5514'],
            'malaria-report'         => ['num' => 'OC-MAL-2026-019220', 'vcode' => 'M9L3-MALA-8821'],
            'hiv-counselling-record' => ['num' => 'OC-HCR-2026-001205', 'vcode' => 'H4I2-HIVC-2290'],
            'blood-bank-request'     => ['num' => 'OC-BBR-2026-000942', 'vcode' => 'B1B6-BLDB-4473'],
            'postop-recovery-record' => ['num' => 'OC-POR-2026-008835', 'vcode' => 'P7O4-POPR-7701'],
            // ── Batch C ──────────────────────────────────────────────────────
            'ecg-report'             => ['num' => 'OC-ECG-2026-019484', 'vcode' => 'E3C9-ECGR-0041'],
            'fall-risk-assessment'   => ['num' => 'OC-FRA-2026-041210', 'vcode' => 'F8A2-FALL-9901'],
            'pressure-ulcer-assessment' => ['num' => 'OC-PUA-2026-041211', 'vcode' => 'P4U7-ULCR-2284'],
            'glucose-log'            => ['num' => 'OC-DGL-2026-012205', 'vcode' => 'G6L1-GLUC-5510'],
            'handover-note'          => ['num' => 'OC-HOV-2026-041212', 'vcode' => 'H3V5-HVNR-8812'],
            'mental-health-involuntary' => ['num' => 'OC-MHI-2026-000089', 'vcode' => 'M2H8-MHIN-3390'],
            // ── Batch D ──────────────────────────────────────────────────────
            'dnr-order'              => ['num' => 'OC-DNR-2026-000090', 'vcode' => 'D5N3-DNRO-7723'],
            'palliative-care-plan'   => ['num' => 'OC-PAL-2026-000313', 'vcode' => 'P9A6-PALL-9910'],
            'occupational-therapy'   => ['num' => 'OC-OTA-2026-003342', 'vcode' => 'O1T4-OCCT-2241'],
            'speech-therapy-report'  => ['num' => 'OC-SLT-2026-003343', 'vcode' => 'S7L2-SLTR-6637'],
            'nutritional-assessment' => ['num' => 'OC-NTR-2026-019221', 'vcode' => 'N3U9-NUTR-1180'],
            'social-work-assessment' => ['num' => 'OC-SWA-2026-041213', 'vcode' => 'S8W1-SOCW-5514'],
            // ── Batch E ──────────────────────────────────────────────────────
            'orthopaedic-chart'      => ['num' => 'OC-ORT-2026-008836', 'vcode' => 'O5R7-ORTH-8821'],
            'resuscitation-record'   => ['num' => 'OC-CPR-2026-000091', 'vcode' => 'R4S3-RESU-2290'],
            'nicu-chart'             => ['num' => 'OC-NIC-2026-000195', 'vcode' => 'N6I8-NICU-4473'],
            'patient-complaint'      => ['num' => 'OC-PCF-2026-000043', 'vcode' => 'P2C5-COMP-7701'],
            'procedure-consent'      => ['num' => 'OC-PCS-2026-066202', 'vcode' => 'P1C9-PRCS-0041'],
            // ── Batch F ──────────────────────────────────────────────────────
            'mortuary-admission'     => ['num' => 'OC-BRF-2026-000092', 'vcode' => 'B7R4-MORT-9901'],
            'body-release'           => ['num' => 'OC-BRL-2026-000093', 'vcode' => 'B3L6-BREL-2284'],
            'autopsy-consent'        => ['num' => 'OC-PMC-2026-000094', 'vcode' => 'A9P2-PMCN-5510'],
            'embalming-record'       => ['num' => 'OC-EMB-2026-000095', 'vcode' => 'E5M7-EMBL-8812'],
            'burial-permit'          => ['num' => 'OC-BPN-2026-000096', 'vcode' => 'B4N1-BURP-3390'],
            'clinical-autopsy-report' => ['num' => 'OC-CAR-2026-000097', 'vcode' => 'C8A5-CATR-7723'],
            'forensic-autopsy-report' => ['num' => 'OC-FAR-2026-000098', 'vcode' => 'F2A9-FATR-9910'],
            // ── Batch G ──────────────────────────────────────────────────────
            'maternal-death-review'  => ['num' => 'OC-MDR-2026-000099', 'vcode' => 'M6D3-MDVR-2241'],
            'perinatal-mortality-review' => ['num' => 'OC-PMV-2026-000100', 'vcode' => 'P3V7-PMVR-6637'],
            'coroners-notification'  => ['num' => 'OC-CMN-2026-000101', 'vcode' => 'C7M2-CORN-1180'],
            'verbal-autopsy'         => ['num' => 'OC-VBA-2026-000102', 'vcode' => 'V1B8-VBAP-5514'],
            'mortuary-storage-log'   => ['num' => 'OC-MSL-2026-000103', 'vcode' => 'M4S4-MSTL-8821'],
            'body-identification'    => ['num' => 'OC-BIR-2026-000104', 'vcode' => 'B9I6-BIDR-2290'],
        ];

        $c = $codes[$type];

        return [
            'title'             => $titles[$type],
            'language'          => 'en',
            'status'            => 'issued',
            'version'           => '1.0',
            'facility_name'     => 'OpesCare Central General Hospital',
            'facility_license'  => 'MIN-SAN-2019-00847',
            'patient_name'      => 'NJOMO EKAMBI, Marie Claire',
            'health_id'         => 'CMR-2024-00429871',
            'patient_sex'       => 'Female',
            'patient_dob'       => '12 March 1985',
            'document_number'   => $c['num'],
            'issued_at'         => '07 June 2026, 14:32 WAT',
            'issuer_name'       => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
            'issuer_role'       => 'Attending Physician',
            'verification_code' => $c['vcode'],
            'qr_svg'            => $this->dummyQrSvg(),
        ];
    }

    private function payload(string $type): array
    {
        switch ($type) {
            case 'prescription':
                return [
                    'clinical_indication' => 'Hypertensive emergency — Stage 2 hypertension with target organ damage',
                    'validity_period'     => '30 Days / Jours',
                    'allergy_warnings'    => 'Penicillin allergy (anaphylaxis) documented. NSAIDs — relative contraindication.',
                    'medications'         => [
                        [
                            'name' => 'Amlodipine', 'generic_name' => 'Amlodipine Besylate', 'strength' => '10mg',
                            'form' => 'Tablet', 'dose' => '10mg', 'route' => 'Oral',
                            'frequency' => 'Once daily (morning)', 'duration' => '90 days', 'quantity' => '90 tabs',
                            'instructions' => 'Take with or without food. Do not crush.', 'substitution_allowed' => true,
                        ],
                        [
                            'name' => 'Losartan', 'generic_name' => 'Losartan Potassium', 'strength' => '50mg',
                            'form' => 'Tablet', 'dose' => '50mg', 'route' => 'Oral',
                            'frequency' => 'Once daily (evening)', 'duration' => '90 days', 'quantity' => '90 tabs',
                            'instructions' => 'Monitor serum potassium. Avoid potassium supplements.', 'substitution_allowed' => false,
                        ],
                        [
                            'name' => 'Hydrochlorothiazide', 'generic_name' => 'Hydrochlorothiazide', 'strength' => '25mg',
                            'form' => 'Tablet', 'dose' => '12.5mg', 'route' => 'Oral',
                            'frequency' => 'Once daily (morning)', 'duration' => '90 days', 'quantity' => '90 tabs',
                            'instructions' => 'Take with food. Monitor electrolytes.', 'substitution_allowed' => true,
                        ],
                    ],
                ];

            case 'lab-result':
                return [
                    'specimen_type'       => 'Venous Blood',
                    'collection_date'     => '07 June 2026, 08:15',
                    'report_date'         => '07 June 2026, 11:42',
                    'lab_name'            => 'OpesCare Central Laboratory',
                    'lab_accreditation'   => 'ISO 15189:2022',
                    'clinical_indication' => 'Routine metabolic panel — hypertensive emergency workup',
                    'results'             => [
                        ['test' => 'HbA1c',           'loinc' => '4548-4',  'value' => '8.2',  'unit' => '%',             'reference' => '< 5.7%',          'flag' => 'H', 'critical' => true,  'interpretation' => 'Poorly controlled diabetes — intensification required'],
                        ['test' => 'Serum Creatinine', 'loinc' => '2160-0',  'value' => '1.42', 'unit' => 'mg/dL',         'reference' => '0.6–1.1 mg/dL',   'flag' => 'H', 'critical' => false, 'interpretation' => 'Mild renal impairment — eGFR 54 mL/min/1.73m²'],
                        ['test' => 'Serum Potassium',  'loinc' => '2823-3',  'value' => '3.8',  'unit' => 'mmol/L',        'reference' => '3.5–5.0 mmol/L',  'flag' => 'N', 'critical' => false, 'interpretation' => 'Normal'],
                        ['test' => 'LDL Cholesterol',  'loinc' => '2089-1',  'value' => '4.1',  'unit' => 'mmol/L',        'reference' => '< 2.6 mmol/L',    'flag' => 'H', 'critical' => false, 'interpretation' => 'Elevated — statin therapy indicated'],
                        ['test' => 'eGFR',             'loinc' => '62238-1', 'value' => '54',   'unit' => 'mL/min/1.73m²', 'reference' => '> 60',            'flag' => 'L', 'critical' => false, 'interpretation' => 'CKD Stage 3a'],
                    ],
                ];

            case 'invoice':
                return [
                    'invoice_type'         => 'Outpatient Consultation + Investigations',
                    'encounter_date'       => '07 June 2026',
                    'insurance_provider'   => 'CNPS Health Insurance',
                    'insurance_policy_number' => 'CNPS-2024-00987-B',
                    'payment_terms'        => 'Due Upon Receipt',
                    'items'                => [
                        ['description' => 'Emergency Consultation — Internal Medicine', 'service_code' => 'CONS-EM-001', 'quantity' => 1, 'unit_price' => 15000, 'insurance_covered' => 15000, 'patient_responsibility' => 0,     'total_amount' => 15000],
                        ['description' => 'CBC with differential',                      'service_code' => 'LAB-CBC-001', 'quantity' => 1, 'unit_price' => 8500,  'insurance_covered' => 8500,  'patient_responsibility' => 0,     'total_amount' => 8500],
                        ['description' => 'HbA1c (HPLC method)',                        'service_code' => 'LAB-HBA-001', 'quantity' => 1, 'unit_price' => 12000, 'insurance_covered' => 12000, 'patient_responsibility' => 0,     'total_amount' => 12000],
                        ['description' => 'Lipid Profile (Complete)',                   'service_code' => 'LAB-LIP-001', 'quantity' => 1, 'unit_price' => 9500,  'insurance_covered' => 9500,  'patient_responsibility' => 0,     'total_amount' => 9500],
                        ['description' => 'Chest X-Ray (PA + Lateral)',                 'service_code' => 'RAD-CXR-001', 'quantity' => 1, 'unit_price' => 18000, 'insurance_covered' => 0,     'patient_responsibility' => 18000, 'total_amount' => 18000],
                        ['description' => 'ECG (12-lead)',                              'service_code' => 'CARD-ECG-001','quantity' => 1, 'unit_price' => 7500,  'insurance_covered' => 0,     'patient_responsibility' => 7500,  'total_amount' => 7500],
                    ],
                    'subtotal'             => 70500,
                    'insurance_total'      => 45000,
                    'patient_total'        => 25500,
                    'payment_status'       => 'pending',
                ];

            case 'receipt':
                return [
                    'receipt_for'      => 'Payment for Outpatient Services — Invoice OC-INV-2026-088421',
                    'payment_date'     => '07 June 2026, 15:18 WAT',
                    'payment_method'   => 'MTN Mobile Money',
                    'mtn_reference'    => 'MTN2026060700847291',
                    'amount_paid'      => 25500,
                    'currency'         => 'XAF',
                    'amount_words'     => 'TWENTY-FIVE THOUSAND FIVE HUNDRED FRANCS CFA',
                    'invoice_number'   => 'OC-INV-2026-088421',
                    'cashier_name'     => 'BELLO HAMIDOU, Fatimatou',
                    'cashier_id'       => 'STAFF-0042',
                    'items_paid'       => [
                        ['description' => 'Chest X-Ray (PA + Lateral)', 'amount' => 18000],
                        ['description' => 'ECG (12-lead)',               'amount' => 7500],
                    ],
                    'change_given'     => 0,
                    'balance_due'      => 0,
                    'payment_complete' => true,
                ];

            case 'discharge-summary':
                return [
                    'admission_date'         => '05 June 2026, 22:47',
                    'discharge_date'         => '07 June 2026, 16:00',
                    'length_of_stay'         => '1 day 17 hours',
                    'ward'                   => 'Internal Medicine — Ward 3B',
                    'bed_number'             => 'BED-3B-12',
                    'admission_diagnosis'    => 'Hypertensive Emergency with hypertensive nephropathy',
                    'final_diagnoses'        => [
                        'Primary: Stage 2 Hypertension — Hypertensive Emergency (BP 210/130 mmHg on admission)',
                        'Secondary: Type 2 Diabetes Mellitus — poorly controlled (HbA1c 8.2%)',
                        'Tertiary: Chronic Kidney Disease Stage 3a (eGFR 54 mL/min/1.73m²)',
                        'Quaternary: Dyslipidaemia — LDL 4.1 mmol/L',
                    ],
                    'procedures_performed'   => [
                        'IV Labetalol infusion — BP reduction protocol',
                        'Continuous cardiac monitoring × 24h',
                        '12-lead ECG (no ischaemic changes)',
                        'Fundoscopy — Grade II hypertensive retinopathy',
                        'Renal ultrasound — bilateral echogenic kidneys, no obstruction',
                    ],
                    'discharge_medications'  => [
                        ['drug' => 'Amlodipine 10mg',            'frequency' => 'Once daily',         'duration' => 'Ongoing'],
                        ['drug' => 'Losartan 50mg',              'frequency' => 'Once daily',         'duration' => 'Ongoing'],
                        ['drug' => 'Hydrochlorothiazide 12.5mg', 'frequency' => 'Once daily',         'duration' => 'Ongoing'],
                        ['drug' => 'Atorvastatin 40mg',          'frequency' => 'Once daily (night)', 'duration' => 'Ongoing'],
                    ],
                    'follow_up'              => [
                        ['specialist' => 'Internal Medicine Clinic', 'when' => '14 June 2026 (1 week)'],
                        ['specialist' => 'Nephrology',               'when' => '28 June 2026 (3 weeks)'],
                        ['specialist' => 'Ophthalmology',            'when' => '21 June 2026 (2 weeks)'],
                    ],
                    'red_flags'              => [
                        'Severe headache or visual changes',
                        'BP > 180/110 mmHg at home',
                        'Decreased urine output',
                        'Chest pain or shortness of breath',
                    ],
                    'diet_instructions'      => 'Low sodium diet (< 2g Na/day), low glycaemic index foods, fluid restriction 1.5L/day',
                    'condition_at_discharge' => 'STABLE — BP 138/88 mmHg on discharge',
                ];

            case 'referral-letter':
                return [
                    'referral_type'           => 'external',
                    'referral_urgency'        => 'urgent',
                    'to_specialist'           => 'Prof. TABI NDIP, Charles Etienne — Nephrology & Hypertension',
                    'to_facility'             => 'OpesCare University Teaching Hospital — Nephrology Department',
                    'provisional_diagnosis'   => 'CKD Stage 3a — Hypertensive Nephropathy',
                    'reason_for_referral'     => 'Specialist nephrology assessment for CKD Stage 3a in the context of newly diagnosed hypertensive nephropathy. Patient requires formal GFR staging, kidney biopsy consideration, and initiation of renoprotective therapy.',
                    'clinical_summary'        => 'Ms. NJOMO EKAMBI is a 41-year-old female admitted with hypertensive emergency (BP 210/130 mmHg). Workup revealed CKD Stage 3a (eGFR 54 mL/min/1.73m²), poorly controlled T2DM (HbA1c 8.2%), and bilateral echogenic kidneys on ultrasound. She has been stabilised on triple antihypertensive therapy.',
                    'relevant_investigations' => [
                        ['test' => 'Serum Creatinine',  'result' => '1.42 mg/dL (↑)',             'date' => '07 Jun 2026'],
                        ['test' => 'eGFR (CKD-EPI)',    'result' => '54 mL/min/1.73m² — Stage 3a','date' => '07 Jun 2026'],
                        ['test' => 'Renal Ultrasound',  'result' => 'Bilateral echogenic kidneys, no obstruction', 'date' => '07 Jun 2026'],
                        ['test' => 'Urinalysis',        'result' => '2+ proteinuria',              'date' => '07 Jun 2026'],
                    ],
                    'current_medications'     => ['Amlodipine 10mg OD', 'Losartan 50mg OD', 'Hydrochlorothiazide 12.5mg OD', 'Atorvastatin 40mg OD'],
                    'specific_request'        => 'Please assess for underlying CKD aetiology, advise on ACE inhibitor/ARB dose optimisation, formal GFR staging, and consider renal biopsy if clinically indicated.',
                    'referring_doctor'        => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'referring_contact'       => 'Tel: +237 600-000-000 | OpesCare Central General Hospital, Bonanjo, Douala',
                ];

            case 'medical-certificate':
                return [
                    'certificate_purpose'  => 'Medical Incapacity Certificate — Sick Leave',
                    'examination_date'     => '07 June 2026',
                    'examination_findings' => 'Patient examined and found unfit for work due to hypertensive emergency requiring inpatient stabilisation, ongoing close monitoring, and antihypertensive medication titration.',
                    'diagnosis'            => 'Hypertensive Emergency (ICD-10: I16.0) with Hypertensive Nephropathy (ICD-10: I12.9)',
                    'incapacity_start'     => '05 June 2026',
                    'incapacity_end'       => '19 June 2026',
                    'duration_days'        => 14,
                    'rest_type'            => 'Complete rest at home',
                    'restrictions'         => ['No strenuous physical activity', 'Strict sodium-restricted diet', 'Daily BP monitoring mandatory', 'Avoid driving until BP controlled'],
                    'intended_recipient'   => 'Employer / Human Resources Department',
                    'return_to_work_note'  => 'Return to work subject to follow-up review on 19 June 2026 with Internal Medicine Clinic.',
                    'stamp_required'       => true,
                ];

            case 'radiology-report':
                return [
                    'modality'             => 'Chest X-Ray (Radiographie thoracique)',
                    'views'                => 'Posteroanterior (PA) + Left Lateral',
                    'study_date'           => '07 June 2026, 09:35',
                    'report_date'          => '07 June 2026, 12:10',
                    'accession_number'     => 'RAD-2026-00041832',
                    'requesting_physician' => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'radiologist'          => 'Dr. ONDOA MANGA, Bernadette Alice',
                    'radiologist_reg'      => 'CMR-RAD-0091',
                    'clinical_indication'  => 'Hypertensive emergency — assess for pulmonary oedema and cardiomegaly',
                    'technique'            => 'Standard PA and lateral chest views obtained at full inspiration. Digital acquisition. No contrast used.',
                    'findings'             => [
                        'Cardiac silhouette mildly enlarged — cardiothoracic ratio approximately 0.54 (upper limit of normal).',
                        'Bilateral perihilar haziness consistent with early pulmonary venous hypertension. No frank alveolar oedema.',
                        'Mild bilateral pleural blunting — trace pleural effusions bilaterally, right > left.',
                        'Mediastinum is not widened. Trachea is midline.',
                        'No pneumothorax. No consolidation or mass lesion identified.',
                        'Bony thorax: no rib fractures or lytic lesions noted.',
                        'Aortic knuckle is prominent — aortic unfolding consistent with age and longstanding hypertension.',
                    ],
                    'impression'           => 'Mild cardiomegaly with features of pulmonary venous hypertension and trace bilateral pleural effusions, consistent with hypertensive heart disease. No pneumonia or pneumothorax. Recommend echocardiography to assess LV function and ejection fraction.',
                    'recommendation'       => 'Echocardiogram within 1 week. Repeat CXR after diuresis if clinically indicated.',
                    'urgency_flag'         => false,
                    'critical_finding'     => false,
                ];

            case 'antenatal-card':
                return [
                    'gravida'              => 'G3P2',
                    'lmp'                  => '01 January 2026',
                    'edd'                  => '08 October 2026',
                    'gestational_age'      => '22 weeks + 2 days',
                    'blood_group'          => 'B Positive (B+)',
                    'rhesus'               => 'Positive',
                    'hiv_status'           => 'Negative (tested 07 June 2026)',
                    'syphilis_status'      => 'Non-reactive (tested 07 June 2026)',
                    'hepatitis_b'          => 'HBsAg Negative',
                    'height_cm'            => 163,
                    'pre_pregnancy_weight' => '62 kg',
                    'risk_factors'         => ['Previous caesarean section (×1)', 'Gestational hypertension in prior pregnancy', 'Moderate anaemia (Hb 9.8 g/dL)'],
                    'visits'               => [
                        ['date' => '10 Feb 2026', 'ga' => '8+3',  'bp' => '110/70', 'weight' => '63.5 kg', 'urine' => 'Normal',       'fetal_heart' => 'N/A',    'notes' => 'Booking visit. Folic acid + iron started.'],
                        ['date' => '15 Mar 2026', 'ga' => '14+0', 'bp' => '112/72', 'weight' => '65.0 kg', 'urine' => 'Trace protein', 'fetal_heart' => '148 bpm','notes' => 'Anomaly scan booked.'],
                        ['date' => '25 Apr 2026', 'ga' => '18+1', 'bp' => '118/74', 'weight' => '67.2 kg', 'urine' => 'Normal',       'fetal_heart' => '152 bpm','notes' => '18-week anomaly scan — no structural defects.'],
                        ['date' => '07 Jun 2026', 'ga' => '22+2', 'bp' => '124/80', 'weight' => '69.4 kg', 'urine' => '1+ protein',   'fetal_heart' => '156 bpm','notes' => 'BP trending up. 24h urine protein ordered.'],
                    ],
                    'next_visit'           => '21 June 2026',
                    'immunizations_given'  => ['Tetanus Toxoid — TT2 (28 April 2026)'],
                    'supplements'          => ['Ferrous sulfate 200mg BD', 'Folic acid 5mg OD', 'Calcium 1g OD'],
                ];

            case 'immunization-cert':
                return [
                    'certificate_scope'   => 'Routine Adult Immunization Record',
                    'nationality'         => 'Cameroonian',
                    'passport_number'     => 'CM-P-20198834',
                    'vaccinations'        => [
                        ['vaccine' => 'Yellow Fever',         'brand' => 'Stamaril',        'dose' => '1 of 1',  'date' => '12 Jan 2020',  'batch' => 'STM-2019-4412', 'site' => 'Left arm',  'valid_until' => 'Lifetime', 'administrator' => 'OpesCare Vaccination Centre'],
                        ['vaccine' => 'COVID-19 (mRNA)',      'brand' => 'Pfizer-BioNTech', 'dose' => '1 of 2',  'date' => '14 Mar 2022',  'batch' => 'PFZ-CMR-0031', 'site' => 'Left arm',  'valid_until' => '—',        'administrator' => 'OpesCare Central Hospital'],
                        ['vaccine' => 'COVID-19 (mRNA)',      'brand' => 'Pfizer-BioNTech', 'dose' => '2 of 2',  'date' => '05 Apr 2022',  'batch' => 'PFZ-CMR-0044', 'site' => 'Left arm',  'valid_until' => '—',        'administrator' => 'OpesCare Central Hospital'],
                        ['vaccine' => 'Influenza (Seasonal)', 'brand' => 'Vaxigrip Tetra', 'dose' => 'Annual',  'date' => '10 Oct 2025',  'batch' => 'VXT-2025-0298', 'site' => 'Right arm', 'valid_until' => 'Oct 2026', 'administrator' => 'OpesCare Outpatient Clinic'],
                        ['vaccine' => 'Hepatitis B',          'brand' => 'Engerix-B',       'dose' => '3 of 3',  'date' => '20 Mar 2024',  'batch' => 'ENB-2024-0175', 'site' => 'Left arm',  'valid_until' => 'Lifetime', 'administrator' => 'OpesCare Central Hospital'],
                    ],
                    'due_vaccines'        => ['Influenza (Seasonal) — due October 2026', 'COVID-19 Booster — consider annual update'],
                    'issuing_authority'   => 'OpesCare Immunization Registry — MINSANTE Approved',
                ];

            case 'surgical-report':
                return [
                    'procedure_name'       => 'Laparoscopic Appendicectomy',
                    'icd_procedure_code'   => '47.01',
                    'operation_date'       => '06 June 2026',
                    'operation_start'      => '10:15',
                    'operation_end'        => '11:42',
                    'duration_minutes'     => 87,
                    'theatre'              => 'Theatre 2 — OpesCare Surgical Suite',
                    'anaesthesia_type'     => 'General Anaesthesia — LMA',
                    'anaesthetist'         => 'Dr. FONKOU NJIKE, Blaise Albert',
                    'surgeon'              => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'assistant'            => 'Dr. ABENA MFOUNDI, Solange',
                    'scrub_nurse'          => 'Sr. NGONO BIYA, Celestine',
                    'circulating_nurse'    => 'Sr. EYINGA MBIDA, Jeanne',
                    'pre_op_diagnosis'     => 'Acute appendicitis — perforated',
                    'post_op_diagnosis'    => 'Gangrenous perforated appendicitis with localised peritonitis',
                    'procedure_details'    => 'Patient placed supine in reverse Trendelenburg. Pneumoperitoneum established to 12 mmHg. Three ports placed — umbilical 10mm, RIF 5mm, suprapubic 5mm. Appendix identified — gangrenous, perforated at base. Mesoappendix divided with LigaSure. Appendix base secured with Endoloop ×3 and divided. Appendix retrieved via umbilical port in retrieval bag. Peritoneal lavage with 2L warm saline. Haemostasis confirmed. Ports removed under direct vision. Fascial defects closed. Skin stapled.',
                    'findings'             => ['Gangrenous perforated appendix — perforation at base', 'Localised purulent peritonitis in right iliac fossa', 'No free perforation or generalised peritonitis', 'Surrounding bowel and omentum edematous but viable'],
                    'estimated_blood_loss' => '< 50 mL',
                    'specimens'            => ['Appendix (gangrenous) — sent for histopathology'],
                    'drains'               => ['Robinson drain — right iliac fossa'],
                    'post_op_instructions' => ['NPO until bowel sounds return (expected 24–48h)', 'IV Cefuroxime + Metronidazole × 72h then switch to oral', 'Remove drain when output < 30 mL/24h', 'Staples out day 7–10', 'Review in surgical clinic 2 weeks post-op'],
                    'complications'        => 'None intraoperatively',
                ];

            case 'consent-form':
                return [
                    'procedure_name'        => 'Laparoscopic Appendicectomy',
                    'consent_type'          => 'Informed Surgical Consent',
                    'consent_date'          => '06 June 2026, 08:30',
                    'procedure_description' => 'Minimally invasive surgical removal of the appendix using laparoscopic (keyhole) technique under general anaesthesia.',
                    'indication'            => 'Acute appendicitis — risk of perforation and generalised peritonitis if not treated surgically.',
                    'alternatives'          => ['Conservative antibiotic therapy (lower success rate for perforated appendicitis)', 'Open appendicectomy (if laparoscopic approach not feasible)', 'No treatment — carries risk of rupture, sepsis, and death'],
                    'risks_common'          => ['Pain and discomfort post-operatively', 'Wound infection at port sites (5–10%)', 'Nausea and vomiting from anaesthesia', 'Short hospital stay 1–2 days'],
                    'risks_serious'         => ['Bleeding requiring blood transfusion (< 1%)', 'Injury to bowel, bladder, or blood vessels (< 1%)', 'Conversion to open surgery if laparoscopic approach unsafe (5%)', 'Deep vein thrombosis / pulmonary embolism (rare)', 'Anaesthetic complications (rare)'],
                    'patient_statement'     => 'I confirm that I have been explained the procedure, its risks, benefits, and alternatives in a language I understand. I have had the opportunity to ask questions. I give my voluntary consent to proceed.',
                    'interpreter_required'  => false,
                    'witness_name'          => 'ABENA MFOUNDI, Solange (Medical Student)',
                    'patient_signed_at'     => '06 June 2026, 08:47',
                    'physician_signed_at'   => '06 June 2026, 08:49',
                    'legal_basis'           => 'Cameroon Law No. 2003/004 — Patients Rights and Healthcare Access',
                ];

            case 'preauth-letter':
                return [
                    'preauth_number'       => 'PA-CNPS-2026-008814',
                    'request_date'         => '07 June 2026',
                    'decision_date'        => '07 June 2026',
                    'decision'             => 'APPROVED',
                    'valid_from'           => '07 June 2026',
                    'valid_until'          => '21 June 2026',
                    'insurer'              => 'CNPS Health Insurance (Caisse Nationale de Prévoyance Sociale)',
                    'insurer_contact'      => 'Tel: +237 222 222 100 | preauth@cnps.cm',
                    'insurer_ref'          => 'CNPS-PREAUTH-2026-008814',
                    'policy_holder'        => 'NJOMO EKAMBI, Marie Claire',
                    'policy_number'        => 'CNPS-2024-00987-B',
                    'employer'             => 'Ministère de la Santé Publique du Cameroun',
                    'approved_services'    => [
                        ['service' => 'Emergency Consultation — Internal Medicine',   'code' => 'CONS-EM-001', 'approved_amount' => 15000, 'limit' => 'Per visit'],
                        ['service' => 'Full Metabolic Panel (CBC + HbA1c + Lipids)',  'code' => 'LAB-MET-012', 'approved_amount' => 30000, 'limit' => 'Per episode'],
                        ['service' => 'Inpatient Admission — Internal Medicine',      'code' => 'IPD-INT-003', 'approved_amount' => 45000, 'limit' => 'Per day × 3 days'],
                    ],
                    'excluded_services'    => ['Chest X-Ray (covered only if admitted > 48h)', 'ECG (patient responsibility)'],
                    'total_approved'       => 90000,
                    'patient_copay_percent'=> 20,
                    'notes'                => 'Pre-authorisation valid for services rendered at OpesCare Central General Hospital only. Any service not listed requires separate pre-authorisation. Subject to final claim verification.',
                    'authorised_by'        => 'CNPS Medical Reviewer — Dr. NKENG FOTSO, Paul',
                ];

            case 'birth-notification':
                return [
                    'child_name'               => 'NJOMO EKAMBI, Chloé Beatrice',
                    'child_sex'                => 'Female',
                    'date_of_birth'            => '07 June 2026',
                    'time_of_birth'            => '03:22 WAT',
                    'place_of_birth'           => 'OpesCare Central General Hospital — Maternity Suite, Room 4',
                    'birth_weight_kg'          => 3.42,
                    'birth_length_cm'          => 51,
                    'head_circumference_cm'    => 34,
                    'gestational_age_at_birth' => '39 weeks + 1 day',
                    'apgar_1min'               => 8,
                    'apgar_5min'               => 9,
                    'delivery_type'            => 'Normal Vaginal Delivery (NVD)',
                    'complications'            => 'None',
                    'mother_name'              => 'NJOMO EKAMBI, Marie Claire',
                    'mother_health_id'         => 'CMR-2024-00429871',
                    'mother_dob'               => '12 March 1985',
                    'father_name'              => 'EKAMBI BILONG, Jean-Baptiste Honoré',
                    'father_health_id'         => 'CMR-2018-00211043',
                    'attending_midwife'        => 'Sr. NGONO BIYA, Celestine — RM No. CMR-RM-0472',
                    'supervising_physician'    => 'Dr. ABANDA BELLA, Lydie Suzanne — Obstetrician',
                    'notification_for'         => 'Civil Registration — Mairie de Yaoundé Centre',
                    'legal_reference'          => 'Cameroon Ordinance No. 81/02 — Civil Status Registration',
                    'birth_certificate_ref'    => 'Ref to be assigned by registrar',
                    'vaccinations_at_birth'    => ['BCG (0.05 mL — left arm)', 'Oral Polio Vaccine (OPV0)', 'Hepatitis B birth dose'],
                    'vitamin_k_given'          => true,
                ];

            case 'care-plan':
                return [
                    'plan_start'             => '07 June 2026',
                    'plan_end'               => '07 September 2026',
                    'review_date'            => '07 July 2026',
                    'care_coordinator'       => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'multidisciplinary_team' => ['Internal Medicine — Dr. MBASSI ATEBA', 'Nephrology — Prof. TABI NDIP (referral pending)', 'Ophthalmology — Dr. AKONO OLINGA', 'Dietitian — Sr. MBARGA FOUDA, Claire', 'Community Health Nurse — NGONO BIYA, Celestine'],
                    'active_problems'        => [
                        ['problem' => 'Stage 2 Hypertension — Hypertensive Emergency', 'icd10' => 'I16.0', 'status' => 'Active — stabilised on discharge'],
                        ['problem' => 'Type 2 Diabetes Mellitus — poorly controlled',  'icd10' => 'E11.9', 'status' => 'Active — intensification required'],
                        ['problem' => 'Chronic Kidney Disease Stage 3a',                'icd10' => 'N18.3', 'status' => 'Active — nephrology referral'],
                        ['problem' => 'Dyslipidaemia',                                  'icd10' => 'E78.5', 'status' => 'Active — statin initiated'],
                        ['problem' => 'Grade II Hypertensive Retinopathy',              'icd10' => 'H35.0', 'status' => 'Active — ophthalmology referral'],
                    ],
                    'goals'                  => [
                        ['goal' => 'Achieve BP < 130/80 mmHg within 90 days', 'target_date' => '07 Sep 2026', 'metric' => 'Home BP monitoring log'],
                        ['goal' => 'Reduce HbA1c to < 7.0% within 6 months',  'target_date' => '07 Dec 2026', 'metric' => 'HbA1c blood test'],
                        ['goal' => 'eGFR stabilisation > 50 mL/min/1.73m²',   'target_date' => '07 Sep 2026', 'metric' => 'Serum creatinine + eGFR'],
                        ['goal' => 'LDL < 2.6 mmol/L',                         'target_date' => '07 Sep 2026', 'metric' => 'Lipid profile at 3 months'],
                        ['goal' => 'Weight reduction to BMI < 25',             'target_date' => '07 Dec 2026', 'metric' => 'Monthly weight check'],
                    ],
                    'interventions'          => [
                        ['category' => 'Medication', 'action' => 'Triple antihypertensive therapy as prescribed',           'frequency' => 'Daily',       'responsible' => 'Patient + Pharmacy'],
                        ['category' => 'Monitoring', 'action' => 'Home BP twice daily — morning + evening log',             'frequency' => 'Daily',       'responsible' => 'Patient'],
                        ['category' => 'Monitoring', 'action' => 'Fasting blood glucose daily',                             'frequency' => 'Daily',       'responsible' => 'Patient'],
                        ['category' => 'Diet',       'action' => 'Low sodium (< 2g/day) + low glycaemic index dietary plan','frequency' => 'Ongoing',     'responsible' => 'Dietitian + Patient'],
                        ['category' => 'Exercise',   'action' => '30 min moderate walking 5× per week — as BP permits',    'frequency' => 'Weekly ×5',   'responsible' => 'Patient'],
                        ['category' => 'Follow-up',  'action' => 'Internal Medicine OPD review',                            'frequency' => '14 Jun 2026', 'responsible' => 'Dr. MBASSI ATEBA'],
                        ['category' => 'Follow-up',  'action' => 'Nephrology consultation',                                 'frequency' => '28 Jun 2026', 'responsible' => 'Prof. TABI NDIP'],
                        ['category' => 'Follow-up',  'action' => 'Ophthalmology review',                                    'frequency' => '21 Jun 2026', 'responsible' => 'Dr. AKONO OLINGA'],
                        ['category' => 'Lab',        'action' => 'HbA1c + Renal function + Lipids at 3 months',            'frequency' => '07 Sep 2026', 'responsible' => 'OpesCare Central Lab'],
                        ['category' => 'Education',  'action' => 'Hypertension self-management education session',          'frequency' => '14 Jun 2026', 'responsible' => 'Community Health Nurse'],
                    ],
                    'patient_preferences'    => 'Patient prefers Francophone communication. Agrees to care plan. Concerned about renal prognosis — counselled.',
                    'emergency_contact'      => 'EKAMBI BILONG, Jean-Baptiste — Husband — Tel: +237 677 441 829',
                ];

            // ── 13 New Document Types ─────────────────────────────────────────

            case 'narcotic-prescription':
                return [
                    'narcotics_serial'         => 'CMR-NAR-2026-00441',
                    'doctor_narcotics_license' => 'MINSANTE-NAR-LIC-0092',
                    'prescription_date'        => '07 June 2026',
                    'valid_until'              => '17 June 2026 (10 days)',
                    'dispensing_pharmacy'      => 'OpesCare Central Pharmacy — Bonanjo, Douala',
                    'no_refill'                => true,
                    'clinical_justification'   => 'Severe post-operative pain following laparoscopic appendicectomy. Patient unable to achieve adequate analgesia with non-opioid therapy. Opioid therapy initiated under close monitoring as per MINSANTE narcotic prescribing guidelines.',
                    'allergy_warnings'         => 'Penicillin allergy documented. No known opioid allergies.',
                    'medications'              => [
                        [
                            'name'             => 'Morphine Sulfate',
                            'generic_name'     => 'Morphine Sulfate',
                            'strength'         => '10mg/mL',
                            'form'             => 'Oral Solution',
                            'dose'             => '5–10 mg',
                            'route'            => 'Oral',
                            'frequency'        => 'Every 4–6 hours as needed for pain',
                            'duration'         => '5 days',
                            'quantity_numeric' => 100,
                            'quantity_words'   => 'ONE HUNDRED MILLILITRES',
                            'schedule'         => 'Schedule II',
                            'instructions'     => 'Do not exceed 60mg in 24 hours. Avoid alcohol. Do not drive.',
                        ],
                        [
                            'name'             => 'Tramadol HCl',
                            'generic_name'     => 'Tramadol Hydrochloride',
                            'strength'         => '50mg',
                            'form'             => 'Tablet',
                            'dose'             => '50mg',
                            'route'            => 'Oral',
                            'frequency'        => 'Every 8 hours',
                            'duration'         => '5 days',
                            'quantity_numeric' => 15,
                            'quantity_words'   => 'FIFTEEN TABLETS',
                            'schedule'         => 'Schedule IV',
                            'instructions'     => 'Take with food. May cause drowsiness.',
                        ],
                    ],
                ];

            case 'death-certificate':
                return [
                    'deceased_name'          => 'EKAMBI BILONG, Jean-Baptiste Honoré',
                    'deceased_dob'           => '14 August 1952',
                    'deceased_sex'           => 'Male',
                    'deceased_nationality'   => 'Cameroonian',
                    'deceased_id_number'     => 'CMR-ID-1978-04412',
                    'date_of_death'          => '07 June 2026',
                    'time_of_death'          => '14:22 WAT',
                    'place_of_death'         => 'OpesCare Central General Hospital — Intensive Care Unit',
                    'cause_of_death_a'       => 'Acute Myocardial Infarction (STEMI — Left Anterior Descending)',
                    'cause_of_death_b'       => 'Coronary Artery Disease — Triple Vessel',
                    'cause_of_death_c'       => 'Type 2 Diabetes Mellitus — poorly controlled',
                    'cause_of_death_d'       => null,
                    'contributing_conditions'=> ['Systemic Arterial Hypertension (Stage 3)', 'Dyslipidaemia', 'Chronic Cigarette Smoking — 40 pack-years'],
                    'manner_of_death'        => 'Natural / Naturelle',
                    'autopsy_performed'      => false,
                    'autopsy_findings'       => null,
                    'certifying_physician'   => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'physician_reg_number'   => 'CMR-MD-0291',
                    'notification_for'       => 'Civil Registration — Mairie de Douala, Wouri Division',
                    'next_of_kin'            => 'NJOMO EKAMBI, Marie Claire (Spouse)',
                ];

            case 'death-summary':
                return [
                    'admission_date'             => '06 June 2026, 09:14 WAT',
                    'death_date'                 => '07 June 2026',
                    'death_time'                 => '14:22 WAT',
                    'total_length_of_stay'       => '1 day 5 hours 8 minutes',
                    'ward'                       => 'Intensive Care Unit — Bed ICU-4',
                    'admission_diagnosis'        => 'Acute STEMI — LAD territory with cardiogenic shock',
                    'final_cause_of_death'       => 'Acute Myocardial Infarction — Left Anterior Descending Territory with refractory cardiogenic shock',
                    'icd10_code'                 => 'I21.0',
                    'clinical_narrative'         => 'Mr. EKAMBI BILONG, a 73-year-old male with known CAD, T2DM, and HTN, presented with 4-hour history of crushing central chest pain radiating to the left arm, diaphoresis, and acute breathlessness. ECG confirmed anterior STEMI with ST elevation V1–V5. Immediate thrombolysis administered (Streptokinase 1.5MU IV). Initial reperfusion achieved but patient rapidly deteriorated into cardiogenic shock (BP 70/40 mmHg, HR 122 bpm, CRT > 4s). Intra-aortic balloon pump support initiated. Bedside echo confirmed severe LV dysfunction EF ~20%. Despite maximum vasopressor support (Noradrenaline + Dopamine), patient developed ventricular fibrillation at 14:18. Resuscitation attempted without success. Death pronounced at 14:22 WAT.',
                    'resuscitation_attempted'    => true,
                    'resuscitation_details'      => 'CPR initiated at 14:18 WAT for 4 minutes. Defibrillation ×3 (200J, 300J, 360J). IV Adrenaline 1mg ×2, Amiodarone 300mg IV. Return of spontaneous circulation not achieved. Resuscitation ceased at 14:22 WAT.',
                    'dnr_order_in_place'         => false,
                    'family_informed'            => true,
                    'family_informed_at'         => '07 June 2026, 14:45 WAT',
                    'next_of_kin_present'        => true,
                    'procedures_at_death'        => ['Intra-aortic balloon pump (IABP)', 'Central venous catheter — right internal jugular', 'Arterial line — right radial', 'Urinary catheter', 'Mechanical ventilation (intubated 13:55 WAT)'],
                    'active_medications_at_death'=> ['Noradrenaline 0.4 mcg/kg/min IV', 'Dopamine 10 mcg/kg/min IV', 'Heparin 1200 units/hr IV', 'Aspirin 300mg (loading dose administered)', 'Atorvastatin 80mg PO'],
                    'comorbidities'              => ['Type 2 Diabetes Mellitus', 'Coronary Artery Disease — Triple Vessel', 'Systemic Arterial Hypertension Stage 3', 'Dyslipidaemia', 'Chronic Kidney Disease Stage 2'],
                    'autopsy_requested'          => false,
                    'notification_sent_to'       => ['Civil Registrar — Mairie de Douala', 'MINSANTE District Health Officer — Wouri Division', 'Hospital Mortality Committee'],
                ];

            case 'transfer-letter':
                return [
                    'transfer_type'         => 'Emergency',
                    'transfer_urgency'      => 'urgent',
                    'from_facility'         => 'OpesCare Central General Hospital',
                    'from_ward'             => 'Internal Medicine — Ward 3B',
                    'to_facility'           => 'OpesCare University Teaching Hospital',
                    'to_department'         => 'Nephrology & Hypertension Unit — ICU',
                    'receiving_physician'   => 'Prof. TABI NDIP, Charles Etienne',
                    'transfer_date'         => '07 June 2026',
                    'transfer_time'         => '16:45 WAT',
                    'transport_mode'        => 'OpesCare Ambulance (ALS-equipped)',
                    'escort'                => 'Sr. NGONO BIYA, Celestine (RN) + Paramedic ABENA MFOUNDI',
                    'vitals_at_transfer'    => ['bp' => '148/92 mmHg', 'pulse' => '88 bpm', 'spo2' => '97%', 'temp' => '37.1°C', 'rr' => '18/min', 'gcs' => '15/15 (E4V5M6)'],
                    'iv_access'             => 'Right antecubital 18G cannula — Normal Saline 0.9% @ 80 mL/h',
                    'active_medications'    => ['Amlodipine 10mg OD (last dose 07:00)', 'Losartan 50mg OD (last dose 07:00)', 'Hydrochlorothiazide 12.5mg OD', 'Atorvastatin 40mg nocte'],
                    'ongoing_treatments'    => ['Daily fluid balance monitoring', 'BP every 4 hours', 'Continuous SpO2 monitoring', 'Low sodium diet (< 2g/day)'],
                    'clinical_summary'      => 'Ms. NJOMO EKAMBI is a 41-year-old female stabilised following hypertensive emergency (admission BP 210/130 mmHg). Investigations revealed CKD Stage 3a (eGFR 54 mL/min/1.73m²), bilateral echogenic kidneys, and 2+ proteinuria consistent with hypertensive nephropathy. BP now controlled on triple therapy. Transferred for specialist nephrology assessment and consideration of renal biopsy.',
                    'reason_for_transfer'   => 'Specialist nephrology evaluation for CKD Stage 3a in context of hypertensive nephropathy. Requires formal GFR staging, kidney biopsy consideration, and ARB dose optimisation beyond scope of general internal medicine service.',
                    'documents_accompanying'=> ['Discharge summary (draft)', 'Laboratory results (07 June 2026)', 'Renal ultrasound report', 'ECG strip', 'Medication reconciliation list'],
                    'special_instructions'  => 'Patient is anxious — please provide Francophone interpreter if available. Allergic to Penicillin (anaphylaxis). Avoid NSAIDs.',
                ];

            case 'pathology-report':
                return [
                    'specimen_id'             => 'SPEC-2026-008841',
                    'specimen_type'           => 'Appendix — Excised (Laparoscopic appendicectomy)',
                    'collection_date'         => '06 June 2026, 11:45',
                    'received_date'           => '06 June 2026, 13:20',
                    'report_date'             => '07 June 2026, 10:15',
                    'requesting_physician'    => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'pathologist'             => 'Dr. OWONA ATANGANA, Marcel-René',
                    'pathologist_reg'         => 'CMR-PATH-0031',
                    'clinical_indication'     => 'Acute appendicitis — perforated. Gangrenous appendix with localised peritonitis identified intraoperatively.',
                    'gross_description'       => 'Received in formalin: a tubular structure measuring 8.5 × 1.8 cm with attached mesoappendix. The serosal surface is haemorrhagic and covered by fibrinopurulent exudate. On section, the lumen is obliterated by necrotic material with a visible perforation 0.4 cm from the base. The wall is markedly thickened. No appendicolith identified.',
                    'microscopic_description' => 'Sections show transmural acute inflammation with areas of full-thickness necrosis (gangrene) consistent with gangrenous appendicitis. There is extensive neutrophilic infiltrate with fibrinopurulent periappendicitis. The mucosa shows ulceration and haemorrhage. No features of Crohn\'s disease or malignancy. Lymphoid aggregates are reactive. Vascular channels show margination of neutrophils.',
                    'special_stains'          => [],
                    'immunohistochemistry'    => [],
                    'diagnosis'               => 'GANGRENOUS PERFORATED APPENDICITIS — Acute transmural necrotising appendicitis with perforation and fibrinopurulent periappendicitis. No malignancy identified.',
                    'icd10_morphology'        => 'M44000 (Acute appendicitis with peritonitis)',
                    'staging'                 => null,
                    'margins'                 => 'Surgical resection margin clear of acute inflammation',
                    'lymph_nodes'             => null,
                    'pathologist_comment'     => 'The histological appearances are consistent with the clinical and intraoperative findings of gangrenous perforated appendicitis. No incidental neoplasm or granulomatous disease identified. Clinical correlation recommended for post-operative management.',
                    'critical_finding'        => false,
                ];

            case 'arv-card':
                return [
                    'hiv_test_date'              => '15 January 2024',
                    'hiv_confirmation_date'      => '22 January 2024',
                    'who_stage_at_enrollment'    => 'Stage III',
                    'who_stage_current'          => 'Stage I',
                    'cd4_at_enrollment'          => '214 cells/µL',
                    'cd4_current'                => '487 cells/µL',
                    'viral_load_current'         => '< 50 copies/mL (Undetectable)',
                    'viral_load_date'            => '01 May 2026',
                    'regimen_line'               => 'First-Line',
                    'current_regimen'            => 'TDF 300mg + 3TC 300mg + DTG 50mg (Once daily fixed-dose combination)',
                    'regimen_start_date'         => '05 February 2024',
                    'previous_regimens'          => [],
                    'ois_history'                => ['Oral candidiasis — treated January 2024 (Fluconazole)', 'Herpes Zoster — left thoracic dermatomal, March 2024 (Acyclovir)'],
                    'tb_coinfection'             => false,
                    'tb_treatment_status'        => null,
                    'pmtct'                      => false,
                    'adherence_counselling_dates'=> ['05 February 2024', '05 May 2024', '05 August 2024', '05 November 2024', '05 February 2025', '07 June 2026'],
                    'visit_log'                  => [
                        ['date' => '05 Feb 2024', 'weight' => '54 kg', 'cd4' => '214',  'viral_load' => 'Not done',          'adherence_pct' => '—',   'regimen' => 'TDF+3TC+DTG', 'notes' => 'Enrollment. OI treatment completed.'],
                        ['date' => '05 May 2024', 'weight' => '57 kg', 'cd4' => '312',  'viral_load' => '2,400 copies/mL',   'adherence_pct' => '94%', 'regimen' => 'TDF+3TC+DTG', 'notes' => 'Good response. Counselled on adherence.'],
                        ['date' => '05 Aug 2024', 'weight' => '59 kg', 'cd4' => '398',  'viral_load' => '< 200 copies/mL',   'adherence_pct' => '97%', 'regimen' => 'TDF+3TC+DTG', 'notes' => 'Virological suppression achieved.'],
                        ['date' => '05 Nov 2024', 'weight' => '61 kg', 'cd4' => '441',  'viral_load' => '< 50 copies/mL',    'adherence_pct' => '100%','regimen' => 'TDF+3TC+DTG', 'notes' => 'Undetectable. WHO Stage I.'],
                        ['date' => '01 May 2026', 'weight' => '63 kg', 'cd4' => '487',  'viral_load' => '< 50 copies/mL',    'adherence_pct' => '98%', 'regimen' => 'TDF+3TC+DTG', 'notes' => 'Sustained suppression. Annual VL confirmed.'],
                    ],
                    'next_appointment'           => '01 November 2026',
                    'case_manager'               => 'Sr. NGONO BIYA, Celestine — HIV Clinic Coordinator',
                ];

            case 'tb-dots-card':
                return [
                    'tb_registration_number'  => 'CMR-DOTS-2026-00312',
                    'treatment_category'      => 'Category I (New Case)',
                    'tb_type'                 => 'Pulmonary TB — Smear Positive (3+)',
                    'diagnosis_date'          => '03 January 2026',
                    'treatment_start_date'    => '10 January 2026',
                    'expected_end_date'       => '09 July 2026',
                    'intensive_phase_months'  => 2,
                    'continuation_phase_months' => 4,
                    'initial_weight_kg'       => 58,
                    'regimen'                 => '2RHZE / 4RH — Rifampicin 600mg + Isoniazid 300mg + Pyrazinamide 1500mg + Ethambutol 1200mg (Intensive Phase); Rifampicin 600mg + Isoniazid 300mg (Continuation Phase)',
                    'dots_supporter'          => 'ABANDA BELLA, Lydie (Community Health Worker — Zone 4)',
                    'hiv_status'              => 'Negative',
                    'diabetes_comorbidity'    => false,
                    'contact_tracing_done'    => true,
                    'smear_results'           => [
                        ['month' => 'Month 0 (Baseline)', 'result' => '3+ Positive'],
                        ['month' => 'Month 2 (End of IP)', 'result' => 'Negative'],
                        ['month' => 'Month 5 (Follow-up)', 'result' => 'Negative'],
                        ['month' => 'Month 6 (End of Tx)', 'result' => 'Pending'],
                    ],
                    'weight_monitoring'       => [
                        ['date' => '10 Jan 2026', 'weight_kg' => 58.0],
                        ['date' => '10 Feb 2026', 'weight_kg' => 59.5],
                        ['date' => '10 Mar 2026', 'weight_kg' => 61.2],
                        ['date' => '10 Apr 2026', 'weight_kg' => 62.8],
                        ['date' => '10 May 2026', 'weight_kg' => 63.4],
                    ],
                    'monthly_adherence'       => [
                        ['month_label' => 'Jan 2026', 'doses_expected' => 28, 'doses_taken' => 28, 'adherence_pct' => 100, 'status' => 'Complete'],
                        ['month_label' => 'Feb 2026', 'doses_expected' => 28, 'doses_taken' => 27, 'adherence_pct' => 96,  'status' => 'Complete'],
                        ['month_label' => 'Mar 2026', 'doses_expected' => 28, 'doses_taken' => 28, 'adherence_pct' => 100, 'status' => 'Complete'],
                        ['month_label' => 'Apr 2026', 'doses_expected' => 28, 'doses_taken' => 26, 'adherence_pct' => 93,  'status' => 'Complete'],
                        ['month_label' => 'May 2026', 'doses_expected' => 28, 'doses_taken' => 28, 'adherence_pct' => 100, 'status' => 'Complete'],
                        ['month_label' => 'Jun 2026', 'doses_expected' => 28, 'doses_taken' => 14, 'adherence_pct' => 50,  'status' => 'In Progress'],
                    ],
                    'adverse_effects'         => ['Peripheral neuropathy — mild (Isoniazid). Pyridoxine 25mg OD added Month 1.'],
                    'outcome'                 => 'On Treatment',
                    'treatment_officer'       => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                ];

            case 'psychiatric-assessment':
                return [
                    'assessment_date'              => '07 June 2026',
                    'assessment_type'              => 'Initial Assessment',
                    'referral_source'              => 'Emergency Department',
                    'presenting_complaint'         => 'Patient presents with agitation, paranoid ideation, auditory hallucinations, and refusal to eat for 5 days. Brought in by family members.',
                    'history_of_presenting_illness'=> 'Mr. OWONA, a 28-year-old male, was well until approximately 6 weeks ago when he began displaying unusual behaviour: social withdrawal, talking to himself, claiming neighbours were conspiring against him. In the past week symptoms escalated with auditory hallucinations ("voices telling him to harm himself"), insomnia, and complete food refusal. No precipitant identified by family. No recent travel or febrile illness.',
                    'psychiatric_history'          => 'No previous psychiatric admissions. Family reports one episode of "strange behaviour" 3 years ago which resolved spontaneously without treatment. No history of self-harm or suicide attempts.',
                    'substance_use'                => 'Cannabis use — approximately 3–4 joints daily for 3 years. Denies alcohol or other substance use. Family confirms cannabis use.',
                    'family_psychiatric_history'   => 'Maternal uncle: reported to have had "madness" — no formal diagnosis known.',
                    'forensic_history'             => 'None',
                    'mse'                          => [
                        'appearance'    => 'Dishevelled, poor hygiene, malodorous. Wearing inappropriate layered clothing. Appears older than stated age.',
                        'behaviour'     => 'Agitated, guarded, intermittently muttering. Established partial rapport. No overt aggression during interview.',
                        'speech'        => 'Pressured, occasionally incoherent. Increased rate and volume.',
                        'mood'          => 'Subjectively: "fine — there is nothing wrong with me." Objectively: dysphoric, irritable.',
                        'affect'        => 'Incongruent — laughing inappropriately at times. Restricted range.',
                        'thought_form'  => 'Loosening of associations. Tangential at times. No formal thought disorder.',
                        'thought_content'=> 'Persecutory delusions (neighbours poisoning food and water). Ideas of reference. Denies suicidal ideation. Endorses passive death wishes.',
                        'perceptions'   => 'Auditory hallucinations — command type ("voices telling him he is cursed"). Denies visual hallucinations.',
                        'cognition'     => 'Alert, oriented ×3. Digit span 5 forward. Unable to complete serial 7s. Concentration impaired.',
                        'insight'       => 'Absent — denies mental illness. Attributes experiences to "spiritual attacks."',
                        'judgement'     => 'Impaired — unable to weigh risks of food refusal.',
                    ],
                    'risk_assessment'              => [
                        'suicidal_ideation' => 'Passive death wishes present. No active plan or intent.',
                        'homicidal_ideation'=> 'None elicited.',
                        'self_harm'         => 'No history. Current passive ideation only.',
                        'overall_risk'      => 'Medium',
                    ],
                    'diagnosis'                    => [
                        ['diagnosis' => 'Acute and Transient Psychotic Disorder — with symptoms of schizophrenia (Cannabis-related aetiology to be excluded)', 'icd11_code' => '6A23', 'type' => 'Primary'],
                        ['diagnosis' => 'Cannabis Use Disorder — moderate severity', 'icd11_code' => '6C43.1', 'type' => 'Secondary'],
                    ],
                    'capacity_assessment'          => [
                        'understands'   => false,
                        'retains'       => false,
                        'weighs'        => false,
                        'communicates'  => true,
                        'has_capacity'  => false,
                    ],
                    'formulation'                  => 'Mr. OWONA is a 28-year-old male presenting with a first episode of acute psychosis likely precipitated and perpetuated by heavy cannabis use on a possible background of familial predisposition. Biologically: dopaminergic dysregulation (cannabis + genetic vulnerability). Psychologically: poor insight, paranoid cognitive schema. Socially: social isolation, limited support network, stigma around mental illness.',
                    'management_plan'              => [
                        'Admit to psychiatric unit under Cameroon Mental Health Act for assessment and stabilisation',
                        'Olanzapine 10mg PO nocte — commence immediately (consent by next-of-kin under capacity provisions)',
                        'Lorazepam 2mg IM PRN for acute agitation (max 3 doses/24h)',
                        'Nutritional support — nasogastric if food refusal continues > 48h',
                        'Cannabis cessation counselling — commence when settled',
                        'Family psychoeducation session within 72 hours',
                        'EEG + MRI Brain to exclude organic aetiology',
                        'HIV, syphilis, and metabolic screen to exclude organic psychosis',
                    ],
                    'medications_recommended'      => ['Olanzapine 10mg PO nocte', 'Lorazepam 2mg IM PRN'],
                    'follow_up'                    => 'Review in 72 hours. If no improvement, consider Risperidone or Haloperidol augmentation.',
                ];

            case 'opd-summary':
                return [
                    'visit_date'           => '07 June 2026, 09:15 WAT',
                    'visit_type'           => 'Follow-up',
                    'clinic'               => 'Internal Medicine OPD',
                    'chief_complaint'      => 'Hypertension review — 2 weeks post-discharge follow-up. Mild headache. No chest pain.',
                    'vitals'               => ['bp' => '138/88 mmHg', 'pulse' => '76 bpm', 'temp' => '36.8°C', 'weight_kg' => '69.1 kg', 'height_cm' => '163 cm', 'spo2' => '98%', 'bmi' => '26.0'],
                    'history_summary'      => 'Patient discharged 2 weeks ago following hypertensive emergency. Reports compliance with all medications. Home BP readings range 140–148/88–92 mmHg. No headache until today (mild, occipital, 4/10). No chest pain, no shortness of breath, no palpitations. Bowels regular. Appetite good.',
                    'examination_findings' => 'Cardiovascular: Regular rate and rhythm. No murmurs. JVP not elevated. Respiratory: Clear to auscultation bilaterally. Abdomen: Soft, non-tender. Mild bilateral ankle oedema (1+). Neurological: Intact. Fundi: Grade II changes unchanged from admission.',
                    'working_diagnosis'    => [
                        ['diagnosis' => 'Hypertension — Stage 2, partially controlled', 'icd10' => 'I10', 'type' => 'Primary'],
                        ['diagnosis' => 'Chronic Kidney Disease — Stage 3a', 'icd10' => 'N18.3', 'type' => 'Secondary'],
                        ['diagnosis' => 'Type 2 Diabetes Mellitus', 'icd10' => 'E11.9', 'type' => 'Secondary'],
                    ],
                    'investigations_ordered'=> [
                        ['test' => 'Serum Creatinine + eGFR', 'urgency' => 'Routine', 'notes' => 'Baseline 2-week renal function check'],
                        ['test' => 'Urine Albumin:Creatinine Ratio', 'urgency' => 'Routine', 'notes' => 'Monitor proteinuria'],
                        ['test' => 'Fasting Blood Glucose + HbA1c', 'urgency' => 'Routine', 'notes' => 'Diabetic monitoring'],
                    ],
                    'prescription_issued'  => true,
                    'prescription_number'  => 'OC-RX-2026-092441',
                    'management_notes'     => 'Continue current antihypertensive triple therapy. Increase Losartan to 100mg OD — BP target < 130/80 mmHg not yet achieved. Reinforce low sodium diet. Reinforce home BP log twice daily. Patient educated on importance of nephrology follow-up.',
                    'next_review'          => '28 June 2026 — Nephrology Clinic (Prof. TABI NDIP) + Internal Medicine OPD 05 July 2026',
                    'instructions_to_patient' => [
                        'Continue all medications as prescribed — do not stop without consulting your doctor',
                        'Measure blood pressure twice daily (morning + evening) and record in your BP diary',
                        'Low sodium diet: avoid added salt, processed foods, smoked fish',
                        'Drink 1.5 litres of water daily — not more',
                        'Return immediately if: severe headache, vision changes, chest pain, or BP > 180/110 mmHg',
                        'Attend Nephrology appointment on 28 June 2026 with all previous lab results',
                    ],
                    'referral_issued'      => false,
                    'referral_to'          => null,
                ];

            case 'insurance-claim':
                return [
                    'claim_number'          => 'CLM-CNPS-2026-041892',
                    'submission_date'       => '08 June 2026',
                    'insurer_name'          => 'CNPS Health Insurance',
                    'insurer_branch'        => 'CNPS Douala Regional Office',
                    'insurer_address'       => 'Boulevard de la Liberté, Bonanjo, Douala — BP 1234',
                    'policy_holder'         => 'NJOMO EKAMBI, Marie Claire',
                    'policy_number'         => 'CNPS-2024-00987-B',
                    'employer'              => 'Ministère de la Santé Publique du Cameroun',
                    'encounter_date'        => '05–07 June 2026',
                    'encounter_type'        => 'Inpatient',
                    'admission_date'        => '05 June 2026',
                    'discharge_date'        => '07 June 2026',
                    'primary_diagnosis'     => 'Hypertensive Emergency — Stage 2 (ICD-10: I16.0)',
                    'secondary_diagnoses'   => ['Type 2 Diabetes Mellitus (E11.9)', 'CKD Stage 3a (N18.3)', 'Dyslipidaemia (E78.5)'],
                    'services'              => [
                        ['description' => 'Emergency Consultation — Internal Medicine',  'cpt_code' => 'CONS-EM-001', 'icd10_code' => 'I16.0', 'quantity' => 1, 'unit_cost' => 15000, 'claimed_amount' => 15000, 'approved_amount' => 15000],
                        ['description' => 'Inpatient Admission — Internal Medicine (×2 days)', 'cpt_code' => 'IPD-INT-003', 'icd10_code' => 'I16.0', 'quantity' => 2, 'unit_cost' => 22500, 'claimed_amount' => 45000, 'approved_amount' => 45000],
                        ['description' => 'CBC + Metabolic Panel + HbA1c + Lipids',     'cpt_code' => 'LAB-MET-012', 'icd10_code' => 'I16.0', 'quantity' => 1, 'unit_cost' => 30000, 'claimed_amount' => 30000, 'approved_amount' => 30000],
                        ['description' => 'Renal Ultrasound',                            'cpt_code' => 'RAD-US-009',  'icd10_code' => 'N18.3', 'quantity' => 1, 'unit_cost' => 25000, 'claimed_amount' => 25000, 'approved_amount' => null],
                        ['description' => 'Chest X-Ray (PA + Lateral)',                  'cpt_code' => 'RAD-CXR-001', 'icd10_code' => 'I50.0', 'quantity' => 1, 'unit_cost' => 18000, 'claimed_amount' => 18000, 'approved_amount' => null],
                        ['description' => 'ECG (12-lead)',                               'cpt_code' => 'CARD-ECG-001','icd10_code' => 'I16.0', 'quantity' => 1, 'unit_cost' => 7500,  'claimed_amount' => 7500,  'approved_amount' => null],
                    ],
                    'subtotal_claimed'      => 140500,
                    'patient_copay'         => 28100,
                    'net_claimed'           => 112400,
                    'claim_status'          => 'Under Review',
                    'supporting_documents'  => ['Discharge summary (OC-DIS-2026-010029)', 'Original invoices (OC-INV-2026-088421)', 'Laboratory reports', 'Radiology reports', 'Physician prescription records', 'Patient ID + CNPS card copies'],
                    'bank_name'             => 'Afriland First Bank — Douala Akwa Branch',
                    'bank_account'          => 'AF-20191-00441-0084712-22',
                    'provider_reg_number'   => 'MINSANTE-PROV-2019-00847',
                ];

            case 'fitness-certificate':
                return [
                    'certificate_purpose'   => 'Employment Medical Clearance',
                    'examination_date'      => '07 June 2026',
                    'valid_until'           => '07 June 2027 (12 months)',
                    'intended_recipient'    => 'Employer: Ministère de la Santé Publique du Cameroun — Human Resources Department',
                    'examination_findings'  => 'Patient examined and found medically fit for sedentary to light office duty. Blood pressure well-controlled on antihypertensive therapy. No acute illness. Chronic conditions stable and under active management.',
                    'vitals'                => ['bp' => '138/88 mmHg', 'pulse' => '76 bpm', 'weight_kg' => '69.1 kg', 'height_cm' => '163 cm', 'bmi' => '26.0', 'vision_right' => '6/6', 'vision_left' => '6/9', 'hearing' => 'Normal bilateral'],
                    'systems_examined'      => [
                        ['system' => 'Cardiovascular',    'finding' => 'Regular rate and rhythm. No murmurs. BP 138/88 mmHg on therapy.',      'normal' => true],
                        ['system' => 'Respiratory',       'finding' => 'Clear to auscultation bilaterally. Good air entry.',                    'normal' => true],
                        ['system' => 'Abdomen',           'finding' => 'Soft, non-tender. No organomegaly.',                                    'normal' => true],
                        ['system' => 'Neurological',      'finding' => 'Alert and oriented. Cranial nerves intact. No focal deficit.',          'normal' => true],
                        ['system' => 'Musculoskeletal',   'finding' => 'Full range of movement. No joint deformity.',                           'normal' => true],
                        ['system' => 'Ophthalmological',  'finding' => 'Grade II hypertensive retinopathy. Corrected vision 6/6 R, 6/9 L.',   'normal' => false],
                    ],
                    'investigations_done'   => [
                        ['test' => 'Urinalysis',        'result' => '1+ protein, no glucose, no blood', 'normal' => false],
                        ['test' => 'Fasting Glucose',   'result' => '7.4 mmol/L',                       'normal' => false],
                        ['test' => 'Haemoglobin',       'result' => '12.8 g/dL',                        'normal' => true],
                    ],
                    'fitness_verdict'       => 'FIT WITH RESTRICTIONS',
                    'restrictions'          => ['No heavy manual labour or sustained physical exertion', 'No working at heights without BP re-check', 'Not fit for commercial vehicle driving until BP < 130/80 mmHg sustained × 3 months'],
                    'fit_for'               => 'Sedentary to light office-based duties within the Ministry of Public Health',
                    'review_required'       => true,
                    'review_date'           => '07 December 2026 (6-month review)',
                ];

            case 'blood-transfusion':
                return [
                    'transfusion_date'          => '06 June 2026',
                    'transfusion_indication'    => 'Symptomatic anaemia (Hb 6.8 g/dL) with dyspnoea at rest in context of acute post-operative blood loss following laparoscopic appendicectomy.',
                    'patient_blood_group'       => 'B Positive (B+)',
                    'patient_crossmatch_result' => 'Compatible',
                    'pre_transfusion_hb'        => '6.8 g/dL',
                    'post_transfusion_hb'       => '9.4 g/dL',
                    'consent_obtained'          => true,
                    'consent_by'                => 'NJOMO EKAMBI, Marie Claire (Patient)',
                    'consent_datetime'          => '06 June 2026, 13:45 WAT',
                    'blood_bank_ref'            => 'BB-2026-00941',
                    'ordering_physician'        => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'blood_bank_technician'     => 'Mr. ETOA ABESSOLO, Didier — Blood Bank Technician',
                    'reactions'                 => ['No adverse transfusion reactions observed'],
                    'reaction_management'       => null,
                    'units'                     => [
                        [
                            'unit_number'    => 'RCC-2026-004412-A',
                            'product_type'   => 'Packed Red Blood Cells (PRBC)',
                            'volume_ml'      => 280,
                            'blood_group'    => 'B Positive (B+)',
                            'expiry_date'    => '28 June 2026',
                            'start_time'     => '14:10 WAT',
                            'end_time'       => '18:10 WAT',
                            'duration_mins'  => 240,
                            'administered_by'=> 'Sr. NGONO BIYA, Celestine (RN)',
                            'rate_ml_hr'     => 70,
                            'pre_obs'        => ['bp' => '102/68', 'pulse' => '104', 'temp' => '36.9°C', 'spo2' => '94%'],
                            'mid_obs'        => ['bp' => '108/72', 'pulse' => '96',  'temp' => '37.1°C', 'spo2' => '96%'],
                            'post_obs'       => ['bp' => '116/76', 'pulse' => '88',  'temp' => '37.0°C', 'spo2' => '98%'],
                        ],
                        [
                            'unit_number'    => 'RCC-2026-004413-B',
                            'product_type'   => 'Packed Red Blood Cells (PRBC)',
                            'volume_ml'      => 280,
                            'blood_group'    => 'B Positive (B+)',
                            'expiry_date'    => '28 June 2026',
                            'start_time'     => '19:00 WAT',
                            'end_time'       => '23:00 WAT',
                            'duration_mins'  => 240,
                            'administered_by'=> 'Sr. NGONO BIYA, Celestine (RN)',
                            'rate_ml_hr'     => 70,
                            'pre_obs'        => ['bp' => '118/78', 'pulse' => '86',  'temp' => '37.0°C', 'spo2' => '98%'],
                            'mid_obs'        => ['bp' => '122/80', 'pulse' => '82',  'temp' => '37.2°C', 'spo2' => '99%'],
                            'post_obs'       => ['bp' => '124/82', 'pulse' => '80',  'temp' => '37.1°C', 'spo2' => '99%'],
                        ],
                    ],
                ];

            case 'nursing-chart':
                return [
                    'chart_date'         => '07 June 2026',
                    'ward'               => 'Internal Medicine — Ward 3B',
                    'bed_number'         => 'BED-3B-12',
                    'admitting_diagnosis'=> 'Hypertensive Emergency — CKD Stage 3a',
                    'allergies'          => ['Penicillin — Anaphylaxis', 'NSAIDs — Relative contraindication'],
                    'shift'              => 'Day (07:00–19:00)',
                    'ward_nurse'         => 'Sr. NGONO BIYA, Celestine (RN)',
                    'observations'       => [
                        ['time' => '07:00', 'bp' => '152/96', 'pulse' => '84',  'temp' => '36.8', 'spo2' => '97', 'rr' => '18', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 2, 'urine_output_ml' => null,  'notes' => 'Awake, alert. Morning vitals.'],
                        ['time' => '09:00', 'bp' => '148/92', 'pulse' => '80',  'temp' => '36.9', 'spo2' => '98', 'rr' => '16', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 1, 'urine_output_ml' => 180,  'notes' => 'Medications given. Breakfast tolerated.'],
                        ['time' => '11:00', 'bp' => '144/90', 'pulse' => '78',  'temp' => '37.0', 'spo2' => '98', 'rr' => '16', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 1, 'urine_output_ml' => 200,  'notes' => 'BP improving. Patient ambulant.'],
                        ['time' => '13:00', 'bp' => '140/88', 'pulse' => '76',  'temp' => '36.9', 'spo2' => '98', 'rr' => '16', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 0, 'urine_output_ml' => 190,  'notes' => 'Lunch tolerated well. Comfortable.'],
                        ['time' => '15:00', 'bp' => '138/88', 'pulse' => '74',  'temp' => '36.8', 'spo2' => '99', 'rr' => '15', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 0, 'urine_output_ml' => 210,  'notes' => 'Dr. review completed. Discharge planned.'],
                        ['time' => '17:00', 'bp' => '136/86', 'pulse' => '72',  'temp' => '36.7', 'spo2' => '99', 'rr' => '15', 'gcs_e' => 4, 'gcs_v' => 5, 'gcs_m' => 6, 'gcs_total' => 15, 'pain_score' => 0, 'urine_output_ml' => 175,  'notes' => 'Discharge paperwork complete. Patient ready.'],
                    ],
                    'fluid_balance'      => [
                        'intake'          => [
                            ['time' => '08:00', 'type' => 'Oral fluids',         'amount_ml' => 300],
                            ['time' => '10:00', 'type' => 'IV NS 0.9%',          'amount_ml' => 200],
                            ['time' => '13:00', 'type' => 'Oral — lunch + water','amount_ml' => 400],
                            ['time' => '16:00', 'type' => 'Oral fluids',         'amount_ml' => 250],
                        ],
                        'output'          => [
                            ['time' => '09:00', 'type' => 'Urine',  'amount_ml' => 180],
                            ['time' => '11:00', 'type' => 'Urine',  'amount_ml' => 200],
                            ['time' => '13:00', 'type' => 'Urine',  'amount_ml' => 190],
                            ['time' => '15:00', 'type' => 'Urine',  'amount_ml' => 210],
                            ['time' => '17:00', 'type' => 'Urine',  'amount_ml' => 175],
                        ],
                        'total_intake_ml' => 1150,
                        'total_output_ml' => 955,
                        'balance_ml'      => 195,
                    ],
                    'medications_given'  => [
                        ['time' => '08:00', 'drug' => 'Amlodipine 10mg',            'dose' => '10mg',   'route' => 'PO', 'given_by' => 'Sr. NGONO BIYA'],
                        ['time' => '08:00', 'drug' => 'Losartan 50mg',              'dose' => '50mg',   'route' => 'PO', 'given_by' => 'Sr. NGONO BIYA'],
                        ['time' => '08:00', 'drug' => 'Hydrochlorothiazide 12.5mg', 'dose' => '12.5mg', 'route' => 'PO', 'given_by' => 'Sr. NGONO BIYA'],
                        ['time' => '20:00', 'drug' => 'Atorvastatin 40mg',          'dose' => '40mg',   'route' => 'PO', 'given_by' => 'Sr. EYINGA MBIDA'],
                    ],
                    'nursing_notes'      => [
                        ['time' => '07:30', 'note' => 'Patient alert and cooperative. Reviewed medications. BP diary checked — average home readings 144/90 mmHg over past week.', 'nurse' => 'Sr. NGONO BIYA'],
                        ['time' => '10:30', 'note' => 'Patient reports mild occipital headache 3/10. Paracetamol 500mg PO administered (not charted — PRN order reviewed with Dr. MBASSI).', 'nurse' => 'Sr. NGONO BIYA'],
                        ['time' => '14:00', 'note' => 'Discharge counselling commenced. Patient educated on medication compliance, BP monitoring, low sodium diet, and red flag symptoms.', 'nurse' => 'Sr. NGONO BIYA'],
                        ['time' => '16:30', 'note' => 'Patient verbalized understanding of discharge instructions. Transport arranged with family. Discharge medication pack dispensed.', 'nurse' => 'Sr. NGONO BIYA'],
                    ],
                ];

            // ── Final 22 Payload Cases ────────────────────────────────────────

            case 'anaesthesia-record':
                return [
                    'procedure_name'       => 'Laparoscopic Appendicectomy',
                    'surgery_date'         => '06 June 2026',
                    'anaesthesia_type'     => 'General Anaesthesia — LMA',
                    'asa_grade'            => 'ASA II',
                    'anaesthetist'         => 'Dr. FONKOU NJIKE, Blaise Albert',
                    'anaesthetist_reg'     => 'CMR-ANS-0041',
                    'surgeon'              => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'theatre'              => 'Theatre 2',
                    'pre_op_assessment_date' => '06 June 2026',
                    'pre_op_vitals'        => ['bp' => '118/76', 'pulse' => '82', 'spo2' => '99%', 'temp' => '36.9°C', 'weight_kg' => 69, 'height_cm' => 163],
                    'airway_assessment'    => ['mallampati' => 'Class II', 'mouth_opening' => '> 3 cm', 'neck_mobility' => 'Full', 'thyromental_distance' => '> 6.5 cm', 'predicted_difficulty' => false],
                    'fasting_status'       => 'NPO > 8 hours (solids), > 4 hours (liquids)',
                    'relevant_history'     => ['Penicillin allergy — anaphylaxis', 'No previous anaesthetic problems', 'Stage 2 Hypertension — controlled'],
                    'pre_medications'      => [['drug' => 'Midazolam', 'dose' => '2mg', 'route' => 'IV', 'time' => '09:50']],
                    'induction_agents'     => [['drug' => 'Propofol', 'dose' => '150mg', 'route' => 'IV'], ['drug' => 'Fentanyl', 'dose' => '100mcg', 'route' => 'IV']],
                    'maintenance_agents'   => [['agent' => 'Sevoflurane', 'concentration' => '2.0–2.5%'], ['agent' => 'O2/Air 50:50', 'concentration' => '2L/min each']],
                    'muscle_relaxants'     => [['drug' => 'Atracurium', 'dose' => '30mg', 'time' => '10:08']],
                    'intubation'           => ['method' => 'LMA #4', 'attempts' => 1, 'cormack_lehane' => 'Grade I', 'confirmed_by' => 'EtCO2 + bilateral breath sounds'],
                    'intraop_vitals'       => [
                        ['time' => '10:10', 'bp' => '122/78', 'hr' => '80', 'spo2' => '99%', 'etco2' => '34', 'agent_percent' => '2.0'],
                        ['time' => '10:30', 'bp' => '118/76', 'hr' => '76', 'spo2' => '100%','etco2' => '35', 'agent_percent' => '2.2'],
                        ['time' => '11:00', 'bp' => '116/74', 'hr' => '74', 'spo2' => '100%','etco2' => '34', 'agent_percent' => '2.0'],
                        ['time' => '11:30', 'bp' => '120/78', 'hr' => '78', 'spo2' => '99%', 'etco2' => '35', 'agent_percent' => '1.8'],
                    ],
                    'fluids_given'         => [['fluid' => 'Hartmann\'s Solution', 'volume_ml' => 500]],
                    'blood_loss_ml'        => 30,
                    'urine_output_ml'      => 120,
                    'reversal_agents'      => [['drug' => 'Neostigmine', 'dose' => '2.5mg'], ['drug' => 'Atropine', 'dose' => '1.2mg']],
                    'anaesthesia_start'    => '10:08',
                    'anaesthesia_end'      => '11:55',
                    'recovery_handover'    => 'Patient transferred to recovery conscious, SpO2 99% on O2, pain 2/10, moving all limbs.',
                    'complications'        => 'None',
                ];

            case 'lama-form':
                return [
                    'lama_date'                => '07 June 2026',
                    'lama_time'                => '15:40 WAT',
                    'reason_for_admission'     => 'Hypertensive emergency — BP 210/130 mmHg. CKD Stage 3a investigation incomplete.',
                    'medical_advice_given'     => 'Patient advised to remain for minimum 72 hours for BP optimisation, nephrology review, and investigation completion.',
                    'risks_explained'          => ['Hypertensive stroke — risk 15–25% with uncontrolled BP > 200/120 mmHg', 'Acute kidney injury / irreversible CKD progression', 'Hypertensive encephalopathy or retinal hemorrhage', 'Acute heart failure', 'Risk of death if BP not controlled'],
                    'patient_understands_risks'=> true,
                    'reason_for_leaving'       => 'Patient states family emergency at home. Child unwell. Will return in 2 days.',
                    'mental_capacity_assessed' => true,
                    'has_capacity'             => true,
                    'capacity_notes'           => 'Patient oriented in time, place, and person. Able to repeat back risks in own words. Decision appears voluntary and free from undue influence.',
                    'medications_dispensed'    => ['Amlodipine 10mg tablets × 7 (1 week supply)', 'Losartan 50mg tablets × 7 (1 week supply)'],
                    'follow_up_arranged'       => true,
                    'follow_up_details'        => 'Internal Medicine OPD — 09 June 2026, 09:00. Dr. MBASSI ATEBA. Tel: +237 600-000-000.',
                    'witness_name'             => 'NGONO BIYA, Celestine',
                    'witness_designation'      => 'Registered Nurse',
                    'next_of_kin_present'      => false,
                    'nok_name'                 => null,
                    'nok_relationship'         => null,
                ];

            case 'aer-report':
                return [
                    'arrival_date'             => '05 June 2026',
                    'arrival_time'             => '22:47 WAT',
                    'arrival_mode'             => 'Self / Walk-in (assisted by husband)',
                    'triage_time'              => '22:51 WAT',
                    'triage_category'          => 2,
                    'triage_nurse'             => 'Sr. EYINGA MBIDA, Jeanne',
                    'chief_complaint'          => 'Severe headache (8/10), visual blurring, and nausea × 3 hours',
                    'triage_vitals'            => ['bp' => '210/130', 'pulse' => '98', 'temp' => '37.1', 'spo2' => '96%', 'rr' => '20', 'gcs' => '15', 'pain_score' => 8],
                    'history'                  => 'Known hypertensive on Amlodipine 5mg OD — ran out of medication 3 days ago. Presents with sudden-onset severe occipital headache, bilateral visual blurring, nausea, and vomiting × 2 since 20:00. No chest pain. No focal neurological deficit. No history of head trauma.',
                    'examination'              => 'Alert, oriented. Distressed. BP 210/130 mmHg bilaterally. HR 98 bpm regular. Pupils equal and reactive. Fundoscopy: Grade III hypertensive retinopathy with flame haemorrhages. No papilloedema. Cardiovascular: Regular, no murmurs. Lungs: Clear. Abdomen: Soft.',
                    'investigations'           => [
                        ['test' => 'ECG (12-lead)', 'result' => 'LVH with strain pattern. No ischaemic changes.', 'critical' => false],
                        ['test' => 'Troponin I', 'result' => '0.02 ng/mL (Normal < 0.04)', 'critical' => false],
                        ['test' => 'Serum Creatinine', 'result' => '1.42 mg/dL ↑', 'critical' => false],
                        ['test' => 'Urinalysis', 'result' => '2+ protein, no blood', 'critical' => false],
                        ['test' => 'CT Head (non-contrast)', 'result' => 'No haemorrhage. No infarct. Mild periventricular white matter changes.', 'critical' => false],
                    ],
                    'working_diagnosis'        => [
                        ['diagnosis' => 'Hypertensive Emergency (BP 210/130 mmHg) with Grade III Retinopathy', 'icd10' => 'I16.0'],
                    ],
                    'treatment_given'          => [
                        ['drug_or_intervention' => 'IV access — 18G right antecubital', 'dose_or_detail' => 'NS 0.9% TKO', 'time' => '23:00'],
                        ['drug_or_intervention' => 'Labetalol IV', 'dose_or_detail' => '20mg over 2 minutes', 'time' => '23:05'],
                        ['drug_or_intervention' => 'Labetalol IV (repeat)', 'dose_or_detail' => '40mg over 10 minutes', 'time' => '23:25'],
                        ['drug_or_intervention' => 'Nifedipine SL', 'dose_or_detail' => '10mg sublingual', 'time' => '23:45'],
                    ],
                    'response_to_treatment'    => 'BP reduced to 172/104 mmHg at 00:15. Target BP 160/100 mmHg achieved by 01:00. Headache improved from 8/10 to 4/10. Vision clearing.',
                    'disposition'              => 'Admitted',
                    'disposition_time'         => '01:30 WAT',
                    'admitted_to'              => 'Internal Medicine — Ward 3B (Bed 12)',
                    'discharge_instructions'   => [],
                    'follow_up'                => null,
                    'treating_physician'       => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'total_ed_time_minutes'    => 163,
                ];

            case 'medicolegal-report':
                return [
                    'report_type'              => 'Road Traffic Accident',
                    'examination_date'         => '07 June 2026',
                    'examination_time'         => '10:30 WAT',
                    'requesting_authority'     => 'Commissariat de Police Centrale — Douala Akwa, PV-2026-DOA-00441',
                    'police_ref_number'        => 'PV-2026-DOA-00441',
                    'history_of_incident'      => 'Patient reports being a pedestrian struck by a motorcycle at the intersection of Boulevard de la Liberté and Rue Joss, Bonanjo, Douala at approximately 08:15 on 07 June 2026. Patient was thrown approximately 2 metres and struck the pavement. No loss of consciousness reported. Police were called. Patient initially refused ambulance but was brought to casualty by police escort.',
                    'examination_findings'     => 'Alert, oriented, ambulant with limp. No acute distress. Abrasions over right knee, right palm, and right elbow. Contusion over right temporal scalp (non-haematoma). Tenderness over right clavicle with mild swelling — probable fracture, X-ray ordered. Neck: mild tenderness C4-C6 on palpation, full range of movement. No thoracic or abdominal tenderness.',
                    'injuries'                 => [
                        ['location' => 'Right temporal scalp', 'description' => '4cm contusion, no laceration, no haematoma', 'type' => 'contusion', 'age' => 'fresh', 'size_cm' => '4'],
                        ['location' => 'Right clavicle',       'description' => 'Swelling and tenderness mid-shaft, probable fracture pending X-ray', 'type' => 'fracture', 'age' => 'fresh', 'size_cm' => null],
                        ['location' => 'Right knee',           'description' => '6cm × 3cm area of abrasion with superficial gravel embedded', 'type' => 'abrasion', 'age' => 'fresh', 'size_cm' => '6'],
                        ['location' => 'Right palm',           'description' => '3cm × 2cm abrasion', 'type' => 'abrasion', 'age' => 'fresh', 'size_cm' => '3'],
                        ['location' => 'Right elbow',          'description' => '2cm contusion with small abrasion', 'type' => 'contusion', 'age' => 'fresh', 'size_cm' => '2'],
                    ],
                    'genital_examination'      => null,
                    'specimens_collected'      => [],
                    'investigations'           => [
                        ['test' => 'Right clavicle X-ray', 'result' => 'Mid-shaft fracture right clavicle — no displacement'],
                        ['test' => 'Cervical spine X-ray', 'result' => 'No fracture or dislocation. Mild C4-C5 disc space narrowing (chronic).'],
                    ],
                    'degree_of_injury'         => 'Grievous',
                    'incapacity_days'          => 21,
                    'fitness_for_interview'    => true,
                    'fitness_notes'            => 'Patient is alert, oriented, and capable of providing a coherent statement.',
                    'opinion'                  => 'The injuries documented are consistent with the alleged history of a pedestrian road traffic accident. The mid-shaft clavicle fracture constitutes a grievous bodily injury as defined under Cameroon Penal Code Article 277. Estimated incapacity of 21 days.',
                    'examiner'                 => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'examiner_reg'             => 'CMR-MD-0291',
                ];

            case 'autopsy-report':
                return [
                    'autopsy_date'             => '08 June 2026',
                    'autopsy_time_start'       => '09:00',
                    'autopsy_time_end'         => '12:30',
                    'autopsy_type'             => 'Hospital Autopsy (Family Consent + Clinical Team)',
                    'requesting_authority'     => 'Family Consent + Clinical Team — OpesCare Central Hospital',
                    'pathologist'              => 'Dr. OWONA ATANGANA, Marcel-René',
                    'pathologist_reg'          => 'CMR-PATH-0031',
                    'assistant'                => 'ABESSOLO NGONO, Pierre (Mortuary Technician)',
                    'external_examination'     => 'Adult male, appearing stated age of 73 years. Height 174 cm. Weight estimated 82 kg. Obese habitus. No decomposition. Skin: jaundice absent. No petechiae. No surgical scars other than old appendicectomy scar (RIF). No external traumatic injuries. Identification confirmed by wristband and next-of-kin.',
                    'internal_examination'     => [
                        'cardiovascular'  => 'Heart weight 540g (increased). Severe concentric left ventricular hypertrophy. Wall thickness 18mm (normal ≤12mm). Triple vessel coronary artery disease: LAD 90% stenosis with acute plaque rupture and overlying thrombus at mid-LAD. RCA 75% stenosis. LCx 60% stenosis. Acute anterior infarct: pale area 4×3cm anterior wall LV with haemorrhagic border. Aorta: grade III atherosclerosis.',
                        'respiratory'    => 'Lungs total weight 1,240g. Bilateral pulmonary oedema — frothy fluid on cut section. No consolidation. Mild emphysematous changes upper lobes bilaterally. Pleura: small bilateral haemorrhagic effusions (R: 80mL, L: 40mL).',
                        'abdominal'      => 'Liver 1,840g — mild hepatomegaly, nutmeg pattern on cut section consistent with chronic venous congestion. Spleen 240g — mild congestion. Kidneys R: 160g, L: 155g — finely granular cortical surfaces consistent with hypertensive nephrosclerosis. Bladder and bowel unremarkable.',
                        'neurological'   => 'Brain 1,380g. No haemorrhage. Mild generalised cerebral atrophy. Old lacunar infarcts periventricular (bilateral). No herniation. Dura intact.',
                        'musculoskeletal' => 'No fractures identified.',
                    ],
                    'organ_weights'            => ['heart_g' => 540, 'lungs_right_g' => 680, 'lungs_left_g' => 560, 'liver_g' => 1840, 'spleen_g' => 240, 'kidneys_right_g' => 160, 'kidneys_left_g' => 155, 'brain_g' => 1380],
                    'histology_samples'        => ['Left ventricle', 'LAD coronary artery (plaque + thrombus)', 'Right lung', 'Liver', 'Kidney (bilateral)'],
                    'toxicology_requested'     => false,
                    'toxicology_results'       => null,
                    'cause_of_death_a'         => 'Acute anterior myocardial infarction — LAD territory',
                    'cause_of_death_b'         => 'Due to: Acute coronary syndrome — plaque rupture mid-LAD',
                    'cause_of_death_c'         => 'Due to: Triple vessel coronary artery disease',
                    'manner_of_death'          => 'Natural',
                    'pathologist_opinion'      => 'Death was due to acute anterior myocardial infarction arising from plaque rupture of the left anterior descending coronary artery on a background of severe triple vessel coronary artery disease, hypertensive heart disease, and Type 2 diabetes mellitus. The cardiac pathology is consistent with the clinical presentation and death was due to natural causes.',
                    'significant_negative_findings' => ['No pulmonary embolism', 'No aortic dissection', 'No intracranial haemorrhage', 'No malignancy identified on gross examination'],
                ];

            case 'partograph':
                return [
                    'gravida'              => 'G3P2',
                    'labour_onset'         => 'Spontaneous',
                    'induction_method'     => null,
                    'membranes'            => 'Ruptured spontaneously',
                    'liquor_colour'        => 'Clear',
                    'presentation'         => 'Cephalic',
                    'active_phase_start'   => '07 June 2026, 00:15 WAT',
                    'observations'         => [
                        ['time' => '00:15', 'fhr_bpm' => 148, 'contractions_in_10min' => 2, 'contraction_duration_sec' => 25, 'cervix_cm' => 4, 'descent_fifths' => 5, 'liquor' => 'C', 'moulding' => '0', 'oxytocin_units' => null, 'bp' => '118/74', 'pulse' => '82', 'temp' => '36.8', 'urine_vol_ml' => 200, 'urine_protein' => 'Nil'],
                        ['time' => '02:15', 'fhr_bpm' => 152, 'contractions_in_10min' => 3, 'contraction_duration_sec' => 35, 'cervix_cm' => 6, 'descent_fifths' => 4, 'liquor' => 'C', 'moulding' => '0', 'oxytocin_units' => null, 'bp' => '120/76', 'pulse' => '86', 'temp' => '37.0', 'urine_vol_ml' => null, 'urine_protein' => null],
                        ['time' => '04:15', 'fhr_bpm' => 156, 'contractions_in_10min' => 4, 'contraction_duration_sec' => 40, 'cervix_cm' => 8, 'descent_fifths' => 3, 'liquor' => 'C', 'moulding' => '+', 'oxytocin_units' => null, 'bp' => '122/78', 'pulse' => '90', 'temp' => '37.1', 'urine_vol_ml' => null, 'urine_protein' => null],
                        ['time' => '05:45', 'fhr_bpm' => 154, 'contractions_in_10min' => 5, 'contraction_duration_sec' => 45, 'cervix_cm' => 10, 'descent_fifths' => 1, 'liquor' => 'C', 'moulding' => '+', 'oxytocin_units' => null, 'bp' => '124/80', 'pulse' => '94', 'temp' => null, 'urine_vol_ml' => null, 'urine_protein' => null],
                    ],
                    'alert_line_crossed'   => false,
                    'action_line_crossed'  => false,
                    'delivery_time'        => '07 June 2026, 06:18 WAT',
                    'delivery_type'        => 'Normal Vaginal Delivery',
                    'delivery_reason'      => null,
                    'apgar_1min'           => 8,
                    'apgar_5min'           => 9,
                    'birth_weight_kg'      => 3.42,
                    'baby_sex'             => 'Female',
                    'placenta_delivery_time' => '06:34 WAT',
                    'placenta_complete'    => true,
                    'blood_loss_ml'        => 250,
                    'episiotomy'           => false,
                    'perineal_tears'       => '1st degree',
                    'complications'        => [],
                    'midwife'              => 'Sr. NGONO BIYA, Celestine',
                    'obstetrician'         => 'Dr. ABANDA BELLA, Lydie Suzanne',
                ];

            case 'newborn-assessment':
                return [
                    'birth_datetime'       => '07 June 2026, 06:18 WAT',
                    'birth_type'           => 'Vaginal',
                    'gestational_age_weeks'=> 39,
                    'birth_weight_kg'      => 3.42,
                    'birth_length_cm'      => 51,
                    'head_circumference_cm'=> 34,
                    'chest_circumference_cm' => 33,
                    'apgar'                => ['score_1min' => 8, 'score_5min' => 9, 'score_10min' => null, 'hr_1min' => 2, 'resp_1min' => 2, 'colour_1min' => 1, 'tone_1min' => 2, 'reflex_1min' => 1, 'hr_5min' => 2, 'resp_5min' => 2, 'colour_5min' => 2, 'tone_5min' => 2, 'reflex_5min' => 1],
                    'resuscitation_required' => false,
                    'resuscitation_details'  => null,
                    'physical_examination'  => [
                        'general'    => 'Well-formed term neonate. Good tone and cry.',
                        'head'       => 'Normocephalic. Mild caput succedaneum — resolving.',
                        'fontanelle' => 'Anterior fontanelle soft, flat, normotensive.',
                        'eyes'       => 'Red reflex present bilaterally. Sclera white.',
                        'ears'       => 'Normal auricles. Good ear recoil.',
                        'nose'       => 'Patent nares bilaterally.',
                        'mouth'      => 'Intact palate. No cleft. Good sucking on pacifier.',
                        'neck'       => 'Supple. No masses.',
                        'chest'      => 'Symmetric. Good air entry bilaterally. No retractions.',
                        'heart'      => 'Regular rate and rhythm. No murmurs. Good femoral pulses bilaterally.',
                        'lungs'      => 'Clear to auscultation.',
                        'abdomen'    => 'Soft, non-distended. Cord intact ×3 vessels. Liver at costal margin.',
                        'spine'      => 'No sacral dimple. Spine intact.',
                        'genitalia'  => 'Female — normal external genitalia.',
                        'limbs'      => 'All 10 fingers and toes present. Good limb tone. No fractures. Hip examination: Barlow and Ortolani negative.',
                        'skin'       => 'Pink, warm, well-perfused. No rash. Mild vernix present.',
                        'reflexes'   => ['moro' => 'Present — symmetric', 'rooting' => 'Present', 'sucking' => 'Strong', 'grasp' => 'Present bilaterally', 'plantar' => 'Up-going (normal)'],
                    ],
                    'congenital_anomalies' => ['None detected'],
                    'blood_group'          => 'B Positive (B+)',
                    'dcst'                 => 'Negative',
                    'glucose_mmol'         => 3.8,
                    'temperature'          => '36.8°C',
                    'prophylaxis'          => [
                        ['intervention' => 'Vitamin K 1mg IM', 'given' => true, 'time' => '06:35'],
                        ['intervention' => 'Hepatitis B Vaccine (birth dose)', 'given' => true, 'time' => '06:40'],
                        ['intervention' => 'BCG Vaccine 0.05mL intradermal', 'given' => true, 'time' => '06:42'],
                        ['intervention' => 'Tetracycline Eye Ointment', 'given' => true, 'time' => '06:38'],
                    ],
                    'feeding'              => 'Breastfeeding initiated',
                    'classification'       => 'Appropriate for Gestational Age (AGA)',
                    'nicu_required'        => false,
                    'examining_pediatrician' => 'Dr. ABANDA BELLA, Lydie Suzanne',
                ];

            case 'child-health-card':
                return [
                    'child_name'            => 'NJOMO, Chloé Beatrice',
                    'child_dob'             => '07 June 2026',
                    'child_sex'             => 'Female',
                    'birth_weight_kg'       => 3.42,
                    'birth_order'           => '3rd',
                    'mother_name'           => 'NJOMO EKAMBI, Marie Claire',
                    'father_name'           => 'EKAMBI BILONG, Jean-Baptiste Honoré',
                    'village_quarter'       => 'Bonanjo, Douala',
                    'health_centre'         => 'OpesCare Central General Hospital',
                    'growth_visits'         => [
                        ['date' => '07 Jun 2026', 'age_months' => 0, 'weight_kg' => 3.42, 'height_cm' => 51.0, 'muac_cm' => null, 'nutritional_status' => 'Normal', 'feeding' => 'Breastfeeding', 'notes' => 'Birth visit. Vaccines given.'],
                    ],
                    'immunizations'         => [
                        ['vaccine' => 'BCG', 'age_given' => 'Birth', 'date_given' => '07 Jun 2026', 'batch' => 'BCG-2026-01', 'site' => 'Left arm ID', 'given_by' => 'Sr. NGONO BIYA', 'next_due' => '—'],
                        ['vaccine' => 'Hepatitis B (birth dose)', 'age_given' => 'Birth', 'date_given' => '07 Jun 2026', 'batch' => 'HBV-2026-03', 'site' => 'Right thigh IM', 'given_by' => 'Sr. NGONO BIYA', 'next_due' => '6 weeks'],
                        ['vaccine' => 'OPV0', 'age_given' => 'Birth', 'date_given' => '07 Jun 2026', 'batch' => 'OPV-2026-07', 'site' => 'Oral', 'given_by' => 'Sr. NGONO BIYA', 'next_due' => '6 weeks'],
                    ],
                    'vitamin_a_doses'       => [],
                    'deworming'             => [],
                    'illnesses'             => [],
                    'nutrition_counselling' => ['07 Jun 2026 — breastfeeding initiation counselling'],
                    'exclusive_breastfeeding_months' => 6,
                    'complementary_feeding_started' => null,
                ];

            case 'dialysis-record':
                return [
                    'session_number'         => 'Session 47',
                    'modality'               => 'Haemodialysis',
                    'session_date'           => '07 June 2026',
                    'session_start'          => '08:00',
                    'session_end'            => '12:00',
                    'duration_hours'         => 4,
                    'access_type'            => 'AV Fistula — Left Forearm (radiocephalic)',
                    'access_condition'       => 'Good flow. Bruit and thrill present. No signs of infection or haematoma.',
                    'pre_weight_kg'          => 71.8,
                    'post_weight_kg'         => 69.2,
                    'dry_weight_kg'          => 69.0,
                    'ultrafiltration_target_ml' => 2800,
                    'ultrafiltration_achieved_ml' => 2600,
                    'dialyser'               => 'Fresenius FX60 — High-flux polysulfone',
                    'blood_flow_ml_min'      => 300,
                    'dialysate_flow_ml_min'  => 500,
                    'dialysate_temp'         => '37.0°C',
                    'anticoagulation'        => ['drug' => 'Heparin', 'loading_dose' => '2000 IU', 'maintenance' => '1000 IU/hr', 'total_dose' => '5000 IU'],
                    'pre_vitals'             => ['bp' => '162/98', 'pulse' => '82', 'temp' => '36.8°C', 'spo2' => '97%'],
                    'intra_vitals'           => [
                        ['time' => '09:00', 'bp' => '154/92', 'pulse' => '80', 'spo2' => '98%'],
                        ['time' => '10:00', 'bp' => '148/88', 'pulse' => '78', 'spo2' => '98%'],
                        ['time' => '11:00', 'bp' => '142/84', 'pulse' => '76', 'spo2' => '99%'],
                    ],
                    'post_vitals'            => ['bp' => '138/82', 'pulse' => '74', 'temp' => '36.9°C', 'spo2' => '99%'],
                    'pre_labs'               => ['bun_mmol' => 22.4, 'creatinine' => 8.8, 'potassium' => 5.4, 'bicarbonate' => 18],
                    'post_labs'              => ['bun_mmol' => 8.1, 'creatinine' => 3.2, 'potassium' => 3.8],
                    'kt_v'                   => 1.42,
                    'complications'          => ['Mild cramps at 3.5 hours — resolved with saline bolus 100mL'],
                    'medications_given'      => [['drug' => 'Epoietin Alfa', 'dose' => '4000 IU', 'route' => 'SC', 'time' => '08:10']],
                    'nursing_notes'          => 'Session completed without major complications. Adequate UF achieved. Kt/V 1.42 — above adequacy target. Patient tolerated session well. Fistula site clean. No access complications.',
                    'dialysis_nurse'         => 'Sr. EYINGA MBIDA, Jeanne',
                    'nephrologist'           => 'Prof. TABI NDIP, Charles Etienne',
                ];

            case 'chemotherapy-record':
                return [
                    'protocol_name'          => 'CHOP — Cycle 3 of 6',
                    'cancer_diagnosis'       => 'Diffuse Large B-Cell Lymphoma (DLBCL) — Stage III, IPI Score 3',
                    'icd10_code'             => 'C83.3',
                    'cycle_number'           => 3,
                    'total_cycles'           => 6,
                    'cycle_date'             => '07 June 2026',
                    'bsa_m2'                 => 1.72,
                    'weight_kg'              => 69.1,
                    'height_cm'              => 163,
                    'oncologist'             => 'Dr. ABANDA BELLA, Lydie Suzanne',
                    'chemo_nurse'            => 'Sr. NGONO BIYA, Celestine',
                    'pre_cycle_bloods'       => ['wbc' => '4.2', 'neutrophils' => '2.1', 'haemoglobin' => '11.8', 'platelets' => '142', 'creatinine' => '88', 'alt' => '28', 'ast' => '24'],
                    'go_nogo'                => 'GO',
                    'nogo_reason'            => null,
                    'dose_modification'      => null,
                    'pre_medications'        => [
                        ['drug' => 'Ondansetron', 'dose' => '8mg', 'route' => 'IV', 'time' => '09:00', 'purpose' => 'Antiemetic'],
                        ['drug' => 'Dexamethasone', 'dose' => '20mg', 'route' => 'IV', 'time' => '09:00', 'purpose' => 'Steroid'],
                        ['drug' => 'Chlorphenamine', 'dose' => '10mg', 'route' => 'IV', 'time' => '09:00', 'purpose' => 'Antihistamine'],
                        ['drug' => 'Normal Saline 0.9%', 'dose' => '500mL', 'route' => 'IV', 'time' => '09:10', 'purpose' => 'Hydration'],
                    ],
                    'chemotherapy_agents'    => [
                        ['drug' => 'Rituximab', 'dose_per_m2' => '375mg/m²', 'calculated_dose' => '645mg', 'volume_ml' => 300, 'diluent' => 'NS 0.9%', 'rate_ml_hr' => '50→400', 'infusion_start' => '09:30', 'infusion_end' => '11:30', 'administered_by' => 'Sr. NGONO BIYA'],
                        ['drug' => 'Cyclophosphamide', 'dose_per_m2' => '750mg/m²', 'calculated_dose' => '1290mg', 'volume_ml' => 250, 'diluent' => 'NS 0.9%', 'rate_ml_hr' => '250', 'infusion_start' => '11:45', 'infusion_end' => '12:45', 'administered_by' => 'Sr. NGONO BIYA'],
                        ['drug' => 'Doxorubicin', 'dose_per_m2' => '50mg/m²', 'calculated_dose' => '86mg', 'volume_ml' => 100, 'diluent' => 'D5W', 'rate_ml_hr' => '100', 'infusion_start' => '13:00', 'infusion_end' => '14:00', 'administered_by' => 'Sr. NGONO BIYA'],
                        ['drug' => 'Vincristine', 'dose_per_m2' => '1.4mg/m²', 'calculated_dose' => '2mg (max)', 'volume_ml' => 50, 'diluent' => 'NS 0.9%', 'rate_ml_hr' => '50', 'infusion_start' => '14:15', 'infusion_end' => '15:15', 'administered_by' => 'Sr. NGONO BIYA'],
                    ],
                    'post_medications'       => [['drug' => 'Metoclopramide', 'dose' => '10mg', 'route' => 'IV', 'time' => '15:30']],
                    'toxicities_previous_cycle' => [
                        ['toxicity' => 'Nausea/Vomiting', 'grade' => 2, 'ctcae_term' => 'Nausea'],
                        ['toxicity' => 'Fatigue', 'grade' => 1, 'ctcae_term' => 'Fatigue'],
                    ],
                    'patient_education'      => ['Take prednisone tablets (1mg/kg) days 1–5 at home', 'Monitor temperature — report fever > 38°C immediately', 'Avoid crowded places for 10 days post-chemotherapy', 'Maintain good oral hygiene — use soft toothbrush', 'Report any tingling or numbness in hands/feet'],
                    'next_cycle_date'        => '28 June 2026',
                    'growth_factor'          => 'G-CSF (Filgrastim 5mcg/kg SC) Days 5–12',
                    'complications_during'   => 'None',
                ];

            case 'echo-report':
                return [
                    'study_date'             => '07 June 2026',
                    'study_type'             => 'Transthoracic Echo (TTE)',
                    'indication'             => 'Hypertensive emergency — assess LV function and hypertensive heart disease',
                    'image_quality'          => 'Good',
                    'cardiologist'           => 'Dr. BELINGA NKOA, Thierry — CMR-CARD-0041',
                    'sonographer'            => 'MBARGA FOUDA, Samuel',
                    'lv_measurements'        => ['ivsd_mm' => 14, 'lvedd_mm' => 48, 'lvesd_mm' => 32, 'lvpwd_mm' => 13, 'ef_percent' => 58, 'fs_percent' => 33, 'lv_mass_g' => 248],
                    'lv_function'            => ['systolic' => 'Normal', 'ef_comment' => 'EF 58% — preserved systolic function. Concentric LVH present (IVSd 14mm).', 'wall_motion' => 'Normal'],
                    'wall_motion_abnormalities' => null,
                    'diastolic_function'     => ['grade' => 'Grade I', 'e_wave' => '0.72', 'a_wave' => '0.88', 'e_prime_lateral' => '8.2', 'e_e_prime_ratio' => '8.8'],
                    'valves'                 => [
                        'mitral'    => ['morphology' => 'Normal leaflets. No prolapse.', 'regurgitation' => 'Trivial', 'stenosis' => false, 'mvarea_cm2' => null, 'gradient_mmhg' => null],
                        'aortic'    => ['morphology' => 'Mildly thickened. Trileaflet.', 'regurgitation' => 'None', 'stenosis' => false, 'avarea_cm2' => null, 'gradient_mmhg' => null],
                        'tricuspid' => ['morphology' => 'Normal.', 'regurgitation' => 'Trivial'],
                        'pulmonary' => ['morphology' => 'Normal.', 'regurgitation' => 'None'],
                    ],
                    'right_heart'            => ['rv_size' => 'Normal', 'rv_function' => 'Normal', 'rvsp_mmhg' => 32, 'rai_size' => 'Normal'],
                    'aorta'                  => ['root_mm' => 34, 'ascending_mm' => 36],
                    'pericardium'            => 'Normal — no pericardial effusion',
                    'ivc'                    => 'IVC 1.8cm, collapsible > 50% — RA pressure estimated 5–10 mmHg',
                    'impression'             => 'Concentric left ventricular hypertrophy consistent with longstanding hypertensive heart disease. Preserved LV systolic function (EF 58%). Grade I diastolic dysfunction. Trivial mitral and tricuspid regurgitation. Mildly thickened aortic valve — not haemodynamically significant. No pericardial effusion.',
                    'recommendation'         => 'Optimise antihypertensive therapy. Repeat echocardiogram in 6 months to assess LVH regression. Consider aldosterone antagonist if LVH persists.',
                ];

            case 'endoscopy-report':
                return [
                    'procedure_type'         => 'Oesophagogastroduodenoscopy (OGD/Gastroscopy)',
                    'procedure_date'         => '07 June 2026',
                    'indication'             => 'Epigastric pain and nausea. Rule out peptic ulcer disease / H. pylori.',
                    'sedation'               => 'Conscious sedation (Midazolam 3mg + Fentanyl 50mcg IV)',
                    'endoscopist'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'endoscopist_reg'        => 'CMR-MD-0291',
                    'scope_used'             => 'Olympus GIF-H290 Gastroscope',
                    'consent_obtained'       => true,
                    'bowel_prep_quality'     => null,
                    'extent_of_examination'  => 'Complete — oesophagus, stomach (all regions), first and second part of duodenum visualised',
                    'findings'               => [
                        ['location' => 'Oesophagus', 'description' => 'Normal mucosa throughout. No Barrett\'s changes. No varices. Z-line normal.', 'impression' => 'Normal'],
                        ['location' => 'Gastric antrum', 'description' => 'Erythematous, nodular mucosa in the antrum consistent with gastritis. No ulceration.', 'impression' => 'Antral gastritis — likely H. pylori'],
                        ['location' => 'Gastric body/fundus', 'description' => 'Mild erythema. No polyps or masses.', 'impression' => 'Mild superficial gastritis'],
                        ['location' => 'Duodenum', 'description' => 'First and second parts normal. No ulceration or erosions.', 'impression' => 'Normal'],
                    ],
                    'biopsies'               => [
                        ['site' => 'Gastric antrum', 'number_of_pieces' => 2, 'sent_for' => 'H.pylori'],
                        ['site' => 'Gastric antrum', 'number_of_pieces' => 2, 'sent_for' => 'Histology'],
                    ],
                    'polypectomy'            => [],
                    'haemostasis'            => null,
                    'hp_rapid_urease_test'   => 'Positive',
                    'impression'             => 'Antral gastritis with H. pylori rapid urease test positive. Superficial gastritis of body and fundus. No peptic ulcer or malignancy on gross endoscopic appearances. Histology pending.',
                    'recommendations'        => ['H. pylori eradication therapy: Triple therapy — PPI + Amoxicillin + Clarithromycin × 14 days', 'Confirm eradication with urea breath test 4–8 weeks post-treatment', 'Await histology results (2–5 working days)', 'Repeat OGD if symptoms persist after eradication therapy'],
                    'complications'          => 'None',
                    'recovery_time_min'      => 30,
                ];

            case 'physio-report':
                return [
                    'referral_diagnosis'     => 'Post-stroke rehabilitation — right hemiparesis (6 weeks post-ischaemic stroke)',
                    'assessment_date'        => '07 June 2026',
                    'session_type'           => 'Initial Assessment',
                    'physiotherapist'        => 'ABENA MFOUNDI, Solange — CMR-PHY-0044',
                    'physiotherapist_reg'    => 'CMR-PHY-0044',
                    'referring_physician'    => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'presenting_complaints'  => 'Weakness of right arm and leg following left MCA ischaemic stroke 6 weeks ago. Unable to walk independently. Right hand grip significantly reduced. Difficulty with ADLs — unable to dress, bathe, or prepare food independently.',
                    'medical_history'        => 'Left MCA ischaemic stroke on 22 April 2026 — admitted to neurology. IV alteplase administered within window. Post-stroke right hemiparesis. Background hypertension, type 2 DM. On aspirin, atorvastatin, amlodipine, and metformin.',
                    'pain_assessment'        => ['location' => 'Right shoulder', 'severity_vas' => 4, 'character' => 'Aching, constant', 'aggravating' => 'Passive ROM, transfers', 'relieving' => 'Rest, heat'],
                    'functional_assessment'  => ['mobility' => 'Assisted', 'transfers' => 'Maximal assist × 2 persons', 'gait' => 'Non-ambulatory — transfers to wheelchair only', 'stairs' => 'Unable', 'adl' => 'Moderate-severe dependence'],
                    'objective_measures'     => [
                        ['measure' => 'Barthel Index', 'value' => '8/20', 'normal_value' => '20/20', 'interpretation' => 'Severe dependence'],
                        ['measure' => 'MRC Grip Strength (R)', 'value' => '2/5', 'normal_value' => '5/5', 'interpretation' => 'Active movement with gravity eliminated only'],
                        ['measure' => 'MRC Grip Strength (L)', 'value' => '5/5', 'normal_value' => '5/5', 'interpretation' => 'Normal'],
                        ['measure' => 'Berg Balance Scale', 'value' => '12/56', 'normal_value' => '> 45/56', 'interpretation' => 'High fall risk'],
                    ],
                    'posture_balance'        => 'Sitting balance: fair with trunk support. Standing: requires maximal assist. Significant right-side lean.',
                    'range_of_motion'        => [
                        ['joint' => 'Right shoulder', 'movement' => 'Flexion', 'active_degrees' => '60°', 'passive_degrees' => '120°', 'normal' => '180°'],
                        ['joint' => 'Right elbow',    'movement' => 'Extension', 'active_degrees' => '-20° (lag)', 'passive_degrees' => '0°', 'normal' => '0°'],
                        ['joint' => 'Right wrist',    'movement' => 'Extension', 'active_degrees' => '10°', 'passive_degrees' => '40°', 'normal' => '70°'],
                        ['joint' => 'Right hip',      'movement' => 'Flexion', 'active_degrees' => '80°', 'passive_degrees' => '110°', 'normal' => '120°'],
                        ['joint' => 'Right knee',     'movement' => 'Extension', 'active_degrees' => '-10° (lag)', 'passive_degrees' => '0°', 'normal' => '0°'],
                    ],
                    'muscle_strength'        => [
                        ['muscle_group' => 'Right deltoid', 'mrc_grade' => 2, 'side' => 'R'],
                        ['muscle_group' => 'Right biceps',  'mrc_grade' => 3, 'side' => 'R'],
                        ['muscle_group' => 'Right quad',    'mrc_grade' => 3, 'side' => 'R'],
                        ['muscle_group' => 'Right hamstring','mrc_grade' => 2, 'side' => 'R'],
                        ['muscle_group' => 'Right tibialis anterior', 'mrc_grade' => 1, 'side' => 'R'],
                    ],
                    'special_tests'          => [
                        ['test' => 'Fugl-Meyer Assessment', 'result' => '28/66 (UL) + 14/34 (LL)', 'interpretation' => 'Moderate motor impairment'],
                        ['test' => 'Modified Ashworth Scale', 'result' => 'Grade 1 right UL/LL', 'interpretation' => 'Mild spasticity'],
                    ],
                    'problems_identified'    => ['Right hemiplegia — moderate motor impairment', 'Right shoulder subluxation + pain', 'Severe functional dependence for ADLs', 'High fall risk', 'Spasticity right UL and LL — Grade 1'],
                    'short_term_goals'       => [
                        ['goal' => 'Sit to stand with minimum assist × 1', 'target_date' => '21 Jun 2026'],
                        ['goal' => 'Improve right shoulder active ROM to 90° flexion', 'target_date' => '21 Jun 2026'],
                    ],
                    'long_term_goals'        => [
                        ['goal' => 'Ambulate 10m with walking aid independently', 'target_date' => '07 Sep 2026'],
                        ['goal' => 'Independent basic ADLs (dressing/grooming)', 'target_date' => '07 Sep 2026'],
                    ],
                    'treatment_plan'         => [
                        ['intervention' => 'NDT/Bobath facilitation techniques', 'frequency' => 'Daily', 'duration' => '45 min'],
                        ['intervention' => 'Task-specific gait training — parallel bars', 'frequency' => 'Daily', 'duration' => '30 min'],
                        ['intervention' => 'Right shoulder positioning + sling', 'frequency' => 'Continuous', 'duration' => 'Ongoing'],
                        ['intervention' => 'Upper limb functional electrical stimulation (FES)', 'frequency' => 'Daily', 'duration' => '20 min'],
                    ],
                    'session_notes'          => [
                        ['date' => '07 Jun 2026', 'treatment_given' => 'Initial assessment. Passive ROM exercises, positioning education. Sling fitted.', 'patient_response' => 'Cooperative. Pain 4/10 on shoulder movement.', 'progress' => 'Assessment only — baseline established.'],
                    ],
                    'home_exercise_programme'=> ['Right shoulder pendulum exercises × 10 twice daily', 'Supine hip flexion exercises × 10 three times daily', 'Seated ankle pumps × 20 hourly to prevent DVT'],
                    'equipment_prescribed'   => ['Right shoulder sling', 'Wheelchair (standard with right arm support)'],
                    'next_review'            => '14 June 2026',
                    'prognosis'              => 'Fair',
                ];

            case 'medication-reconciliation':
                return [
                    'reconciliation_type'    => 'Discharge',
                    'reconciliation_date'    => '07 June 2026',
                    'source_of_information'  => ['Patient interview', 'GP referral letter', 'Repeat prescription from purse'],
                    'information_reliability'=> 'Reliable',
                    'medications'            => [
                        ['drug_name' => 'Amlodipine', 'dose' => '5mg', 'frequency' => 'Once daily', 'route' => 'Oral', 'indication' => 'Hypertension', 'home_status' => 'Taking as prescribed', 'admission_decision' => 'Dose change', 'discharge_decision' => 'Continue', 'reason_for_change' => 'Dose increased to 10mg — BP inadequately controlled on 5mg', 'high_alert' => false],
                        ['drug_name' => 'Losartan', 'dose' => '50mg', 'frequency' => 'Once daily', 'route' => 'Oral', 'indication' => 'Hypertension + Renoprotection', 'home_status' => 'Not taking', 'admission_decision' => 'New', 'discharge_decision' => 'Continue', 'reason_for_change' => 'Added for dual blockade and CKD renoprotection', 'high_alert' => false],
                        ['drug_name' => 'Hydrochlorothiazide', 'dose' => '12.5mg', 'frequency' => 'Once daily', 'route' => 'Oral', 'indication' => 'Hypertension', 'home_status' => 'Unknown', 'admission_decision' => 'New', 'discharge_decision' => 'Continue', 'reason_for_change' => 'Added as third-line agent for BP control', 'high_alert' => false],
                        ['drug_name' => 'Atorvastatin', 'dose' => '40mg', 'frequency' => 'Once daily (evening)', 'route' => 'Oral', 'indication' => 'Dyslipidaemia — LDL 4.1 mmol/L', 'home_status' => 'Not taking', 'admission_decision' => 'New', 'discharge_decision' => 'Continue', 'reason_for_change' => 'Statin indicated — LDL above target, high CV risk', 'high_alert' => false],
                        ['drug_name' => 'Metformin', 'dose' => '500mg', 'frequency' => 'Twice daily', 'route' => 'Oral', 'indication' => 'Type 2 Diabetes', 'home_status' => 'Taking as prescribed', 'admission_decision' => 'Withhold temporarily', 'discharge_decision' => 'Continue', 'reason_for_change' => 'Withheld during admission due to IV contrast and AKI risk. Restarted on discharge as eGFR > 30.', 'high_alert' => false],
                    ],
                    'new_medications_added'  => ['Amlodipine 10mg', 'Losartan 50mg', 'Hydrochlorothiazide 12.5mg', 'Atorvastatin 40mg'],
                    'allergies_confirmed'    => [['allergen' => 'Penicillin', 'reaction' => 'Anaphylaxis', 'severity' => 'Severe']],
                    'patient_counselled'     => true,
                    'counselling_notes'      => 'Patient counselled on all 5 medications. Importance of compliance emphasised. Allergy to penicillin confirmed and documented.',
                    'gp_informed'            => false,
                    'pharmacist'             => 'BELLO HAMIDOU, Fatimatou — CMR-PHARM-0018',
                    'prescriber'             => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                ];

            case 'incident-report':
                return [
                    'incident_date'          => '07 June 2026',
                    'incident_time'          => '02:15 WAT',
                    'incident_type'          => 'Patient Fall',
                    'incident_severity'      => 'Minor Harm',
                    'location'               => 'Internal Medicine — Ward 3B, Bed 12 (Bedside)',
                    'description'            => 'Patient found on floor beside bed at approximately 02:15 WAT during routine night rounds. Patient reports she attempted to get up independently to use the toilet without calling for assistance, slipped on the linoleum floor, and fell onto her right side. No loss of consciousness. Patient alert on finding. Complaining of pain right hip and right wrist. Call bell was within reach but not used.',
                    'immediate_actions_taken'=> ['Nurse immediately assessed patient on floor', 'Patient assisted back to bed — required 2 staff', 'Vital signs checked — stable (BP 148/90, HR 82, SpO2 98%)', 'Right hip and wrist examined — no obvious deformity, bruising noted right wrist', 'X-ray right hip and wrist ordered', 'Physician Dr. MBASSI ATEBA notified at 02:20 WAT', 'Patient re-oriented to fall prevention protocol', 'Bed rails raised. Anti-slip mat placed.'],
                    'patient_outcome'        => 'Minor soft tissue injury — bruising right wrist. X-ray: no fracture confirmed. Patient comfortable by 03:00.',
                    'patient_informed'       => true,
                    'family_informed'        => true,
                    'staff_involved'         => [
                        ['name' => 'Sr. NGONO BIYA, Celestine', 'designation' => 'Registered Nurse', 'role_in_incident' => 'Found patient. Immediate responder.'],
                        ['name' => 'Sr. EYINGA MBIDA, Jeanne', 'designation' => 'Enrolled Nurse', 'role_in_incident' => 'Assisted with patient transfer back to bed.'],
                    ],
                    'witnesses'              => [],
                    'root_cause_category'    => 'Patient factors',
                    'contributing_factors'   => ['Patient did not use call bell despite education', 'Night lighting reduced (patient preference — noted in care plan)', 'Antihypertensive medications may have contributed to postural hypotension'],
                    'preventable'            => true,
                    'corrective_actions'     => [
                        ['action' => 'Re-educate patient on fall prevention and call bell use', 'responsible_person' => 'Ward nurse (AM shift)', 'target_date' => '07 Jun 2026', 'status' => 'Completed'],
                        ['action' => 'Apply falls risk sticker to bed and medication chart', 'responsible_person' => 'Sr. NGONO BIYA', 'target_date' => '07 Jun 2026', 'status' => 'Completed'],
                        ['action' => 'Review anti-slip mat provision for ward 3B beds', 'responsible_person' => 'Ward Manager', 'target_date' => '14 Jun 2026', 'status' => 'In progress'],
                    ],
                    'reported_by'            => 'Sr. NGONO BIYA, Celestine — Registered Nurse',
                    'reported_to'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre — Attending Physician',
                    'report_datetime'        => '07 June 2026, 02:30 WAT',
                    'quality_review_required'=> false,
                ];

            case 'wound-care-chart':
                return [
                    'wound_type'             => 'Surgical Wound',
                    'wound_location'         => 'Right iliac fossa — Robinson drain exit site (post-laparoscopic appendicectomy)',
                    'wound_onset_date'       => '06 June 2026',
                    'assessments'            => [
                        ['date' => '07 Jun 2026', 'time' => '08:00', 'length_cm' => 1.5, 'width_cm' => 0.8, 'depth_cm' => 0.3, 'tissue_type' => 'Granulating', 'exudate_amount' => 'Low', 'exudate_type' => 'Serosanguineous', 'wound_edges' => 'Well-defined', 'periwound_skin' => 'Mildly erythematous — consistent with post-surgical reaction', 'odour' => 'None', 'pain_score' => 3, 'dressing_removed' => 'Melolin non-adherent + gauze', 'dressing_applied' => 'Aquacel Ag + foam cover', 'next_change_days' => 2, 'done_by' => 'Sr. NGONO BIYA', 'notes' => 'Day 1 post-op. Wound clean. Drain output 35mL serosanguineous.'],
                    ],
                    'current_wound_dimensions' => ['length_cm' => 1.5, 'width_cm' => 0.8, 'depth_cm' => 0.3],
                    'wound_swab_sent'        => false,
                    'wound_swab_result'      => null,
                    'referrals'              => [],
                    'nutrition_support'      => true,
                    'nutrition_notes'        => 'High protein diet. Encourage oral intake. Vitamin C supplementation commenced.',
                    'wound_care_nurse'       => 'Sr. NGONO BIYA, Celestine',
                ];

            case 'postnatal-record':
                return [
                    'delivery_date'          => '07 June 2026',
                    'delivery_type'          => 'NVD',
                    'baby_sex'               => 'Female',
                    'birth_weight_kg'        => 3.42,
                    'pnc_visits'             => [
                        [
                            'visit_number'              => 1,
                            'visit_date'                => '07 June 2026',
                            'days_postpartum'           => 0,
                            'maternal_vitals'           => ['bp' => '120/76', 'pulse' => '82', 'temp' => '36.9°C'],
                            'uterine_involution'        => 'Well involuted — uterus at umbilicus',
                            'lochia'                    => 'Rubra',
                            'perineal_wound'            => 'Healing well — 1st degree tear repaired',
                            'cs_wound'                  => 'N/A',
                            'breast_condition'          => 'Colostrum expressed. Latch attempted — good.',
                            'breastfeeding'             => 'Exclusive',
                            'depression_screen'         => 'Not done',
                            'baby_weight_kg'            => 3.42,
                            'baby_condition'            => 'Alert, breastfeeding well, temperature 36.8°C.',
                            'family_planning_discussed' => true,
                            'fp_method_chosen'          => 'Deferred — to be discussed at 6-week visit',
                            'immunizations_given'       => ['BCG', 'HBV birth dose', 'OPV0'],
                            'problems'                  => [],
                            'management'                => ['Vitamin A 200,000IU to mother', 'Iron + folic acid continued'],
                            'next_visit_date'           => '14 June 2026 (Day 7)',
                            'seen_by'                   => 'Sr. NGONO BIYA, Celestine',
                        ],
                    ],
                    'discharge_advice_given' => ['Continue exclusive breastfeeding for 6 months', 'Report any fever > 38°C, heavy bleeding, or foul lochia immediately', 'Perineal hygiene — warm water rinse after each void', 'Attend Day 7 PNC visit on 14 June 2026', 'Family planning counselling at 6-week visit'],
                ];

            case 'referral-acknowledgement':
                return [
                    'original_referral_number'   => 'OC-REF-2026-022184',
                    'original_referral_date'     => '07 June 2026',
                    'referring_doctor'           => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'referring_facility'         => 'OpesCare Central General Hospital',
                    'referring_specialty'        => 'Internal Medicine',
                    'patient_received_date'      => '07 June 2026',
                    'patient_received_time'      => '17:30 WAT',
                    'specialist'                 => 'Prof. TABI NDIP, Charles Etienne',
                    'specialty'                  => 'Nephrology & Hypertension',
                    'assessment_summary'         => 'Ms. NJOMO EKAMBI was reviewed in the Nephrology outpatient clinic on 07 June 2026. Full history, examination, and review of the transferred investigation results were completed. The clinical picture is consistent with hypertensive nephropathy with CKD Stage 3a (eGFR 54 mL/min/1.73m²) and significant proteinuria (2+ on dipstick, confirmed on urine ACR 45mg/mmol). Blood pressure remains suboptimally controlled at 148/92 mmHg on triple therapy.',
                    'specialist_diagnosis'       => [
                        ['diagnosis' => 'Hypertensive Nephropathy — CKD Stage 3a', 'icd10' => 'I12.9 / N18.3'],
                        ['diagnosis' => 'Nephrotic-range proteinuria — under investigation', 'icd10' => 'N04.9'],
                    ],
                    'management_plan'            => ['Increase Losartan to 100mg OD for additional renoprotection', 'Add spironolactone 25mg OD — for residual proteinuria reduction', '24-hour urine protein collection ordered', 'Renal biopsy to be considered if proteinuria > 1g/24h — patient counselled', 'Repeat eGFR and serum potassium in 2 weeks after medication changes'],
                    'investigations_requested'   => ['24-hour urine protein', 'Urine albumin:creatinine ratio (ACR)', 'Renal immunology panel (ANA, ANCA, complement)', 'Renal Doppler ultrasound — assess for renovascular disease'],
                    'medications_changed'        => [
                        ['change' => 'Losartan increased from 50mg to 100mg OD', 'reason' => 'Inadequate BP control and renoprotection at 50mg'],
                        ['change' => 'Spironolactone 25mg OD added', 'reason' => 'Persistent proteinuria despite ACE/ARB — CKD renoprotection'],
                    ],
                    'follow_up_plan'             => 'Nephrology Clinic review in 2 weeks (21 June 2026) with results of 24-hour urine protein and repeat renal function.',
                    'shared_care_recommendations'=> ['Monitor serum potassium in 1 week — risk of hyperkalaemia with Losartan 100mg + Spironolactone', 'Continue BP monitoring with target BP < 130/80 mmHg', 'Maintain low sodium diet < 2g Na/day', 'Ensure annual urine ACR and eGFR monitoring if stable'],
                    'urgent_concerns'            => 'Please check serum potassium urgently within 7 days of medication change due to risk of hyperkalaemia with dual RAAS blockade + spironolactone.',
                    'thank_you_note'             => 'Thank you for this prompt referral. We will continue to keep you updated on Ms. NJOMO\'s progress and look forward to working with you in her ongoing management.',
                ];

            case 'admission-form':
                return [
                    'admission_date'         => '05 June 2026',
                    'admission_time'         => '22:47 WAT',
                    'admission_type'         => 'Emergency',
                    'admitting_ward'         => 'Internal Medicine — Ward 3B',
                    'admitting_doctor'       => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'admitting_diagnosis'    => 'Hypertensive Emergency — BP 210/130 mmHg',
                    'patient_details'        => ['full_name' => 'NJOMO EKAMBI, Marie Claire', 'dob' => '12 March 1985', 'age' => '41 years', 'sex' => 'Female', 'marital_status' => 'Married', 'nationality' => 'Cameroonian', 'occupation' => 'Civil Servant — MINSANTE', 'phone' => '+237 677 441 829', 'address' => 'Bonanjo Quarter, Douala, Wouri Division', 'religion' => 'Christian (Catholic)', 'language_preference' => 'French', 'id_type' => 'National ID', 'id_number' => 'CMR-ID-1985-22971'],
                    'next_of_kin'            => [
                        ['name' => 'EKAMBI BILONG, Jean-Baptiste', 'relationship' => 'Husband', 'phone' => '+237 677 441 829', 'address' => 'Same as patient', 'is_primary' => true],
                        ['name' => 'NJOMO, Christiane Marcelle', 'relationship' => 'Sister', 'phone' => '+237 699 112 834', 'address' => 'Bastos, Yaoundé', 'is_primary' => false],
                    ],
                    'insurance'              => ['has_insurance' => true, 'insurer' => 'CNPS Health Insurance', 'policy_number' => 'CNPS-2024-00987-B', 'employer' => 'MINSANTE', 'authorization_number' => null],
                    'allergies'              => [['allergen' => 'Penicillin', 'reaction' => 'Anaphylaxis'], ['allergen' => 'NSAIDs', 'reaction' => 'Relative contraindication — renal impairment']],
                    'presenting_complaint'   => 'Severe headache, visual blurring, nausea — hypertensive crisis',
                    'consent_to_treat'       => true,
                    'consent_to_photograph'  => false,
                    'consent_to_teaching'    => true,
                    'patient_rights_explained' => true,
                    'advance_directive'      => 'None',
                    'valuables_deposited'    => true,
                    'valuables_description'  => 'Gold wedding ring (1), Mobile phone (1), Handbag with wallet (1)',
                    'admitting_clerk'        => 'BELLO HAMIDOU, Fatimatou — Reception Desk',
                ];

            case 'pharmacy-record':
                return [
                    'dispensing_date'        => '07 June 2026',
                    'dispensing_type'        => 'Discharge Medications',
                    'prescription_ref'       => 'OC-RX-2026-088201',
                    'prescriber'             => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'prescriber_reg'         => 'CMR-MD-0291',
                    'items'                  => [
                        ['drug_name' => 'Amlodipine', 'generic_name' => 'Amlodipine Besylate', 'strength' => '10mg', 'form' => 'Tablet', 'quantity_dispensed' => 90, 'unit' => 'Tablets', 'batch_number' => 'AML-2026-0441', 'expiry_date' => 'Dec 2027', 'manufacturer' => 'Laborex Cameroon', 'storage_condition' => 'Room temp', 'patient_counselling' => 'Take once daily morning. Do not crush. May cause ankle swelling.', 'high_alert' => false, 'controlled_substance' => false, 'serial_number' => null],
                        ['drug_name' => 'Losartan Potassium', 'generic_name' => 'Losartan Potassium', 'strength' => '50mg', 'form' => 'Tablet', 'quantity_dispensed' => 90, 'unit' => 'Tablets', 'batch_number' => 'LOS-2026-0312', 'expiry_date' => 'Aug 2027', 'manufacturer' => 'Pfizer Inc', 'storage_condition' => 'Room temp', 'patient_counselling' => 'Take once daily evening. Monitor serum potassium. Avoid NSAIDS.', 'high_alert' => false, 'controlled_substance' => false, 'serial_number' => null],
                        ['drug_name' => 'Hydrochlorothiazide', 'generic_name' => 'Hydrochlorothiazide', 'strength' => '12.5mg', 'form' => 'Tablet', 'quantity_dispensed' => 90, 'unit' => 'Tablets', 'batch_number' => 'HCT-2026-0819', 'expiry_date' => 'Jun 2027', 'manufacturer' => 'Roussel SA', 'storage_condition' => 'Room temp', 'patient_counselling' => 'Take once daily morning with food. Monitor electrolytes.', 'high_alert' => false, 'controlled_substance' => false, 'serial_number' => null],
                        ['drug_name' => 'Atorvastatin', 'generic_name' => 'Atorvastatin Calcium', 'strength' => '40mg', 'form' => 'Tablet', 'quantity_dispensed' => 90, 'unit' => 'Tablets', 'batch_number' => 'ATV-2026-1104', 'expiry_date' => 'Mar 2028', 'manufacturer' => 'Pfizer Inc', 'storage_condition' => 'Room temp', 'patient_counselling' => 'Take once daily at night. Report muscle pain or weakness.', 'high_alert' => false, 'controlled_substance' => false, 'serial_number' => null],
                    ],
                    'total_items'            => 4,
                    'controlled_items_count' => 0,
                    'patient_counselled'     => true,
                    'counselling_language'   => 'French',
                    'patient_understood'     => true,
                    'dispensed_by'           => 'BELLO HAMIDOU, Fatimatou',
                    'dispensed_by_reg'       => 'CMR-PHARM-0018',
                    'checked_by'             => 'ABESSOLO NGONO, Pierre (Pharm. Tech.)',
                    'collection_by'          => 'Patient',
                    'representative_name'    => null,
                    'representative_id'      => null,
                ];

            case 'adr-report':
                return [
                    'reaction_date'          => '07 June 2026',
                    'report_date'            => '07 June 2026',
                    'suspect_drug'           => 'Hydrochlorothiazide 12.5mg',
                    'suspect_drug_dose'      => '12.5mg OD',
                    'suspect_drug_route'     => 'Oral',
                    'suspect_drug_indication'=> 'Hypertension (third-line antihypertensive)',
                    'suspect_drug_start_date'=> '07 June 2026 (discharge)',
                    'suspect_drug_stop_date' => null,
                    'reaction_description'   => 'Patient reports development of dry non-productive cough and hyponatraemia (Na+ 131 mmol/L, down from 138 mmol/L at admission) detected on day 3 post-discharge. Cough appeared within 24 hours of starting hydrochlorothiazide. Hyponatraemia confirmed on repeat sample. No other new medications. No respiratory infection. Symptoms improved after drug withdrawal.',
                    'reaction_start_date'    => '08 June 2026',
                    'reaction_onset_after'   => '24 hours after first dose',
                    'reaction_type'          => 'Type A (Augmented — dose-related)',
                    'reaction_severity'      => 'Moderate',
                    'outcome'                => 'Recovered fully',
                    'action_taken'           => 'Drug withdrawn',
                    'rechallenge'            => 'Not done',
                    'dechallenge'            => 'Improved on withdrawal',
                    'causality_who_umc'      => 'Probable/Likely',
                    'concomitant_drugs'      => ['Amlodipine 10mg OD', 'Losartan 50mg OD', 'Atorvastatin 40mg nocte'],
                    'nafdac_reported'        => false,
                    'nafdac_ref'             => null,
                    'minsante_reported'      => false,
                    'patient_known_allergy'  => false,
                    'preventable'            => false,
                    'prevention_notes'       => 'Electrolyte monitoring on day 3–7 recommended when starting thiazide diuretics — particularly in elderly and CKD patients.',
                    'reporter'               => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'reporter_designation'   => 'Attending Physician — Internal Medicine',
                ];

            case 'growth-chart':
                return [
                    'child_name'             => 'NJOMO, Chloé Beatrice',
                    'child_dob'              => '07 June 2026',
                    'child_sex'              => 'Female',
                    'mother_name'            => 'NJOMO EKAMBI, Marie Claire',
                    'measurements'           => [
                        ['date' => '07 Jun 2026', 'age_months' => 0, 'weight_kg' => 3.42, 'height_cm' => 51.0, 'muac_cm' => null, 'bmi' => null, 'oedema' => false, 'nutritional_status' => 'Normal', 'notes' => 'Birth measurements.'],
                    ],
                    'latest_weight_kg'       => 3.42,
                    'latest_height_cm'       => 51.0,
                    'latest_age_months'      => 0,
                    'latest_muac_cm'         => null,
                    'weight_for_age_z'       => '0.2 SD (Normal)',
                    'height_for_age_z'       => '0.1 SD (Normal)',
                    'weight_for_height_z'    => '0.3 SD (Normal)',
                    'nutritional_classification' => 'Well Nourished',
                    'oedema_present'         => false,
                    'interventions'          => [],
                    'referred_to_nutrition'  => false,
                    'therapeutic_feeding'    => null,
                    'next_measurement_date'  => '14 June 2026 (Day 7 check)',
                    'health_worker'          => 'Sr. NGONO BIYA, Celestine',
                ];

            // ── Batch A — Core Inpatient ─────────────────────────────────────────
            case 'medication-administration-record':
                return [
                    'ward'                => 'Internal Medicine — Ward 3B',
                    'bed_number'          => 'BED-3B-12',
                    'allergies'           => 'Penicillin (anaphylaxis)',
                    'weight_kg'           => 68,
                    'scheduled_times'     => ['06:00','10:00','14:00','18:00','22:00'],
                    'medications'         => [
                        ['name'=>'Amlodipine 10mg','route'=>'PO','dose'=>'10mg','frequency'=>'OD','06:00'=>'✓','10:00'=>'-','14:00'=>'-','18:00'=>'-','22:00'=>'-','notes'=>'Given 06:15 — NJOMO'],
                        ['name'=>'Losartan 50mg','route'=>'PO','dose'=>'50mg','frequency'=>'OD','06:00'=>'-','10:00'=>'-','14:00'=>'-','18:00'=>'✓','22:00'=>'-','notes'=>'Given 18:10 — NJOMO'],
                        ['name'=>'Atorvastatin 40mg','route'=>'PO','dose'=>'40mg','frequency'=>'OD (night)','06:00'=>'-','10:00'=>'-','14:00'=>'-','18:00'=>'-','22:00'=>'✓','notes'=>'Given 22:05'],
                        ['name'=>'Metformin 500mg','route'=>'PO','dose'=>'500mg','frequency'=>'BD','06:00'=>'✓','10:00'=>'-','14:00'=>'-','18:00'=>'✓','22:00'=>'-','notes'=>'Taken with meals'],
                    ],
                    'prn_medications'     => [
                        ['name'=>'Paracetamol 1g IV','indication'=>'Temp >38.5°C or pain >6/10','max_daily'=>'4g','administered'=>[['time'=>'14:30','dose'=>'1g','reason'=>'Temp 38.9°C','by'=>'Sr. ATEBA']]],
                    ],
                    'date'                => '07 June 2026',
                    'nurse_in_charge'     => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'daily-progress-note':
                return [
                    'date'                   => '07 June 2026',
                    'time'                   => '09:15',
                    'ward'                   => 'Internal Medicine — Ward 3B',
                    'bed_number'             => 'BED-3B-12',
                    'day_of_admission'       => 'Day 2',
                    'subjective'             => 'Patient reports mild residual headache (4/10), much improved from admission. Denies chest pain, SOB, visual changes. Ate 75% of breakfast. Slept 5 hours.',
                    'vital_signs'            => ['bp'=>'148/92 mmHg','pulse'=>'76 bpm','temp'=>'37.1°C','spo2'=>'98% (room air)','rr'=>'16/min','urine_output'=>'1,840 mL (24h)'],
                    'objective_examination'  => 'Alert and oriented. No JVD. Chest clear. No peripheral oedema. Abdomen soft. No focal neurological deficit.',
                    'investigations_today'   => [
                        ['test'=>'Repeat U&E','result'=>'Na 138, K 4.1, Cr 1.38 mg/dL','trend'=>'Improving'],
                        ['test'=>'Morning BP (3 readings)','result'=>'148/92, 144/90, 142/88','trend'=>'Improving'],
                    ],
                    'assessment'             => 'Stage 2 Hypertension — improving. Creatinine trending down. DM under better control.',
                    'plan'                   => [
                        'Continue Amlodipine 10mg + Losartan 50mg + HCTZ 12.5mg',
                        'Target BP < 140/90 before discharge',
                        'Endocrinology review today re: insulin initiation',
                        'Repeat renal panel tomorrow morning',
                        'Physiotherapy ambulation assessment',
                    ],
                    'attending_physician'    => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'resident_physician'     => 'Dr. FOUDA NKENG, Laurence',
                ];

            case 'surgical-safety-checklist':
                return [
                    'procedure'              => 'Laparoscopic Cholecystectomy',
                    'scheduled_time'         => '07 June 2026, 08:00',
                    'surgeon'                => 'Prof. ONDOA MANGA, Jean-Baptiste',
                    'anaesthetist'           => 'Dr. BIYA ESSOMBA, Claude',
                    'scrub_nurse'            => 'Sr. NKOMO ATEBA, Brigitte',
                    'sign_in'                => [
                        'identity_confirmed'    => true,
                        'site_marked'           => true,
                        'consent_obtained'      => true,
                        'anaesthesia_check'     => true,
                        'pulse_oximeter'        => true,
                        'allergies_known'       => 'Penicillin',
                        'difficult_airway'      => false,
                        'aspiration_risk'       => false,
                        'blood_loss_risk'       => 'Low',
                    ],
                    'time_out'               => [
                        'team_introduced'       => true,
                        'patient_identity'      => 'NJOMO EKAMBI, Marie Claire — CMR-2024-00429871',
                        'procedure_confirmed'   => true,
                        'site_confirmed'        => 'Abdomen — laparoscopic ports',
                        'antibiotics_given'     => 'Cefazolin 2g IV at 07:45',
                        'imaging_displayed'     => true,
                        'critical_steps_shared' => 'CBD identification critical — intra-op cholangiogram if anatomy unclear',
                    ],
                    'sign_out'               => [
                        'procedure_recorded'    => true,
                        'instrument_count_ok'   => true,
                        'specimen_labelled'     => 'Gallbladder — cholecystitis',
                        'equipment_issues'      => 'None',
                        'recovery_concerns'     => 'VTE prophylaxis — enoxaparin 40mg SC 6h post-op',
                    ],
                    'completed_at'           => '07 June 2026, 10:45',
                ];

            case 'icu-flowsheet':
                return [
                    'date'                   => '07 June 2026',
                    'icu_bed'                => 'ICU Bed 4',
                    'admission_diagnosis'    => 'Septic shock — community-acquired pneumonia',
                    'apache_ii_score'        => 22,
                    'sofa_score'             => 9,
                    'ventilator_settings'    => ['mode'=>'SIMV+PS','fio2'=>'50%','peep'=>'8 cmH2O','tidal_volume'=>'420 mL','rr_set'=>14,'pip'=>'28 cmH2O'],
                    'hourly_vitals'          => [
                        ['time'=>'08:00','bp'=>'88/52','map'=>62,'hr'=>118,'temp'=>38.9,'spo2'=>94,'fio2'=>'50%','urine_ml'=>35,'cvp'=>10],
                        ['time'=>'10:00','bp'=>'94/58','map'=>70,'hr'=>108,'temp'=>38.5,'spo2'=>96,'fio2'=>'45%','urine_ml'=>48,'cvp'=>9],
                        ['time'=>'12:00','bp'=>'102/64','map'=>77,'hr'=>98,'temp'=>38.1,'spo2'=>97,'fio2'=>'40%','urine_ml'=>62,'cvp'=>9],
                    ],
                    'infusions'              => [
                        ['drug'=>'Norepinephrine','concentration'=>'4mg/50mL','rate_ml_hr'=>'8→6→4','indication'=>'Vasopressor support'],
                        ['drug'=>'Meropenem 1g','route'=>'IV','frequency'=>'Q8h','last_given'=>'06:00'],
                        ['drug'=>'Heparin 5000U','route'=>'SC','frequency'=>'Q12h','indication'=>'DVT prophylaxis'],
                    ],
                    'fluid_balance_24h'      => ['input'=>4200,'output'=>1840,'balance'=>'+2360'],
                    'nurse_in_charge'        => 'Sr. ATOUBA NGUELE, Christiane',
                    'intensivist'            => 'Dr. MBIDA TOKO, Rodrigue',
                ];

            case 'investigation-request':
                return [
                    'urgency'                => 'URGENT',
                    'requesting_ward'        => 'Internal Medicine — Ward 3B',
                    'clinical_indication'    => 'Hypertensive emergency with renal impairment — Day 2 monitoring',
                    'investigations'         => [
                        ['department'=>'Haematology','test'=>'Full Blood Count + Differential','tube'=>'EDTA (Purple)','fasting'=>false,'special_instructions'=>''],
                        ['department'=>'Biochemistry','test'=>'Urea, Electrolytes & Creatinine','tube'=>'Plain (Red)','fasting'=>false,'special_instructions'=>'Spin within 30min'],
                        ['department'=>'Biochemistry','test'=>'Fasting Blood Glucose + HbA1c','tube'=>'Fluoride (Grey)','fasting'=>true,'special_instructions'=>'Must be 8h fasted'],
                        ['department'=>'Cardiology','test'=>'12-lead ECG','tube'=>'N/A','fasting'=>false,'special_instructions'=>'Perform at 07:00 before medications'],
                        ['department'=>'Radiology','test'=>'Chest X-Ray (PA)','tube'=>'N/A','fasting'=>false,'special_instructions'=>'Mobile CXR — patient non-ambulatory'],
                    ],
                    'sample_taken_by'        => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                    'sample_taken_at'        => '07 June 2026, 06:45',
                    'expected_turnaround'    => '2–4 hours for biochemistry; ECG immediate',
                ];

            case 'nursing-admission-assessment':
                return [
                    'admission_date'         => '05 June 2026, 22:47',
                    'admission_mode'         => 'Emergency via A&E',
                    'ward'                   => 'Internal Medicine — Ward 3B',
                    'bed_number'             => 'BED-3B-12',
                    'chief_complaint'        => 'Severe headache, blurred vision, palpitations × 3 hours',
                    'vital_signs_on_admission' => ['bp'=>'210/130 mmHg','pulse'=>'104 bpm','temp'=>'37.8°C','rr'=>'22/min','spo2'=>'96% room air','weight'=>'78kg','height'=>'165cm','bmi'=>'28.6'],
                    'allergies'              => [['allergen'=>'Penicillin','reaction'=>'Anaphylaxis','severity'=>'Severe','recorded_by'=>'Sr. ATEBA']],
                    'past_medical_history'   => 'Hypertension (diagnosed 2019), Type 2 DM (2021)',
                    'current_medications'    => 'Nifedipine 30mg OD, Metformin 500mg BD (self-reported)',
                    'pain_score'             => 7,
                    'pain_location'          => 'Occipital headache, diffuse',
                    'nutritional_screen'     => ['must_score'=>1,'nutritional_risk'=>'Low','diet_on_admission'=>'Normal — low sodium advised'],
                    'fall_risk'              => ['morse_score'=>35,'risk_level'=>'Moderate','interventions'=>['Non-slip socks','Bed rail up','Call bell within reach']],
                    'pressure_ulcer_risk'    => ['braden_score'=>19,'risk_level'=>'Low','skin_condition'=>'Intact'],
                    'social_history'         => 'Lives with husband and 3 children. Teacher by profession. Non-smoker. Occasional wine.',
                    'admitting_nurse'        => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            // ── Batch B — Legal / Programmatic ────────────────────────────────────
            case 'stillbirth-certificate':
                return [
                    'type_of_birth'          => 'Stillbirth',
                    'definition_used'        => 'WHO — born dead at ≥ 28 weeks gestation or ≥ 1000g',
                    'date_of_birth'          => '07 June 2026',
                    'time_of_birth'          => '03:48 WAT',
                    'place_of_birth'         => 'OpesCare Central General Hospital — Maternity Unit',
                    'gestational_age'        => '36 weeks + 2 days',
                    'birth_weight_grams'     => 2640,
                    'sex'                    => 'Male',
                    'plurality'              => 'Singleton',
                    'presentation'           => 'Cephalic',
                    'mode_of_delivery'       => 'Emergency Caesarean Section',
                    'maceration_present'     => false,
                    'probable_cause'         => 'Umbilical cord prolapse — acute fetal hypoxia',
                    'antepartum_stillbirth'  => false,
                    'intrapartum_stillbirth' => true,
                    'mother_name'            => 'NJOMO EKAMBI, Marie Claire',
                    'mother_age'             => 41,
                    'mother_health_id'       => 'CMR-2024-00429871',
                    'gravida'                => 3,
                    'para'                   => 1,
                    'antenatal_visits'       => 6,
                    'father_name'            => 'EKAMBI MPONDO, Joseph Blaise',
                    'attending_midwife'      => 'Sr. ABONO ELONGO, Solange',
                    'attending_obstetrician' => 'Dr. ESSAMA BELA, Christine',
                    'perinatal_audit_required' => true,
                ];

            case 'aefi-report':
                return [
                    'vaccine_name'           => 'Pentavalent (DTP-HepB-Hib)',
                    'vaccine_batch_number'   => 'PENTA-CM-2026-B0441',
                    'dose_number'            => '3rd dose',
                    'date_of_vaccination'    => '04 June 2026',
                    'date_of_aefi_onset'     => '05 June 2026',
                    'time_to_onset_hours'    => 18,
                    'patient_age_months'     => 14,
                    'event_description'      => 'High fever (39.8°C), inconsolable crying, injection site swelling 4cm diameter. Seizure lasting 2 minutes at 20 hours post-vaccination.',
                    'aefi_classification'    => 'Serious — seizure',
                    'aefi_type'              => 'Vaccine reaction',
                    'outcome'                => 'Recovered — discharged home 07 June 2026',
                    'treatment_given'        => 'Paracetamol syrup, IV fluids, lorazepam 0.1mg/kg for seizure',
                    'vaccination_site'       => 'OpesCare Central Hospital Vaccination Centre',
                    'vaccinator_name'        => 'Sr. KOUAM NJOYA, Albertine',
                    'reporter_name'          => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'district_health_office' => 'DDS Centre — Yaoundé',
                    'reported_to_minsante'   => true,
                    'minsante_reference'     => 'MINSANTE-AEFI-2026-00312',
                ];

            case 'notifiable-disease-report':
                return [
                    'disease'                => 'Cholera (Vibrio cholerae O1)',
                    'icd10_code'             => 'A00.1',
                    'case_classification'    => 'Confirmed',
                    'case_type'              => 'New case',
                    'date_of_onset'          => '05 June 2026',
                    'date_of_notification'   => '07 June 2026',
                    'date_of_hospitalisation'=> '06 June 2026',
                    'clinical_presentation'  => 'Profuse watery diarrhoea (rice-water stool), vomiting, severe dehydration — WHO cholera criteria met',
                    'laboratory_confirmation'=> 'Stool culture: Vibrio cholerae O1 Ogawa biotype El Tor',
                    'patient_occupation'     => 'Food vendor — Marché central',
                    'contact_tracing_done'   => true,
                    'contacts_identified'    => 8,
                    'contacts_followed_up'   => 8,
                    'probable_source'        => 'Contaminated street food (fish sauce)',
                    'treatment'              => 'IV Ringer lactate rehydration, doxycycline 300mg stat',
                    'outcome'                => 'Improving — stable',
                    'notified_by'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'district_surveillance'  => 'DSO Centre — confirmed receipt 07 June 2026, 15:30',
                ];

            case 'malaria-report':
                return [
                    'test_type'              => 'Rapid Diagnostic Test (RDT) + Thick / Thin Blood Film',
                    'test_kit'               => 'SD BIOLINE Malaria Ag P.f/Pan',
                    'date_of_test'           => '07 June 2026',
                    'time_of_test'           => '10:22',
                    'rdt_result'             => 'POSITIVE — Plasmodium falciparum (Pf line)',
                    'microscopy_result'      => 'Positive — P. falciparum trophozoites, parasite density 12,400/μL (0.27%)',
                    'clinical_severity'      => 'Uncomplicated malaria',
                    'symptoms'               => ['Fever 39.2°C','Chills and rigors','Headache','Myalgia','Vomiting × 3'],
                    'treatment_protocol'     => 'PNLP First-line: Artemether-Lumefantrine (AL) 4 tabs BD × 3 days',
                    'treatment_started'      => '07 June 2026, 11:00',
                    'g6pd_tested'            => false,
                    'pregnancy_test'         => 'Negative',
                    'rdt_performed_by'       => 'Sr. NKOMO BIYA, Christine',
                    'reviewed_by'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'reported_to_district'   => true,
                ];

            case 'hiv-counselling-record':
                return [
                    'session_type'           => 'Pre-Test Counselling + Testing',
                    'session_date'           => '07 June 2026',
                    'session_duration_min'   => 35,
                    'counselling_type'       => 'Individual',
                    'reason_for_testing'     => 'Routine antenatal screening',
                    'risk_assessment'        => 'Low-risk — monogamous relationship, no prior STI',
                    'pre_test_counselling'   => ['HIV transmission explained'=>true,'Meaning of results explained'=>true,'Consent for testing obtained'=>true,'Confidentiality assured'=>true],
                    'test_performed'         => 'Determine HIV-1/2 Ag/Ab Combo RDT',
                    'test_result'            => 'NEGATIVE',
                    'result_disclosed'       => true,
                    'result_understood'      => true,
                    'post_test_counselling'  => ['Window period explained'=>true,'Prevention counselled (ABC)'=>true,'Partner testing encouraged'=>true,'Re-testing plan: 3 months'=>true],
                    'referred_to'            => 'Antenatal clinic — continue PMTCT pathway',
                    'counsellor_name'        => 'Mr. FOUDA BELO, Samuel',
                    'counsellor_code'        => 'HCC-0047',
                    'client_code'            => 'HCR-2026-004712',
                ];

            case 'blood-bank-request':
                return [
                    'urgency'                => 'URGENT — within 2 hours',
                    'requesting_ward'        => 'Surgical Ward — 4A',
                    'clinical_indication'    => 'Acute blood loss — post-laparoscopic cholecystectomy; Hb 6.8 g/dL',
                    'component_requested'    => 'Packed Red Blood Cells (PRBC)',
                    'units_requested'        => 2,
                    'patient_blood_group'    => 'O Positive',
                    'crossmatch_sample_time' => '07 June 2026, 11:30',
                    'sample_tube'            => 'Plain (Red) — 5mL',
                    'sample_taken_by'        => 'Dr. FOUDA NKENG, Laurence',
                    'special_requirements'   => ['Leucodepleted','CMV negative if possible'],
                    'transfusion_history'    => 'No prior transfusions',
                    'previous_reactions'     => 'None',
                    'blood_bank_response'    => ['units_available'=>2,'blood_group_confirmed'=>'O+ (ABO/Rh verified)','crossmatch_result'=>'Compatible','issue_time'=>'07 June 2026, 13:15'],
                    'authorised_by'          => 'Prof. ONDOA MANGA, Jean-Baptiste',
                ];

            case 'postop-recovery-record':
                return [
                    'operation'              => 'Laparoscopic Cholecystectomy',
                    'anaesthesia_type'       => 'General Anaesthesia — TIVA',
                    'anaesthesia_duration'   => '1 hour 45 minutes',
                    'arrival_in_recovery'    => '07 June 2026, 10:52',
                    'discharge_from_recovery'=> '07 June 2026, 13:15',
                    'aldrete_scores'         => [
                        ['time'=>'10:52','activity'=>2,'respiration'=>2,'circulation'=>2,'consciousness'=>2,'spo2'=>2,'total'=>10,'action'=>'Arrived stable'],
                        ['time'=>'11:30','activity'=>2,'respiration'=>2,'circulation'=>2,'consciousness'=>2,'spo2'=>2,'total'=>10,'action'=>'Orientated'],
                        ['time'=>'13:00','activity'=>2,'respiration'=>2,'circulation'=>2,'consciousness'=>2,'spo2'=>2,'total'=>10,'action'=>'Cleared for ward'],
                    ],
                    'pain_scores'            => [['time'=>'10:52','score'=>6],['time'=>'11:00','score'=>4,'after'=>'Morphine 2mg IV'],['time'=>'12:00','score'=>2]],
                    'nausea_vomiting'        => 'PONV — one episode; treated with ondansetron 4mg IV',
                    'complications'          => 'None',
                    'analgesics_given'       => ['Morphine 2mg IV (10:58)','Paracetamol 1g IV (11:30)'],
                    'antiemetics_given'      => ['Ondansetron 4mg IV (11:05)'],
                    'discharge_criteria_met' => true,
                    'recovery_nurse'         => 'Sr. ELONG BASSO, Jacqueline',
                    'discharged_to'          => 'Surgical Ward 4A',
                ];

            // ── Batch C — Clinical ────────────────────────────────────────────────
            case 'ecg-report':
                return [
                    'indication'             => '12-lead ECG — hypertensive emergency screening',
                    'date_time'              => '07 June 2026, 07:05',
                    'rate'                   => '96 bpm',
                    'rhythm'                 => 'Sinus Rhythm',
                    'pr_interval_ms'         => 164,
                    'qrs_duration_ms'        => 88,
                    'qt_qtc_ms'              => '398 / 455',
                    'axis'                   => 'Normal (+62°)',
                    'p_wave'                 => 'Normal — upright in I, II; bifid in V1',
                    'st_changes'             => 'No ST elevation or depression',
                    't_wave'                 => 'Inverted in V1–V2 (non-specific)',
                    'lv_hypertrophy'         => 'Sokolow-Lyon criteria met (SV1+RV5 = 38mm)',
                    'interpretation'         => 'Sinus rhythm with LVH pattern. Non-specific T-wave changes V1–V2. No acute ischaemia.',
                    'clinical_correlation'   => 'LVH consistent with longstanding hypertension. Serial ECGs recommended.',
                    'performed_by'           => 'Sr. ATEBA NKENG, Brigitte',
                    'reported_by'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                ];

            case 'fall-risk-assessment':
                return [
                    'assessment_date'        => '07 June 2026',
                    'assessment_tool'        => 'Morse Fall Scale',
                    'morse_items'            => [
                        ['item'=>'History of falling in past 3 months','score_options'=>'25=Yes / 0=No','score'=>0],
                        ['item'=>'Secondary diagnosis','score_options'=>'15=Yes / 0=No','score'=>15],
                        ['item'=>'Ambulatory aid','score_options'=>'30=Furniture / 15=Crutch-cane / 0=None/bedridden','score'=>0],
                        ['item'=>'IV therapy / heparin lock','score_options'=>'20=Yes / 0=No','score'=>20],
                        ['item'=>'Gait/Transferring','score_options'=>'20=Impaired / 10=Weak / 0=Normal/bedrest','score'=>10],
                        ['item'=>'Mental status','score_options'=>'15=Overestimates ability / 0=Knows limits','score'=>0],
                    ],
                    'total_morse_score'      => 45,
                    'risk_level'             => 'High Risk (≥45)',
                    'interventions'          => [
                        'Fall risk armband applied (yellow)',
                        'Non-slip footwear',
                        'Bed in lowest position, brakes locked',
                        'Call bell at bedside',
                        'Frequent nursing checks Q2h',
                        'Nightlight on',
                    ],
                    'reassessment_due'       => '09 June 2026',
                    'assessed_by'            => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'pressure-ulcer-assessment':
                return [
                    'assessment_date'        => '07 June 2026',
                    'assessment_tool'        => 'Braden Scale',
                    'braden_items'           => [
                        ['subscale'=>'Sensory Perception','score'=>3,'descriptor'=>'Slightly limited'],
                        ['subscale'=>'Moisture','score'=>3,'descriptor'=>'Occasionally moist'],
                        ['subscale'=>'Activity','score'=>2,'descriptor'=>'Chairfast'],
                        ['subscale'=>'Mobility','score'=>3,'descriptor'=>'Slightly limited'],
                        ['subscale'=>'Nutrition','score'=>3,'descriptor'=>'Adequate'],
                        ['subscale'=>'Friction & Shear','score'=>2,'descriptor'=>'Potential problem'],
                    ],
                    'total_braden_score'     => 16,
                    'risk_level'             => 'Mild Risk (15–18)',
                    'skin_inspection'        => ['Sacrum'=>'Intact','Heels'=>'Intact','Elbows'=>'Intact','Occiput'=>'Intact','Other'=>'No breakdown noted'],
                    'existing_wounds'        => 'None',
                    'prevention_plan'        => [
                        '2-hourly repositioning chart initiated',
                        'Foam mattress overlay applied',
                        'Heel protectors — bilateral',
                        'Skin moisturiser BD',
                        'Nutritional support — high-protein diet',
                    ],
                    'reassessment_due'       => '10 June 2026',
                    'assessed_by'            => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'glucose-log':
                return [
                    'monitoring_period'      => '05 June — 07 June 2026',
                    'target_range'           => 'Fasting: 4.4–7.2 mmol/L | Post-prandial: < 10.0 mmol/L',
                    'diabetes_type'          => 'Type 2 Diabetes Mellitus',
                    'current_therapy'        => 'Metformin 500mg BD + sliding scale insulin (hospital protocol)',
                    'readings'               => [
                        ['date'=>'05 Jun','time'=>'22:50 (Admission)','reading'=>'18.2 mmol/L','action'=>'Insulin 8U SC stat + sliding scale commenced'],
                        ['date'=>'06 Jun','time'=>'06:00 (Fasting)','reading'=>'12.4 mmol/L','action'=>'Sliding scale — 6U SC'],
                        ['date'=>'06 Jun','time'=>'12:00 (Pre-lunch)','reading'=>'10.8 mmol/L','action'=>'Sliding scale — 4U SC'],
                        ['date'=>'06 Jun','time'=>'18:00 (Pre-supper)','reading'=>'9.2 mmol/L','action'=>'Sliding scale — 2U SC'],
                        ['date'=>'07 Jun','time'=>'06:00 (Fasting)','reading'=>'7.8 mmol/L','action'=>'Sliding scale — 2U SC'],
                        ['date'=>'07 Jun','time'=>'12:00 (Pre-lunch)','reading'=>'8.4 mmol/L','action'=>'Dietary counselling'],
                    ],
                    'hypoglycaemia_episodes' => 0,
                    'hba1c_on_admission'     => '8.2%',
                    'endocrinology_review'   => 'Requested — 07 June 2026',
                    'monitored_by'           => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'handover-note':
                return [
                    'handover_date'          => '07 June 2026',
                    'handover_time'          => '08:00',
                    'handing_over'           => 'Night shift — Sr. MVONDO BIYA, Patience (22:00–08:00)',
                    'receiving'              => 'Day shift — Sr. NJOYA ATEBA, Marie-Thérèse (08:00–20:00)',
                    'ward'                   => 'Internal Medicine — Ward 3B (12 beds, 11 occupied)',
                    'patients'               => [
                        ['bed'=>'3B-12','name'=>'NJOMO EKAMBI','diagnosis'=>'Hypertensive Emergency + DM','concerns'=>'BP trending down — 148/92 at 06:00. Endo review due today. Glucose 7.8 fasting.','actions_required'=>'ECG at 07:00 ✓, labs at 06:45 ✓, physio referral pending'],
                        ['bed'=>'3B-08','name'=>'NKOMO BELLO, Ahmed','diagnosis'=>'CKD Stage 4 — fluid overload','concerns'=>'Weight up 2kg overnight. Urine output 320mL/24h — borderline oliguric.','actions_required'=>'IV frusemide 80mg at 08:30. Nephrology review 10:00.'],
                        ['bed'=>'3B-05','name'=>'ATEBA FOUDA, Rose','diagnosis'=>'CAP — pneumonia Day 4','concerns'=>'Afebrile × 24h. SpO2 97% room air. Tolerating oral antibiotics.','actions_required'=>'Consider step-down to oral — discuss at ward round 09:00'],
                    ],
                    'critical_alerts'        => 'Bed 3B-08 — watch fluid balance closely. Alert registrar if urine output < 200mL/8h.',
                    'handover_complete'       => true,
                ];

            case 'mental-health-involuntary':
                return [
                    'admission_type'         => 'Involuntary — Section 12 (Cameroon Mental Health Act)',
                    'grounds_for_admission'  => 'Imminent risk of self-harm; psychotic episode; patient lacks capacity to consent',
                    'application_made_by'    => 'Dr. MBASSI ATEBA, Emmanuel Pierre (attending physician)',
                    'second_opinion_by'      => 'Dr. NKONO ESSAME, Paul-Henri (Psychiatrist)',
                    'date_of_admission'      => '07 June 2026',
                    'presenting_complaint'   => 'Agitation, auditory hallucinations, threatening behaviour, refusal of food × 48h',
                    'mental_state_exam'      => [
                        'appearance'    => 'Dishevelled, malodorous, wearing multiple layers',
                        'behaviour'     => 'Agitated, guarded, non-cooperative',
                        'speech'        => 'Pressured, disorganised',
                        'mood'          => 'Dysphoric/labile',
                        'affect'        => 'Incongruent',
                        'perception'    => 'Auditory hallucinations (command type)',
                        'cognition'     => 'Disoriented to time and place',
                        'insight'       => 'None',
                    ],
                    'risk_assessment'        => 'High risk — self-harm (command hallucinations), low risk to others currently',
                    'initial_management'     => 'Lorazepam 2mg IM stat; Haloperidol 5mg IM; 1:1 nursing observation',
                    'rights_explained'       => true,
                    'rights_explained_to'    => 'Patient + next of kin (sister — Ms. NJOMO CLAIRE)',
                    'review_date'            => '10 June 2026 (72h review)',
                    'admitting_psychiatrist' => 'Dr. NKONO ESSAME, Paul-Henri',
                ];

            // ── Batch D — Specialist ──────────────────────────────────────────────
            case 'dnr-order':
                return [
                    'order_date'             => '07 June 2026',
                    'order_time'             => '11:30',
                    'diagnosis'              => 'Metastatic hepatocellular carcinoma — BCLC Stage D; End-stage liver disease (Child-Pugh C)',
                    'prognosis'              => 'Expected survival < 4 weeks',
                    'basis_for_dnr'          => 'Patient-directed — competent adult; consistent with palliative care goals',
                    'patient_decision_capacity' => true,
                    'patient_understanding'  => 'Patient understands prognosis, burdens of CPR, and goal of comfort care',
                    'family_informed'        => true,
                    'family_in_agreement'    => true,
                    'what_dnr_covers'        => ['No cardiopulmonary resuscitation (CPR)','No mechanical ventilation','No ICU admission','No vasopressors or inotropes'],
                    'what_is_continued'      => ['Comfort medications (analgesia, antiemetics, anxiolytics)','Oral / IV hydration as tolerated','Palliative nursing care','Family presence at bedside 24/7'],
                    'second_physician'       => 'Dr. NGONO ATEBA, Sylvestre (Senior Registrar)',
                    'ethical_review'         => 'Not required — patient-directed, documented, witnessed',
                    'valid_for'              => 'Duration of this admission and all subsequent readmissions unless revoked',
                    'order_signed_by'        => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'witness_name'           => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'palliative-care-plan':
                return [
                    'diagnosis'              => 'Metastatic hepatocellular carcinoma — BCLC Stage D',
                    'prognosis_estimate'     => '2–4 weeks',
                    'goals_of_care'          => 'Comfort and dignity — focus on symptom control, family support, and peaceful death at preferred location',
                    'preferred_place_of_death' => 'Home (patient preference expressed)',
                    'symptom_management'     => [
                        ['symptom'=>'Pain (6/10 — right upper quadrant)','management'=>'Morphine SR 10mg BD + 2.5mg IR PRN Q4h; titrate to comfort'],
                        ['symptom'=>'Nausea','management'=>'Metoclopramide 10mg TDS AC; ondansetron PRN'],
                        ['symptom'=>'Ascites','management'=>'Therapeutic paracentesis as needed for comfort; low-sodium diet'],
                        ['symptom'=>'Anxiety/Existential distress','management'=>'Lorazepam 0.5mg SL PRN; chaplaincy referral; psychosocial support'],
                        ['symptom'=>'Anorexia/Cachexia','management'=>'Nutritional supplements; small frequent meals; oral care BD'],
                    ],
                    'psychosocial_support'   => 'Social worker AWAH BELLO involved — financial/family counselling. Chaplain NKEMELU, Timothée visiting daily.',
                    'advance_care_planning'  => 'DNR order signed. Patient aware of prognosis. Will/estate arrangements discussed with family.',
                    'family_education'       => 'Signs of imminent death explained. 24/7 family contact number for palliative team provided.',
                    'discharge_plan'         => 'Home palliative care package — community nurse × 3/week; GP notified; Morphine supply arranged',
                    'palliative_team'        => 'Dr. MBASSI ATEBA (lead) + Sr. NJOYA ATEBA + Social Worker AWAH BELLO',
                    'review_date'            => '10 June 2026',
                ];

            case 'occupational-therapy':
                return [
                    'referral_reason'        => 'Post-CVA functional rehabilitation — assess ADL capacity and home safety',
                    'assessment_date'        => '07 June 2026',
                    'diagnosis'              => 'Left MCA ischaemic stroke — Day 5; Right hemiplegia',
                    'barthel_index'          => [
                        ['activity'=>'Bowels','score'=>1,'max'=>2,'description'=>'Occasional accident'],
                        ['activity'=>'Bladder','score'=>1,'max'=>2,'description'=>'Occasional accident'],
                        ['activity'=>'Grooming','score'=>0,'max'=>1,'description'=>'Needs help'],
                        ['activity'=>'Toilet use','score'=>0,'max'=>2,'description'=>'Dependent'],
                        ['activity'=>'Feeding','score'=>1,'max'=>2,'description'=>'Needs help cutting'],
                        ['activity'=>'Transfer (bed/chair)','score'=>1,'max'=>3,'description'=>'Major help × 2 people'],
                        ['activity'=>'Mobility','score'=>0,'max'=>3,'description'=>'Immobile'],
                        ['activity'=>'Dressing','score'=>0,'max'=>2,'description'=>'Dependent'],
                        ['activity'=>'Stairs','score'=>0,'max'=>2,'description'=>'Unable'],
                        ['activity'=>'Bathing','score'=>0,'max'=>1,'description'=>'Dependent'],
                    ],
                    'barthel_total'          => 4,
                    'barthel_max'            => 20,
                    'functional_level'       => 'Severely dependent',
                    'upper_limb_function'    => 'Right UL — no active movement; flaccid; Brunnstrom Stage 1',
                    'goals'                  => [
                        'Short-term (2 weeks): Transfer from bed to chair with minimal assistance',
                        'Medium-term (6 weeks): Independent self-care (washing face/hands)',
                        'Long-term (3 months): Return to home with adapted environment',
                    ],
                    'home_assessment_required' => true,
                    'adaptive_equipment'     => 'Wheelchair, raised toilet seat, shower chair, non-slip mat',
                    'occupational_therapist' => 'Mr. MBIDA FOUDA, Emmanuel',
                ];

            case 'speech-therapy-report':
                return [
                    'referral_reason'        => 'Post-stroke dysphagia and aphasia assessment',
                    'assessment_date'        => '07 June 2026',
                    'diagnosis'              => 'Left MCA ischaemic stroke — Broca aphasia + oropharyngeal dysphagia',
                    'speech_language_assessment' => [
                        'aphasia_type'      => 'Broca (expressive) — non-fluent, agrammatic',
                        'comprehension'     => 'Relatively preserved (75% on Token Test)',
                        'naming'            => 'Severely impaired — anomic; 40% correct on BNT',
                        'repetition'        => 'Impaired — unable to repeat phrases > 3 words',
                        'reading'           => 'Impaired for sentences; single words partially preserved',
                        'writing'           => 'Unable — right hemiplegic hand',
                    ],
                    'dysphagia_assessment'   => [
                        'bedside_swallow'   => 'FAIL — coughing and wet voice on 3mL water bolus',
                        'diet_texture'      => 'IDDSI Level 4 — Puréed (no lumps)',
                        'fluid_consistency' => 'IDDSI Level 2 — Mildly thick',
                        'aspiration_risk'   => 'Moderate-high — silent aspiration possible',
                        'video_fluoroscopy' => 'Requested — pending scheduling',
                    ],
                    'recommendations'        => [
                        'NGT feeding while dysphagia assessment progressing',
                        'Twice-weekly SLT sessions',
                        'AAC (Augmentative and Alternative Communication) device trial',
                        'Family communication training',
                    ],
                    'speech_therapist'       => 'Ms. KONO ATEBA, Christelle',
                    'next_review'            => '14 June 2026',
                ];

            case 'nutritional-assessment':
                return [
                    'assessment_date'        => '07 June 2026',
                    'assessment_tool'        => 'MUST (Malnutrition Universal Screening Tool)',
                    'must_items'             => [
                        ['item'=>'BMI score','value'=>'18.2 kg/m²','score'=>1,'descriptor'=>'BMI 18.5–20 → 1 point'],
                        ['item'=>'Unplanned weight loss > 5% in 3 months','value'=>'7.2% loss (82kg → 76kg)','score'=>2,'descriptor'=>'> 10% → 2 points'],
                        ['item'=>'Acute disease effect','value'=>'Post-operative day 2','score'=>2,'descriptor'=>'Acutely ill + likely no nutrition > 5 days → 2'],
                    ],
                    'must_total'             => 5,
                    'must_category'          => 'High Risk (≥2)',
                    'anthropometrics'        => ['weight_kg'=>76,'height_cm'=>165,'bmi'=>'27.9','mid_arm_circumference'=>'24.2cm','weight_6months_ago'=>'82kg'],
                    'dietary_intake'         => 'Less than 50% of requirements for past 3 days; poor appetite; nausea',
                    'gut_function'           => 'Functioning — oral route preferred',
                    'nutritional_plan'       => [
                        'Target calories: 1,900 kcal/day (25 kcal/kg)',
                        'Target protein: 114g/day (1.5g/kg)',
                        'Oral nutritional supplements: Fresubin 2kcal × 2/day',
                        'Dietitian-led counselling × 3/week',
                        'Reassess in 5 days',
                    ],
                    'dietitian'              => 'Ms. ABOMO ESSAMA, Véronique',
                ];

            case 'social-work-assessment':
                return [
                    'referral_reason'        => 'Psychosocial assessment — prolonged hospitalisation; financial hardship; discharge planning',
                    'assessment_date'        => '07 June 2026',
                    'living_situation'       => 'Rented 2-room apartment, Yaoundé Centre. Lives with husband and 3 children (ages 8, 12, 16).',
                    'social_support'         => 'Strong family support. Husband self-employed (carpenter). Extended family nearby.',
                    'financial_assessment'   => 'Household income XAF 180,000/month. CNPS insurance active (covers 80%). Hospital bill to date: XAF 248,000 — XAF 49,600 patient portion.',
                    'financial_concerns'     => 'Husband unable to work during her hospitalisation. Requesting social assistance fund.',
                    'child_welfare'          => 'Children cared for by maternal aunt during admission. Attending school normally.',
                    'coping_assessment'      => 'Patient anxious about diagnosis, worried about children. Accepting support. No psychiatric concerns.',
                    'identified_needs'       => [
                        'Financial assistance — social fund application submitted',
                        'Home help post-discharge (household tasks × 2 weeks)',
                        'Psychosocial counselling — 3 sessions',
                        'Community health nurse follow-up',
                    ],
                    'discharge_plan'         => 'Discharge to home in 2–3 days. Community nurse referral made. Pharmacy home delivery arranged.',
                    'social_worker'          => 'Ms. AWAH BELLO, Mireille',
                    'supervisor_review'      => '09 June 2026',
                ];

            // ── Batch E — Specialist continued ───────────────────────────────────
            case 'orthopaedic-chart':
                return [
                    'diagnosis'              => 'Right femoral neck fracture — Garden Type III (displaced)',
                    'injury_mechanism'       => 'Low-energy fall from standing height',
                    'date_of_injury'         => '05 June 2026',
                    'imaging'                => [
                        ['view'=>'AP Pelvis X-ray','finding'=>'Displaced right femoral neck fracture — Garden III; neck-shaft angle 110°'],
                        ['view'=>'Lateral hip X-ray','finding'=>'Posterior tilt confirmed; no acetabular involvement'],
                        ['view'=>'CT Hip (coronal)','finding'=>'Comminution of posteromedial cortex; no vascular injury'],
                    ],
                    'neurovascular_status'   => 'Dorsalis pedis and posterior tibial pulses present bilaterally. Sensation intact. Capillary refill < 2s.',
                    'vte_risk'               => 'High — LMWH (enoxaparin 40mg SC OD) commenced',
                    'planned_surgery'        => 'Right hemiarthroplasty — uncemented prosthesis',
                    'surgery_date'           => '09 June 2026',
                    'anaesthesia_plan'       => 'Spinal + nerve block',
                    'preop_optimisation'     => [
                        'Haematology review — Hb 9.8g/dL — transfuse to ≥ 10g/dL preop',
                        'Cardiology clearance — hypertension managed',
                        'Physiotherapy — preoperative mobilisation instructions',
                    ],
                    'weight_bearing_plan'    => 'Full weight bearing as tolerated Day 1 post-op',
                    'orthopaedic_surgeon'    => 'Dr. ESSAMA TCHUENTE, Laurent',
                ];

            case 'resuscitation-record':
                return [
                    'event_date'             => '07 June 2026',
                    'event_time'             => '14:22',
                    'location'               => 'Medical Ward 3B — Bed 3B-12',
                    'witness'                => 'Sr. NJOYA ATEBA (nurse) + Dr. FOUDA NKENG (registrar)',
                    'presenting_rhythm'      => 'Pulseless Electrical Activity (PEA)',
                    'cpr_started_at'         => '14:22',
                    'cpr_by'                 => 'Dr. FOUDA NKENG',
                    'airway'                 => 'Intubated at 14:24 — ETT 7.5 — confirmed bilateral air entry',
                    'iv_access'              => 'IV in situ — right antecubital; second IV — left EJ × 14:26',
                    'timeline'               => [
                        ['time'=>'14:22','event'=>'Unresponsive — no pulse','action'=>'CPR commenced'],
                        ['time'=>'14:22','event'=>'AED applied','action'=>'Analysing — no shock advised (PEA)'],
                        ['time'=>'14:24','event'=>'Adrenaline 1mg IV','action'=>'Continue CPR'],
                        ['time'=>'14:26','event'=>'Rhythm check — still PEA','action'=>'Adrenaline 1mg IV Q3–5min'],
                        ['time'=>'14:28','event'=>'Blood gas — pH 7.08, K⁺ 6.8','action'=>'Calcium gluconate 10mL + bicarbonate 50mL'],
                        ['time'=>'14:32','event'=>'ROSC — sinus rhythm','action'=>'BP 94/60; transferred to ICU'],
                    ],
                    'total_cpr_duration'     => '10 minutes',
                    'outcome'                => 'ROSC — transferred to ICU',
                    'probable_cause'         => 'Hyperkalaemia secondary to AKI',
                    'team_leader'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'debriefing_completed'   => true,
                ];

            case 'nicu-chart':
                return [
                    'date'                   => '07 June 2026',
                    'nicu_bed'               => 'NICU Incubator 3',
                    'gestational_age_at_birth' => '30 weeks + 4 days',
                    'birth_weight_g'         => 1320,
                    'postnatal_age_days'     => 5,
                    'corrected_gestational_age' => '31 weeks + 2 days',
                    'current_weight_g'       => 1295,
                    'diagnoses'              => ['Respiratory distress syndrome (RDS) — on CPAP','Very preterm birth','Hypoglycaemia — resolved'],
                    'respiratory_support'    => ['mode'=>'Nasal CPAP','fio2'=>'30%','peep'=>'6 cmH2O'],
                    'vital_signs'            => [
                        ['time'=>'06:00','temp'=>'36.8°C','hr'=>168,'rr'=>52,'spo2'=>96,'bp'=>'48/28 MAP:36'],
                        ['time'=>'12:00','temp'=>'37.0°C','hr'=>162,'rr'=>48,'spo2'=>97,'bp'=>'50/30 MAP:37'],
                    ],
                    'feeds'                  => [
                        'route'             => 'Nasogastric tube',
                        'type'              => 'Expressed breast milk (EBM)',
                        'volume'            => '12mL Q3h (40mL/kg/day)',
                        'tolerance'         => 'Good — no aspirates, no vomiting',
                    ],
                    'medications'            => ['Caffeine citrate 6mg/kg/day IV (loading completed)','Vitamin D 400 IU/day PO','Iron drops — starting Day 7'],
                    'fluid_balance_24h'      => ['iv_fluids'=>120,'oral_feeds'=>96,'urine'=>180,'balance'=>'+36mL'],
                    'head_ultrasound'        => 'Day 3 — Grade I IVH (bilateral) — follow-up scheduled Day 7',
                    'attending_neonatologist'=> 'Dr. AMOUGOU FOUDA, Béatrice',
                    'nurse'                  => 'Sr. KANA BIYA, Yvette',
                ];

            case 'patient-complaint':
                return [
                    'complaint_date'         => '07 June 2026',
                    'complaint_time'         => '10:45',
                    'complaint_category'     => 'Staff attitude / Communication',
                    'description'            => 'Patient reports that a nurse on the night shift (unnamed) was dismissive when she requested pain relief at 02:30. Waited 45 minutes for response. Felt her concerns were not taken seriously.',
                    'desired_outcome'        => 'Acknowledgement, apology, assurance that night staff will be addressed.',
                    'received_by'            => 'Patient Relations Officer — Ms. TCHINDA NKENG, Laurence',
                    'patient_representative' => 'EKAMBI MPONDO, Joseph Blaise (husband)',
                    'preliminary_response'   => 'Apology tendered verbally. Night charge nurse to be interviewed. Patient informed of formal process.',
                    'investigation_assigned_to' => 'Nursing Matron — Sr. ABONO FOUDA, Celestine',
                    'investigation_deadline' => '14 June 2026',
                    'complaint_reference'    => 'OC-PCF-2026-000043',
                    'escalation_required'    => false,
                    'outcome'                => 'Pending investigation',
                    'follow_up_date'         => '10 June 2026',
                ];

            case 'procedure-consent':
                return [
                    'procedure'              => 'Diagnostic Upper GI Endoscopy (OGD)',
                    'indication'             => 'Investigation of dyspepsia, epigastric pain, and iron deficiency anaemia',
                    'consent_date'           => '07 June 2026',
                    'consent_time'           => '11:00',
                    'anaesthesia_type'       => 'Conscious sedation (midazolam + lignocaine spray)',
                    'proposed_benefits'      => [
                        'Direct visualisation of oesophagus, stomach, duodenum',
                        'Biopsy for H. pylori, dysplasia, or malignancy if indicated',
                        'Allows targeted treatment',
                    ],
                    'possible_risks'         => [
                        ['risk'=>'Bleeding','frequency'=>'1 in 1,000','severity'=>'Usually minor; rarely requires transfusion'],
                        ['risk'=>'Perforation','frequency'=>'1 in 10,000','severity'=>'Rare; may require surgery'],
                        ['risk'=>'Aspiration pneumonia','frequency'=>'Rare','severity'=>'Treated with antibiotics'],
                        ['risk'=>'Drug reaction (sedation)','frequency'=>'Uncommon','severity'=>'Managed on-site'],
                    ],
                    'alternatives'           => 'Barium meal X-ray (lower sensitivity); empirical PPI therapy without investigation',
                    'questions_answered'     => true,
                    'patient_decision_capacity' => true,
                    'interpreter_required'   => false,
                    'consent_witnessed_by'   => 'Sr. ATEBA NKENG, Brigitte',
                    'endoscopist'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                ];

            // ── Batch F — Mortuary ────────────────────────────────────────────────
            case 'mortuary-admission':
                return [
                    'body_tag_number'        => 'OC-MRT-2026-00047',
                    'date_of_admission'      => '07 June 2026, 15:45',
                    'admitted_from'          => 'Internal Medicine Ward 3B',
                    'cause_of_death'         => 'Hypertensive emergency — acute intracerebral haemorrhage',
                    'death_certificate_number'=> 'OC-DTH-2026-000088',
                    'date_time_of_death'     => '07 June 2026, 15:18',
                    'certifying_doctor'      => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'next_of_kin_name'       => 'EKAMBI MPONDO, Joseph Blaise',
                    'next_of_kin_relationship'=> 'Husband',
                    'next_of_kin_contact'    => '+237 677 441 009',
                    'storage_unit'           => 'Bay 1, Refrigerated Unit A, Tray 3',
                    'temperature_zone'       => '2–4°C',
                    'body_condition'         => 'Good',
                    'valuables_received'     => [['item'=>'Gold wedding band','description'=>'Yellow gold, plain band','stored_in'=>'Safe B-003']],
                    'infectious_disease_risk'=> false,
                    'autopsy_requested'      => false,
                    'expected_release_date'  => '09 June 2026',
                    'admitted_by'            => 'Mr. BELLO HAMIDOU, Mortuary Attendant',
                ];

            case 'body-release':
                return [
                    'body_tag_number'        => 'OC-MRT-2026-00047',
                    'release_date'           => '09 June 2026',
                    'release_time'           => '10:30',
                    'released_to_name'       => 'EKAMBI MPONDO, Joseph Blaise',
                    'released_to_relationship' => 'Husband',
                    'released_to_id_type'    => 'National Identity Card',
                    'released_to_id_number'  => 'CNI-CM-1991-004472',
                    'funeral_home'           => 'Pompes Funèbres Sainte-Cécile, Yaoundé',
                    'funeral_home_contact'   => '+237 222 110 847',
                    'transport_vehicle'      => 'Hearse — CM 412 AB',
                    'burial_permit_number'   => 'OC-BPN-2026-000096',
                    'death_certificate_issued'=> true,
                    'death_certificate_number'=> 'OC-DTH-2026-000088',
                    'all_valuables_returned' => true,
                    'valuables_list'         => 'Gold wedding band — received by next of kin',
                    'identity_verified_by'   => 'Mr. BELLO HAMIDOU, Mortuary Attendant',
                    'authorised_by'          => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'next_of_kin_signature_obtained' => true,
                ];

            case 'autopsy-consent':
                return [
                    'autopsy_type'           => 'Clinical / Hospital Autopsy (Non-medicolegal)',
                    'purpose'                => 'To determine exact cause of death and obtain complete pathological correlation with ante-mortem clinical findings; educational value for quality improvement.',
                    'consent_date'           => '08 June 2026',
                    'consent_time'           => '09:15',
                    'consenting_next_of_kin' => 'EKAMBI MPONDO, Joseph Blaise (Husband)',
                    'next_of_kin_id'         => 'CNI-CM-1991-004472',
                    'relationship_priority'  => '1st degree — Spouse',
                    'information_provided'   => [
                        'Purpose and procedure of autopsy explained',
                        'Organs may be retained temporarily for histology',
                        'Body returned intact after procedure',
                        'Results shared with family within 6 weeks',
                        'Right to decline without affecting body release',
                    ],
                    'restrictions_requested' => 'No organ retention beyond immediate histological processing',
                    'consent_given'          => true,
                    'consent_witnessed_by'   => 'Ms. TCHINDA NKENG, Laurence (Patient Relations)',
                    'pathologist'            => 'Dr. NGONO BIYA, Albert (Consultant Pathologist)',
                    'autopsy_scheduled'      => '09 June 2026, 09:00',
                ];

            case 'embalming-record':
                return [
                    'body_tag_number'        => 'OC-MRT-2026-00047',
                    'embalming_date'         => '08 June 2026',
                    'embalming_start'        => '10:00',
                    'embalming_end'          => '12:30',
                    'embalmer_name'          => 'Mr. TCHOUNGUI BELA, Pierre (Licensed Embalmer LIC-CM-2018-0047)',
                    'purpose'                => 'Preservation for funeral viewing — requested by family',
                    'procedure_performed'    => [
                        'Arterial embalming — carotid artery cannulation',
                        'Cavity treatment — thoracic and abdominal',
                        'Surface disinfection',
                        'Cosmetic restoration',
                    ],
                    'chemicals_used'         => [
                        ['product'=>'Formalin-based arterial fluid','volume_L'=>3.5,'concentration'=>'25 index'],
                        ['product'=>'Cavity fluid','volume_L'=>1.0,'concentration'=>'Standard'],
                        ['product'=>'Surface disinfectant spray','volume_L'=>0.5,'concentration'=>'N/A'],
                    ],
                    'ppe_used'               => 'Full barrier — gloves, gown, face shield, boots',
                    'infection_risk'         => 'Standard precautions — no notifiable pathogen',
                    'body_condition_after'   => 'Good — presentable for viewing',
                    'issues_encountered'     => 'None',
                    'supervisor'             => 'Mr. BELLO HAMIDOU, Mortuary Supervisor',
                ];

            case 'burial-permit':
                return [
                    'deceased_name'          => 'NJOMO EKAMBI, Marie Claire',
                    'date_of_death'          => '07 June 2026',
                    'place_of_death'         => 'OpesCare Central General Hospital — Yaoundé',
                    'cause_of_death'         => 'Hypertensive emergency — acute intracerebral haemorrhage',
                    'manner_of_death'        => 'Natural',
                    'death_certificate_number'=> 'OC-DTH-2026-000088',
                    'death_certificate_date' => '07 June 2026',
                    'next_of_kin_name'       => 'EKAMBI MPONDO, Joseph Blaise',
                    'next_of_kin_relationship'=> 'Husband',
                    'next_of_kin_contact'    => '+237 677 441 009',
                    'burial_location'        => 'Cimetière Municipal de Nkol-Afeme, Yaoundé III',
                    'burial_type'            => 'Inhumation (Burial)',
                    'civil_status_officer'   => 'M. FOUDA MBARGA, Aristide — Centre d\'État Civil, Yaoundé III',
                    'permit_expiry_date'     => '21 June 2026',
                    'pathological_risk'      => false,
                    'special_instructions'   => 'No special biohazard precautions required.',
                    'chain_of_custody'       => [
                        ['stage'=>'Hospital','status'=>'Body certified and released','date'=>'09 Jun 2026'],
                        ['stage'=>'Mortuary','status'=>'Received and documented','date'=>'09 Jun 2026'],
                        ['stage'=>'Burial','status'=>'Pending'],
                    ],
                ];

            case 'clinical-autopsy-report':
                return [
                    'autopsy_number'         => 'OC-CAR-2026-000097',
                    'autopsy_type'           => 'Clinical / Hospital Autopsy',
                    'date_of_death'          => '07 June 2026',
                    'time_of_death'          => '15:18',
                    'date_of_autopsy'        => '09 June 2026',
                    'pathologist_name'       => 'Dr. NGONO BIYA, Albert',
                    'pathologist_license'    => 'PATH-CM-2015-00088',
                    'clinical_diagnosis'     => 'Hypertensive emergency — suspected intracerebral haemorrhage',
                    'consent_obtained'       => 'Yes — Family',
                    'consent_type'           => 'Next of kin (husband)',
                    'external_examination'   => ['body_weight'=>'76 kg','body_height'=>'165 cm','body_condition'=>'Well nourished; no significant decomposition; embalmed','identifying_features'=>'Bilateral surgical scars (caesarean × 2)','external_injuries'=>'No traumatic injuries identified'],
                    'internal_examination'   => [
                        ['organ'=>'Brain','weight'=>'1,490g (↑)','gross_appearance'=>'Large right hemisphere haematoma 4.5×3.2cm with midline shift of 12mm. Cerebral oedema. Herniation — uncal grooving.','histology_result'=>'Hypertensive arteriolar changes; fibrinoid necrosis of small vessels'],
                        ['organ'=>'Heart','weight'=>'520g (↑ — LVH)','gross_appearance'=>'Concentric LV hypertrophy; wall thickness 1.6cm. No coronary atherosclerosis. Valves competent.','histology_result'=>'Myocardial fibrosis, hypertrophic cardiomyocytes'],
                        ['organ'=>'Kidneys (combined)','weight'=>'290g','gross_appearance'=>'Bilateral nephrosclerosis — granular cortices. No infarcts.','histology_result'=>'Arteriolosclerosis, glomerular sclerosis — consistent with hypertensive nephropathy'],
                        ['organ'=>'Lungs (combined)','weight'=>'1,100g (↑ — oedema)','gross_appearance'=>'Frothy fluid from cut surface — acute pulmonary oedema','histology_result'=>'Alveolar oedema, no infection'],
                    ],
                    'cause_of_death'         => ['immediate'=>'Acute intracerebral haemorrhage with transtentorial herniation','underlying'=>'Hypertensive emergency (BP 210/130 mmHg)','contributing'=>'Type 2 Diabetes Mellitus; Chronic Kidney Disease Stage 3a'],
                    'final_diagnosis'        => 'Massive right hemisphere hypertensive intracerebral haemorrhage with fatal transtentorial herniation',
                    'discrepancy_with_clinical' => 'No',
                    'discrepancy_notes'      => 'Autopsy confirms clinical diagnosis of hypertensive ICH',
                    'histology_specimens'    => [
                        ['specimen_label'=>'A1','site'=>'Brain — haematoma wall','fixative'=>'10% formalin','microscopy_findings'=>'Fibrinoid necrosis; haemosiderin-laden macrophages'],
                        ['specimen_label'=>'B1','site'=>'Kidney — right cortex','fixative'=>'10% formalin','microscopy_findings'=>'Arteriolosclerosis; focal tubular atrophy'],
                    ],
                    'toxicology_requested'   => 'No',
                    'toxicology_results'     => null,
                    'conclusions'            => 'Death was due to a massive hypertensive intracerebral haemorrhage. The autopsy findings are fully consistent with the clinical diagnosis. No unexpected findings.',
                    'pathologist_signature_date' => '11 June 2026',
                ];

            case 'forensic-autopsy-report':
                return [
                    'case_number'            => 'TGY-2026-CRM-00147',
                    'requesting_authority'   => 'Tribunal de Grande Instance — Yaoundé Centre (Procureur de la République)',
                    'investigating_officer'  => 'Insp. NKENGNE FOUDA, David — Commissariat Central Yaoundé IV',
                    'warrant_number'         => 'REQ-PARQ-2026-04412',
                    'warrant_date'           => '06 June 2026',
                    'scene_information'      => ['scene_type'=>'Residential — apartment 3rd floor','scene_description'=>'Body found supine in bedroom; no signs of forced entry; empty medication bottles on nightstand','scene_investigators'=>'Insp. NKENGNE FOUDA + Scene-of-crime technician MVONDO, Paul'],
                    'body_identification'    => ['method'=>'Visual identification by next of kin + National ID card','identified_by'=>'EKAMBI MPONDO, Joseph Blaise (husband)','identification_date'=>'06 June 2026'],
                    'date_of_death_estimated'=> '05 June 2026 (evening)',
                    'death_interval_estimate'=> '18–28 hours (rigor resolving; lividity fixed)',
                    'external_injuries'      => [
                        ['injury_type'=>'Contusion','location'=>'Right occiput','dimensions'=>'3×2cm','characteristics'=>'Reddish-purple bruise, no laceration','interpretation'=>'Blunt force impact — consistent with fall against hard surface'],
                        ['injury_type'=>'Petechial haemorrhages','location'=>'Bilateral conjunctivae','dimensions'=>'Multiple pinpoint','characteristics'=>'Fresh petechiae','interpretation'=>'Consistent with raised intracranial pressure; non-specific'],
                    ],
                    'internal_findings'      => [
                        ['organ'=>'Brain','weight'=>'1,510g','findings'=>'Large right frontal subarachnoid haemorrhage; no subdural collection; no contrecoup injury'],
                        ['organ'=>'Heart','weight'=>'510g','findings'=>'LVH — wall thickness 1.5cm; no coronary thrombosis'],
                        ['organ'=>'Neck structures','weight'=>'N/A','findings'=>'No petechiae of strap muscles; thyroid cartilage intact; no asphyxia signs'],
                    ],
                    'cause_of_death'         => ['immediate'=>'Subarachnoid haemorrhage with cerebral compression','underlying'=>'Hypertensive emergency — ruptured cerebral microaneurysm','mechanism'=>'Raised intracranial pressure → brainstem compression → cardiorespiratory arrest'],
                    'manner_of_death'        => 'Natural',
                    'toxicology'             => ['specimens_collected'=>'Femoral blood, vitreous humour, urine, liver','substances_detected'=>'Nifedipine (therapeutic), Metformin (therapeutic); no illicit substances','blood_alcohol_level'=>'< 10mg/100mL (negligible)'],
                    'trace_evidence'         => ['collected'=>false,'description'=>'No trace evidence collected — natural death determination','chain_of_custody_number'=>'N/A'],
                    'opinion_and_conclusions'=> 'The death of NJOMO EKAMBI, Marie Claire was due to natural causes — a hypertensive cerebrovascular event. There is no evidence of homicide, suicide, or accident. The occipital contusion is consistent with a fall secondary to the acute neurological event. No homicidal violence identified.',
                    'forensic_pathologist_name' => 'Prof. ABOMO NKENG, Jean-Pierre (Medecin Légiste Agréé)',
                    'forensic_pathologist_license' => 'FPATH-CM-2008-00012',
                    'report_date'            => '10 June 2026',
                ];

            // ── Batch G — Death Review / Mortuary continued ───────────────────────
            case 'maternal-death-review':
                return [
                    'review_date'            => '10 June 2026',
                    'review_type'            => 'Hospital Maternal Death Review (HMDR)',
                    'death_date'             => '07 June 2026',
                    'death_cause'            => 'Hypertensive emergency — eclampsia-related ICH (post-partum)',
                    'who_classification'     => 'Direct maternal death',
                    'gestational_status'     => '6 weeks post-partum',
                    'avoidable_factors'      => [
                        ['category'=>'Patient/Community','factor'=>'Delayed presentation — hypertension symptoms for 3 days before hospital attendance'],
                        ['category'=>'Health System','factor'=>'No structured post-partum BP monitoring protocol at community level'],
                    ],
                    'substandard_care'       => false,
                    'good_practice_noted'    => 'Rapid IV antihypertensive treatment initiated; CT head obtained promptly; ICU bed arranged',
                    'recommendations'        => [
                        'Introduce structured 6-week post-partum maternal BP follow-up protocol',
                        'Community health worker training on hypertension danger signs',
                        'Audit BP management at ANC level',
                    ],
                    'review_panel'           => ['Dr. MBASSI ATEBA (Chair)','Sr. NJOYA ATEBA (Nurse)','Dr. ESSAMA BELA (Obstetrics)','Ms. AWAH BELLO (Social Work)'],
                    'minsante_notification_due' => '17 June 2026',
                ];

            case 'perinatal-mortality-review':
                return [
                    'review_date'            => '10 June 2026',
                    'review_type'            => 'Facility Perinatal Death Review',
                    'neonatal_or_stillbirth' => 'Stillbirth (intrapartum)',
                    'death_date'             => '07 June 2026',
                    'gestational_age'        => '36 weeks + 2 days',
                    'birth_weight_g'         => 2640,
                    'perinatal_cause'        => 'Acute intrapartum asphyxia — umbilical cord prolapse',
                    'avoidable'              => 'Possibly avoidable — delayed diagnosis of cord prolapse at community clinic before transfer',
                    'antepartum_monitoring'  => 'ANC attendance good (6 visits); no risk factor identified for cord prolapse',
                    'intrapartum_care'       => 'Cord prolapse diagnosed at 03:15; decision-to-delivery interval 33 minutes (LSCS); above recommended < 30min',
                    'modifiable_factors'     => [
                        'Decision-to-delivery interval exceeded target',
                        'Operating theatre readiness time 18 minutes — above 15-minute standard',
                    ],
                    'recommendations'        => [
                        'Emergency obstetric drills for cord prolapse — quarterly',
                        'Theatre activation protocol review',
                        'Community midwife training — cord prolapse recognition',
                    ],
                    'review_panel'           => ['Dr. ESSAMA BELA (Chair — Obstetrician)','Sr. ABONO ELONGO (Midwife)','Dr. AMOUGOU FOUDA (Neonatologist)'],
                ];

            case 'coroners-notification':
                return [
                    'notification_date'      => '07 June 2026',
                    'notification_time'      => '16:00',
                    'notified_to'            => 'Officier de Police Judiciaire — Commissariat Yaoundé IV',
                    'notification_reference' => 'OPN-YJIV-2026-00847',
                    'reason_for_notification'=> 'Death within 24 hours of hospital admission (policy requirement)',
                    'circumstances'          => 'Patient admitted via A&E with hypertensive emergency; died 17 hours after admission. Clear clinical trajectory — no suspicious circumstances. Death certificate issued.',
                    'time_of_death'          => '07 June 2026, 15:18',
                    'place_of_death'         => 'OpesCare Central Hospital — Internal Medicine Ward 3B',
                    'cause_of_death_stated'  => 'Hypertensive emergency — intracerebral haemorrhage (clinical)',
                    'certifying_doctor'      => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'known_to_doctor'        => true,
                    'police_response'        => 'Received — no objection to burial; file closed 07 June 2026, 18:30',
                    'police_officer'         => 'Sgr. MVONDO FOUDA, Éric',
                    'autopsy_ordered_by_police'=> false,
                    'notified_by'            => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                ];

            case 'verbal-autopsy':
                return [
                    'interview_date'         => '10 June 2026',
                    'interview_location'     => 'Residence — Yaoundé Centre',
                    'va_tool'                => 'WHO 2016 Verbal Autopsy Instrument (adult)',
                    'respondent_name'        => 'EKAMBI MPONDO, Joseph Blaise',
                    'respondent_relationship'=> 'Husband',
                    'illness_duration_days'  => 3,
                    'symptoms_reported'      => [
                        ['symptom'=>'Severe headache','onset_days_before_death'=>3,'severity'=>'Severe'],
                        ['symptom'=>'Blurred vision','onset_days_before_death'=>2,'severity'=>'Moderate'],
                        ['symptom'=>'Vomiting','onset_days_before_death'=>2,'severity'=>'Moderate'],
                        ['symptom'=>'Confusion','onset_days_before_death'=>1,'severity'=>'Severe'],
                        ['symptom'=>'Loss of consciousness','onset_days_before_death'=>0,'severity'=>'Severe'],
                    ],
                    'chronic_conditions_known' => 'Hypertension (5 years), Diabetes (3 years)',
                    'medications_at_time'    => 'Nifedipine 30mg daily; Metformin 500mg BD',
                    'healthcare_sought'      => true,
                    'facilities_visited'     => ['Pharmacie du Centre (self-medicated BP tablets)','OpesCare Central Hospital A&E (Day of death)'],
                    'cause_of_death_opinion' => 'Hypertension / stroke',
                    'va_physician_review'    => 'Dr. MBASSI ATEBA — Verbal autopsy consistent with ICD-10 I62.9 (Intracerebral haemorrhage, unspecified)',
                    'icd10_code'             => 'I62.9',
                    'interviewer'            => 'Sr. NJOYA ATEBA, Marie-Thérèse',
                ];

            case 'mortuary-storage-log':
                return [
                    'body_tag_number'        => 'OC-MRT-2026-00047',
                    'storage_unit'           => 'Bay 1, Refrigerated Unit A, Tray 3',
                    'temperature_zone'       => '2–4°C',
                    'admission_datetime'     => '07 June 2026, 15:45',
                    'expected_release_date'  => '09 June 2026',
                    'body_condition_on_admission' => 'Good',
                    'preservation_method'    => 'Refrigeration',
                    'daily_inspection_log'   => [
                        ['date'=>'07 Jun 2026','time'=>'20:00','inspector_name'=>'Mr. BELLO HAMIDOU','condition_noted'=>'Good — no changes','temperature_recorded'=>'3.2°C','action_taken'=>'Routine check'],
                        ['date'=>'08 Jun 2026','time'=>'08:00','inspector_name'=>'Mr. BELLO HAMIDOU','condition_noted'=>'Good — embalming completed 12:30','temperature_recorded'=>'3.0°C','action_taken'=>'Post-embalming check'],
                        ['date'=>'08 Jun 2026','time'=>'20:00','inspector_name'=>'Mr. TCHOUNGUI BELA','condition_noted'=>'Good — no changes','temperature_recorded'=>'3.1°C','action_taken'=>'Routine check'],
                    ],
                    'release_datetime'       => '09 June 2026, 10:30',
                    'released_to'            => 'EKAMBI MPONDO, Joseph Blaise (Husband) + Pompes Funèbres Sainte-Cécile',
                    'release_authorized_by'  => 'Dr. MBASSI ATEBA, Emmanuel Pierre',
                    'remarks'                => 'Body released in good condition. Burial permit OC-BPN-2026-000096 verified.',
                ];

            case 'body-identification':
                return [
                    'body_tag_number'        => 'OC-MRT-2026-00047',
                    'mortuary_admission_number' => 'OC-BRF-2026-000092',
                    'date_of_admission'      => '07 June 2026',
                    'identification_status'  => 'Positive',
                    'identification_method'  => ['Visual identification','National ID card'],
                    'primary_identifier'     => ['name'=>'EKAMBI MPONDO, Joseph Blaise','relationship'=>'Husband','contact'=>'+237 677 441 009','id_type'=>'National Identity Card','id_number'=>'CNI-CM-1991-004472'],
                    'secondary_identifier'   => ['name'=>'NJOMO ASSAMBA, Régine','relationship'=>'Sister','contact'=>'+237 694 882 011','id_type'=>'National Identity Card','id_number'=>'CNI-CM-1998-007831'],
                    'physical_description'   => ['estimated_age'=>41,'height_cm'=>165,'weight_kg'=>76,'build'=>'Medium','skin_complexion'=>'Dark brown','hair_colour_type'=>'Black, natural','eye_colour'=>'Dark brown','scars_marks_tattoos'=>'Bilateral lower abdominal Pfannenstiel scars (caesarean × 2)','clothing_description'=>'Hospital gown; no personal clothing'],
                    'dental_chart_notes'     => 'Not completed — identity positively established by visual + ID',
                    'fingerprint_reference_number' => 'N/A — not required',
                    'dna_sample_collected'   => false,
                    'dna_lab_reference'      => null,
                    'personal_effects'       => [
                        ['item'=>'Wedding ring','description'=>'Yellow gold plain band','quantity'=>1,'condition'=>'Good','storage_location'=>'Mortuary Safe B-003'],
                    ],
                    'police_reference_number'=> 'OPN-YJIV-2026-00847',
                    'investigating_officer'  => 'Sgr. MVONDO FOUDA, Éric',
                    'police_station'         => 'Commissariat Yaoundé IV',
                    'identification_confirmed_by' => ['name'=>'Mr. BELLO HAMIDOU','role'=>'Mortuary Supervisor','date'=>'07 June 2026, 16:00'],
                    'discrepancies_noted'    => '',
                    'chain_of_custody'       => [
                        ['action'=>'Body received from ward','performed_by'=>'Mr. BELLO HAMIDOU','date_time'=>'07 Jun 2026, 15:45','signature_reference'=>'BH-001'],
                        ['action'=>'Identity confirmed by next of kin','performed_by'=>'Mr. BELLO HAMIDOU','date_time'=>'07 Jun 2026, 16:00','signature_reference'=>'BH-002'],
                        ['action'=>'Body released to family','performed_by'=>'Mr. BELLO HAMIDOU','date_time'=>'09 Jun 2026, 10:30','signature_reference'=>'BH-003'],
                    ],
                ];

            default:
                return [];
        }
    }

    private function documentTypes(): array
    {
        return [
            // ── Original 15 ──────────────────────────────────────────────────────────
            ['slug' => 'prescription',           'name' => 'Medical Prescription',              'code' => 'RX',   'color' => '#0F4C81', 'built' => true],
            ['slug' => 'lab-result',             'name' => 'Laboratory Report',                 'code' => 'LAB',  'color' => '#7C3AED', 'built' => true],
            ['slug' => 'invoice',                'name' => 'Medical Invoice',                   'code' => 'INV',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'receipt',                'name' => 'Payment Receipt',                   'code' => 'REC',  'color' => '#059669', 'built' => true],
            ['slug' => 'discharge-summary',      'name' => 'Discharge Summary',                 'code' => 'DIS',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'referral-letter',        'name' => 'Referral Letter',                   'code' => 'REF',  'color' => '#D97706', 'built' => true],
            ['slug' => 'medical-certificate',    'name' => 'Medical Certificate',               'code' => 'MCD',  'color' => '#0F766E', 'built' => true],
            ['slug' => 'radiology-report',       'name' => 'Radiology Report',                  'code' => 'RAD',  'color' => '#1D4ED8', 'built' => true],
            ['slug' => 'antenatal-card',         'name' => 'Antenatal Care Card',               'code' => 'ANC',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'immunization-cert',      'name' => 'Immunization Certificate',          'code' => 'VAX',  'color' => '#7C3AED', 'built' => true],
            ['slug' => 'surgical-report',        'name' => 'Surgical Report',                   'code' => 'SUR',  'color' => '#B45309', 'built' => true],
            ['slug' => 'consent-form',           'name' => 'Informed Consent Form',             'code' => 'CNS',  'color' => '#4F46E5', 'built' => true],
            ['slug' => 'preauth-letter',         'name' => 'Pre-Authorization Letter',          'code' => 'PAL',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'birth-notification',     'name' => 'Birth Notification',                'code' => 'BNF',  'color' => '#D97706', 'built' => true],
            ['slug' => 'care-plan',              'name' => 'Patient Care Plan',                 'code' => 'CPL',  'color' => '#0F766E', 'built' => true],
            // ── New 13 ───────────────────────────────────────────────────────────────
            ['slug' => 'narcotic-prescription',  'name' => 'Narcotic Prescription',             'code' => 'NRX',  'color' => '#B45309', 'built' => true],
            ['slug' => 'death-certificate',      'name' => 'Death Certificate',                 'code' => 'DTH',  'color' => '#374151', 'built' => true],
            ['slug' => 'death-summary',          'name' => 'Clinical Death Summary',            'code' => 'DSU',  'color' => '#1F2937', 'built' => true],
            ['slug' => 'transfer-letter',        'name' => 'Patient Transfer Letter',           'code' => 'TRF',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'pathology-report',       'name' => 'Pathology Report',                  'code' => 'PATH', 'color' => '#6D28D9', 'built' => true],
            ['slug' => 'arv-card',               'name' => 'HIV/ART Treatment Card',            'code' => 'ARV',  'color' => '#0F766E', 'built' => true],
            ['slug' => 'tb-dots-card',           'name' => 'TB Treatment Card (DOTS)',          'code' => 'DOTS', 'color' => '#B45309', 'built' => true],
            ['slug' => 'psychiatric-assessment', 'name' => 'Psychiatric Assessment',            'code' => 'PSY',  'color' => '#4F46E5', 'built' => true],
            ['slug' => 'opd-summary',            'name' => 'OPD Consultation Summary',          'code' => 'OPD',  'color' => '#0F4C81', 'built' => true],
            ['slug' => 'insurance-claim',        'name' => 'Insurance Claim Form',              'code' => 'CLM',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'fitness-certificate',    'name' => 'Fitness Certificate',               'code' => 'FIT',  'color' => '#059669', 'built' => true],
            ['slug' => 'blood-transfusion',      'name' => 'Blood Transfusion Record',          'code' => 'BTR',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'nursing-chart',          'name' => 'Nursing Observation Chart',         'code' => 'NRS',  'color' => '#0891B2', 'built' => true],
            // ── Final 22 ─────────────────────────────────────────────────────────────
            ['slug' => 'anaesthesia-record',     'name' => 'Anaesthesia Record',                'code' => 'ANS',  'color' => '#0F4C81', 'built' => true],
            ['slug' => 'lama-form',              'name' => 'Leave Against Medical Advice',      'code' => 'LAMA', 'color' => '#DC2626', 'built' => true],
            ['slug' => 'aer-report',             'name' => 'A&E Visit Report',                  'code' => 'AER',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'medicolegal-report',     'name' => 'Medicolegal Report',                'code' => 'MLR',  'color' => '#374151', 'built' => true],
            ['slug' => 'autopsy-report',         'name' => 'Post-Mortem Report',                'code' => 'PMR',  'color' => '#1F2937', 'built' => true],
            ['slug' => 'partograph',             'name' => 'Partograph (Labour Chart)',         'code' => 'PTG',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'newborn-assessment',     'name' => 'Newborn Assessment',                'code' => 'NBA',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'child-health-card',      'name' => 'Child Health Card (Under-5)',       'code' => 'CHC',  'color' => '#059669', 'built' => true],
            ['slug' => 'dialysis-record',        'name' => 'Dialysis Session Record',           'code' => 'DLY',  'color' => '#0891B2', 'built' => true],
            ['slug' => 'chemotherapy-record',    'name' => 'Chemotherapy Protocol Record',      'code' => 'CTX',  'color' => '#7C3AED', 'built' => true],
            ['slug' => 'echo-report',            'name' => 'Echocardiography Report',           'code' => 'ECHO', 'color' => '#1D4ED8', 'built' => true],
            ['slug' => 'endoscopy-report',       'name' => 'Endoscopy Report',                  'code' => 'ENDO', 'color' => '#059669', 'built' => true],
            ['slug' => 'physio-report',          'name' => 'Physiotherapy Report',              'code' => 'PHY',  'color' => '#0F766E', 'built' => true],
            ['slug' => 'medication-reconciliation','name' => 'Medication Reconciliation',       'code' => 'MRC',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'incident-report',        'name' => 'Clinical Incident Report',          'code' => 'INC',  'color' => '#F59E0B', 'built' => true],
            ['slug' => 'wound-care-chart',       'name' => 'Wound Care Chart',                  'code' => 'WND',  'color' => '#B45309', 'built' => true],
            ['slug' => 'postnatal-record',       'name' => 'Postnatal Care Record',             'code' => 'PNC',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'referral-acknowledgement','name' => 'Referral Acknowledgement',         'code' => 'RAL',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'admission-form',         'name' => 'Patient Admission Form',            'code' => 'ADM',  'color' => '#0F4C81', 'built' => true],
            ['slug' => 'pharmacy-record',        'name' => 'Drug Dispensing Record',            'code' => 'DPR',  'color' => '#059669', 'built' => true],
            ['slug' => 'adr-report',             'name' => 'Adverse Drug Reaction Report',      'code' => 'ADR',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'growth-chart',           'name' => 'Paediatric Growth Chart',           'code' => 'GCH',  'color' => '#059669', 'built' => true],
            // ── Batch A — Core Inpatient ──────────────────────────────────────────────
            ['slug' => 'medication-administration-record', 'name' => 'Medication Administration Record', 'code' => 'MAR',  'color' => '#0F4C81', 'built' => true],
            ['slug' => 'daily-progress-note',    'name' => 'Daily Progress Note',               'code' => 'PRG',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'surgical-safety-checklist','name'=> 'Surgical Safety Checklist',        'code' => 'SSC',  'color' => '#059669', 'built' => true],
            ['slug' => 'icu-flowsheet',          'name' => 'ICU Flowsheet',                     'code' => 'ICU',  'color' => '#1D4ED8', 'built' => true],
            ['slug' => 'investigation-request',  'name' => 'Investigation Request',             'code' => 'REQ',  'color' => '#6D28D9', 'built' => true],
            ['slug' => 'nursing-admission-assessment','name'=> 'Nursing Admission Assessment',  'code' => 'NAA',  'color' => '#0891B2', 'built' => true],
            // ── Batch B — Legal / Programmatic ───────────────────────────────────────
            ['slug' => 'stillbirth-certificate', 'name' => 'Stillbirth Certificate',            'code' => 'SBC',  'color' => '#374151', 'built' => true],
            ['slug' => 'aefi-report',            'name' => 'AEFI Report',                       'code' => 'AEF',  'color' => '#B45309', 'built' => true],
            ['slug' => 'notifiable-disease-report','name'=> 'Notifiable Disease Report',        'code' => 'NDR',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'malaria-report',         'name' => 'Malaria Diagnostic Report',         'code' => 'MAL',  'color' => '#059669', 'built' => true],
            ['slug' => 'hiv-counselling-record', 'name' => 'HIV Counselling Record',            'code' => 'HCR',  'color' => '#0F766E', 'built' => true],
            ['slug' => 'blood-bank-request',     'name' => 'Blood Bank Request',                'code' => 'BBR',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'postop-recovery-record', 'name' => 'Post-Op Recovery Record',           'code' => 'POR',  'color' => '#7C3AED', 'built' => true],
            // ── Batch C — Clinical ───────────────────────────────────────────────────
            ['slug' => 'ecg-report',             'name' => 'ECG Report',                        'code' => 'ECG',  'color' => '#1D4ED8', 'built' => true],
            ['slug' => 'fall-risk-assessment',   'name' => 'Fall Risk Assessment',              'code' => 'FRA',  'color' => '#F59E0B', 'built' => true],
            ['slug' => 'pressure-ulcer-assessment','name'=> 'Pressure Ulcer Assessment',        'code' => 'PUA',  'color' => '#B45309', 'built' => true],
            ['slug' => 'glucose-log',            'name' => 'Blood Glucose Log',                 'code' => 'DGL',  'color' => '#059669', 'built' => true],
            ['slug' => 'handover-note',          'name' => 'Clinical Handover Note',            'code' => 'HOV',  'color' => '#0891B2', 'built' => true],
            ['slug' => 'mental-health-involuntary','name'=> 'Involuntary Psychiatric Admission', 'code' => 'MHI',  'color' => '#4F46E5', 'built' => true],
            // ── Batch D — Specialist ─────────────────────────────────────────────────
            ['slug' => 'dnr-order',              'name' => 'Do Not Resuscitate Order',          'code' => 'DNR',  'color' => '#374151', 'built' => true],
            ['slug' => 'palliative-care-plan',   'name' => 'Palliative Care Plan',              'code' => 'PAL',  'color' => '#6D28D9', 'built' => true],
            ['slug' => 'occupational-therapy',   'name' => 'Occupational Therapy Assessment',   'code' => 'OTA',  'color' => '#0F766E', 'built' => true],
            ['slug' => 'speech-therapy-report',  'name' => 'Speech Therapy Report',             'code' => 'SLT',  'color' => '#0369A1', 'built' => true],
            ['slug' => 'nutritional-assessment', 'name' => 'Nutritional Assessment',            'code' => 'NTR',  'color' => '#059669', 'built' => true],
            ['slug' => 'social-work-assessment', 'name' => 'Social Work Assessment',            'code' => 'SWA',  'color' => '#B45309', 'built' => true],
            // ── Batch E — Specialist continued ──────────────────────────────────────
            ['slug' => 'orthopaedic-chart',      'name' => 'Orthopaedic Assessment Chart',      'code' => 'ORT',  'color' => '#1D4ED8', 'built' => true],
            ['slug' => 'resuscitation-record',   'name' => 'Resuscitation Record',              'code' => 'CPR',  'color' => '#DC2626', 'built' => true],
            ['slug' => 'nicu-chart',             'name' => 'NICU Chart',                        'code' => 'NIC',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'patient-complaint',      'name' => 'Patient Complaint Form',            'code' => 'PCF',  'color' => '#F59E0B', 'built' => true],
            ['slug' => 'procedure-consent',      'name' => 'Procedure Consent Form',            'code' => 'PCS',  'color' => '#059669', 'built' => true],
            // ── Batch F — Mortuary ───────────────────────────────────────────────────
            ['slug' => 'mortuary-admission',     'name' => 'Mortuary Admission Form',           'code' => 'BRF',  'color' => '#1F2937', 'built' => true],
            ['slug' => 'body-release',           'name' => 'Body Release Form',                 'code' => 'BRL',  'color' => '#374151', 'built' => true],
            ['slug' => 'autopsy-consent',        'name' => 'Autopsy Consent Form',              'code' => 'PMC',  'color' => '#4F46E5', 'built' => true],
            ['slug' => 'embalming-record',       'name' => 'Embalming Record',                  'code' => 'EMB',  'color' => '#6D28D9', 'built' => true],
            ['slug' => 'burial-permit',          'name' => 'Burial Permit',                     'code' => 'BPN',  'color' => '#374151', 'built' => true],
            ['slug' => 'clinical-autopsy-report','name' => 'Clinical Autopsy Report',           'code' => 'CAR',  'color' => '#1F2937', 'built' => true],
            ['slug' => 'forensic-autopsy-report','name' => 'Forensic Autopsy Report',           'code' => 'FAR',  'color' => '#7F1D1D', 'built' => true],
            // ── Batch G — Death Review / Mortuary continued ──────────────────────────
            ['slug' => 'maternal-death-review',  'name' => 'Maternal Death Review',             'code' => 'MDR',  'color' => '#DB2777', 'built' => true],
            ['slug' => 'perinatal-mortality-review','name'=> 'Perinatal Mortality Review',      'code' => 'PMV',  'color' => '#B45309', 'built' => true],
            ['slug' => 'coroners-notification',  'name' => 'Coroner\'s Notification',           'code' => 'CMN',  'color' => '#374151', 'built' => true],
            ['slug' => 'verbal-autopsy',         'name' => 'Verbal Autopsy',                    'code' => 'VBA',  'color' => '#1F2937', 'built' => true],
            ['slug' => 'mortuary-storage-log',   'name' => 'Mortuary Storage Log',              'code' => 'MSL',  'color' => '#0F4C81', 'built' => true],
            ['slug' => 'body-identification',    'name' => 'Body Identification Record',        'code' => 'BIR',  'color' => '#374151', 'built' => true],
        ];
    }

    private function dummyQrSvg(): string
    {
        return '<svg width="56" height="56" viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg">
        <rect width="56" height="56" fill="#0F172A" rx="3"/>
        <rect x="4" y="4" width="14" height="14" fill="white" rx="1"/>
        <rect x="6" y="6" width="10" height="10" fill="#0F172A" rx="1"/>
        <rect x="38" y="4" width="14" height="14" fill="white" rx="1"/>
        <rect x="40" y="6" width="10" height="10" fill="#0F172A" rx="1"/>
        <rect x="4" y="38" width="14" height="14" fill="white" rx="1"/>
        <rect x="6" y="40" width="10" height="10" fill="#0F172A" rx="1"/>
        <rect x="22" y="4" width="4" height="4" fill="white"/>
        <rect x="28" y="4" width="4" height="4" fill="white"/>
        <rect x="22" y="10" width="4" height="4" fill="white"/>
        <rect x="34" y="22" width="4" height="4" fill="white"/>
        <rect x="22" y="22" width="4" height="4" fill="white"/>
        <rect x="28" y="22" width="4" height="4" fill="white"/>
        <rect x="22" y="28" width="4" height="4" fill="white"/>
        <rect x="34" y="28" width="4" height="4" fill="white"/>
        <rect x="28" y="34" width="4" height="4" fill="white"/>
        <rect x="34" y="34" width="4" height="4" fill="white"/>
        <rect x="22" y="40" width="4" height="4" fill="white"/>
        <rect x="28" y="40" width="4" height="4" fill="white"/>
        <rect x="22" y="46" width="4" height="4" fill="white"/>
        <rect x="40" y="22" width="4" height="4" fill="white"/>
        <rect x="46" y="22" width="4" height="4" fill="white"/>
        <rect x="46" y="28" width="4" height="4" fill="white"/>
        <rect x="40" y="34" width="4" height="4" fill="white"/>
        <rect x="46" y="40" width="4" height="4" fill="white"/>
        <rect x="40" y="46" width="4" height="4" fill="white"/>
        <rect x="46" y="46" width="4" height="4" fill="white"/>
    </svg>';
    }
}
