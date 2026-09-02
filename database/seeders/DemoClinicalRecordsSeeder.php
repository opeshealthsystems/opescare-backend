<?php
namespace Database\Seeders;

use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeds demo clinical records: visits, appointments, triage, vitals,
 * clinical notes, diagnoses, allergy records, billing accounts, invoices,
 * and queue tickets so all staff and patient dashboards show real data.
 *
 * Idempotent – safe to run multiple times.
 */
class DemoClinicalRecordsSeeder extends Seeder
{
    // Her real hospital, resolved at run time from the 897-row directory.
    // This used to be a hardcoded id for an invented facility called "Demo
    // Central Hospital", whose name rendered straight into the patient's own
    // timeline — a visit to a hospital that does not exist, in an app whose
    // whole claim is that it holds real MINSANTE institutions.
    private const FAC_FALLBACK = '00000000-0000-0000-0000-100000000001';

    private string $fac = self::FAC_FALLBACK;
    // Demo users
    private const DOC    = '00000000-0000-0000-0000-200000000001';  // Dr. Amara Diallo
    private const NURSE  = '00000000-0000-0000-0000-200000000010';  // Nurse Fatou
    // Demo patients
    private const PAT1   = '00000000-0000-0000-0000-300000000001';  // Jean Dupont
    private const PAT2   = '00000000-0000-0000-0000-300000000002';  // Child patient
    private const PAT3   = '00000000-0000-0000-0000-300000000003';  // Emergency patient
    // Stable UUIDs
    private const VISIT1 = '00000000-0000-0000-0001-100000000001';
    private const VISIT2 = '00000000-0000-0000-0001-100000000002';
    private const VISIT3 = '00000000-0000-0000-0001-100000000003';
    private const BACCT1 = '00000000-0000-0000-0002-100000000001';
    private const BACCT2 = '00000000-0000-0000-0002-100000000002';
    private const BACCT3 = '00000000-0000-0000-0002-100000000003';

    public function run(): void
    {
        $now = Carbon::now();

        // Real institution first; fall back to the legacy demo row only if the
        // directory has not been seeded yet, so this never writes a clinical
        // record against a facility that does not exist.
        $this->fac = DemoFacilityResolver::primaryHospital() ?? self::FAC_FALLBACK;

        // ── Billing accounts ─────────────────────────────────────────
        $this->upsertBillingAccount(self::BACCT1, self::PAT1, $this->fac, 45000);
        $this->upsertBillingAccount(self::BACCT2, self::PAT2, $this->fac, 0);
        $this->upsertBillingAccount(self::BACCT3, self::PAT3, $this->fac, 120000);

        // ── Visits ───────────────────────────────────────────────────
        $this->upsertVisit(self::VISIT1, self::PAT1, $this->fac, self::DOC, 'outpatient', 'closed',
            $now->copy()->subDays(3)->setTime(9, 0),
            $now->copy()->subDays(3)->setTime(10, 30));
        $this->upsertVisit(self::VISIT2, self::PAT2, $this->fac, self::DOC, 'outpatient', 'open',
            $now->copy()->setTime(8, 30), null);
        $this->upsertVisit(self::VISIT3, self::PAT3, $this->fac, self::DOC, 'emergency', 'closed',
            $now->copy()->subDays(1)->setTime(2, 15),
            $now->copy()->subDays(1)->setTime(5, 45));

        // ── Triage records ───────────────────────────────────────────
        $t1 = '00000000-0000-0000-0003-100000000001';
        $t2 = '00000000-0000-0000-0003-100000000002';
        $t3 = '00000000-0000-0000-0003-100000000003';
        $this->upsertTriage($t1, self::VISIT1, self::NURSE, 'Persistent cough and fever for 4 days', 4, 'II');
        $this->upsertTriage($t2, self::VISIT2, self::NURSE, 'Routine paediatric check-up', 1, 'V');
        $this->upsertTriage($t3, self::VISIT3, self::NURSE, 'Severe chest pain, difficulty breathing', 9, 'I');

        // ── Vital signs ──────────────────────────────────────────────
        $this->upsertVitals('00000000-0000-0000-0004-100000000001', $t1, 38.4, 120, 80, 102, 18, 96.5, 70.0, 172.0);
        $this->upsertVitals('00000000-0000-0000-0004-100000000002', $t2, 36.8, 100, 65, 88, 20, 99.0, 22.0, 115.0);
        $this->upsertVitals('00000000-0000-0000-0004-100000000003', $t3, 37.1, 158, 98, 110, 28, 91.0, 85.0, 178.0);

        // ── Clinical notes ───────────────────────────────────────────
        $this->upsertClinicalNote(
            '00000000-0000-0000-0005-100000000001', self::VISIT1, self::DOC,
            'Patient presents with 4 days of productive cough, low-grade fever (38.4°C) and malaise.',
            'Reduced air entry at right base. Mild crackles. No wheeze.',
            'Amoxicillin 500 mg TDS × 5 days. Paracetamol 1 g PRN. Review in 48 hrs.', 'signed');
        $this->upsertClinicalNote(
            '00000000-0000-0000-0005-100000000002', self::VISIT2, self::DOC,
            'Child presenting for 12-month well-child visit. Developmental milestones on track.',
            'Alert, interactive. Weight and height within 50th percentile. No abnormality detected.',
            'Routine vaccinations administered. Next review at 15 months.', 'draft');
        $this->upsertClinicalNote(
            '00000000-0000-0000-0005-100000000003', self::VISIT3, self::DOC,
            'Sudden-onset severe central chest pain radiating to left arm. Diaphoresis present.',
            'ST elevation in II, III, aVF. Troponin 2.3 ng/mL elevated.',
            'STEMI protocol initiated. Thrombolysis given. Cardiology consulted. ICU transfer.', 'signed');

        // ── Diagnoses ────────────────────────────────────────────────
        $this->upsertDiagnosis('00000000-0000-0000-0006-100000000001', self::PAT1, self::VISIT1, self::DOC,
            'ICD-10', 'J18.9', 'Pneumonia, unspecified', 'resolved', true);
        $this->upsertDiagnosis('00000000-0000-0000-0006-100000000002', self::PAT2, self::VISIT2, self::DOC,
            'ICD-10', 'Z00.129', 'Encounter for routine child health examination', 'active', true);
        $this->upsertDiagnosis('00000000-0000-0000-0006-100000000003', self::PAT3, self::VISIT3, self::DOC,
            'ICD-10', 'I21.1', 'ST elevation myocardial infarction of inferior wall', 'active', true);

        // ── Allergy records ──────────────────────────────────────────
        $this->upsertAllergy('00000000-0000-0000-0007-100000000001', self::PAT1, self::DOC,
            'Penicillin', 'high', 'active');
        $this->upsertAllergy('00000000-0000-0000-0007-100000000002', self::PAT3, self::DOC,
            'Aspirin', 'moderate', 'active');

        // ── Today's appointments ─────────────────────────────────────
        $this->upsertAppointment(
            '00000000-0000-0000-0008-100000000001', self::PAT1, $this->fac, self::DOC,
            'outpatient', 'scheduled', $now->copy()->setTime(10, 0), 'Follow-up: pneumonia recovery');
        $this->upsertAppointment(
            '00000000-0000-0000-0008-100000000002', self::PAT2, $this->fac, self::DOC,
            'outpatient', 'scheduled', $now->copy()->setTime(11, 30), 'Paediatric immunisation');
        $this->upsertAppointment(
            '00000000-0000-0000-0008-100000000003', self::PAT3, $this->fac, self::DOC,
            'specialist', 'checked_in', $now->copy()->setTime(9, 0), 'Cardiology follow-up post-STEMI');
        $this->upsertAppointment(
            '00000000-0000-0000-0008-100000000004', self::PAT1, $this->fac, self::DOC,
            'outpatient', 'scheduled', $now->copy()->addDays(2)->setTime(14, 0), 'Routine blood pressure check');

        // ── Invoices ─────────────────────────────────────────────────
        $this->upsertInvoice(
            '00000000-0000-0000-0009-100000000001', self::BACCT1, self::PAT1, $this->fac, self::VISIT1,
            'DEMO-INV-001', 'issued', 45000, 45000, $now->copy()->subDays(3));
        $this->upsertInvoice(
            '00000000-0000-0000-0009-100000000002', self::BACCT3, self::PAT3, $this->fac, self::VISIT3,
            'DEMO-INV-002', 'issued', 350000, 350000, $now->copy()->subDays(1));
        $this->upsertInvoice(
            '00000000-0000-0000-0009-100000000003', self::BACCT1, self::PAT1, $this->fac, null,
            'DEMO-INV-003', 'issued', 15000, 15000, $now->copy());

        // ── Active queue tickets ──────────────────────────────────────
        $this->upsertQueueTicket(
            '00000000-0000-0000-0010-100000000001', self::PAT1, $this->fac, 'A001', 'outpatient', 'waiting', 5);
        $this->upsertQueueTicket(
            '00000000-0000-0000-0010-100000000002', self::PAT2, $this->fac, 'A002', 'outpatient', 'called', 5);
        $this->upsertQueueTicket(
            '00000000-0000-0000-0010-100000000003', self::PAT3, $this->fac, 'E001', 'emergency', 'service_started', 1);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function upsertBillingAccount(string $id, string $patientId, string $facilityId, float $balance): void
    {
        if (DB::table('billing_accounts')->where('id', $id)->doesntExist()) {
            DB::table('billing_accounts')->insert([
                'id' => $id, 'patient_id' => $patientId, 'facility_id' => $facilityId,
                'status' => 'active', 'outstanding_balance_amount' => $balance,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertVisit(string $id, string $patientId, string $facilityId, string $providerId,
        string $type, string $status, Carbon $startedAt, ?Carbon $endedAt): void
    {
        if (DB::table('visits')->where('id', $id)->doesntExist()) {
            DB::table('visits')->insert([
                'id' => $id, 'patient_id' => $patientId, 'facility_id' => $facilityId,
                'provider_id' => $providerId, 'visit_type' => $type, 'status' => $status,
                'started_at' => $startedAt, 'ended_at' => $endedAt,
                'is_demo' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertTriage(string $id, string $visitId, string $nurseId,
        string $complaint, int $pain, string $acuity): void
    {
        if (DB::table('triage_records')->where('id', $id)->doesntExist()) {
            DB::table('triage_records')->insert([
                'id' => $id, 'visit_id' => $visitId, 'nurse_id' => $nurseId,
                'presenting_complaint' => $complaint, 'pain_score' => $pain,
                'acuity_score' => $acuity, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertVitals(string $id, string $triageId, float $temp,
        int $bpSys, int $bpDia, int $pulse, int $rr, float $spo2, float $weight, float $height): void
    {
        if (DB::table('vital_signs')->where('id', $id)->doesntExist()) {
            DB::table('vital_signs')->insert([
                'id' => $id, 'triage_record_id' => $triageId,
                'temperature' => $temp, 'blood_pressure_systolic' => $bpSys,
                'blood_pressure_diastolic' => $bpDia, 'pulse' => $pulse,
                'respiratory_rate' => $rr, 'oxygen_saturation' => $spo2,
                'weight' => $weight, 'height' => $height,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertClinicalNote(string $id, string $visitId, string $providerId,
        string $hpi, string $exam, string $plan, string $status): void
    {
        if (DB::table('clinical_notes')->where('id', $id)->doesntExist()) {
            DB::table('clinical_notes')->insert([
                'id' => $id, 'visit_id' => $visitId, 'provider_id' => $providerId,
                'history_of_present_illness' => $hpi, 'examination_findings' => $exam,
                'treatment_plan' => $plan, 'status' => $status,
                'signed_at' => ($status === 'signed') ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertDiagnosis(string $id, string $patientId, string $visitId,
        string $providerId, string $system, string $code, string $name,
        string $status, bool $isPrimary): void
    {
        if (DB::table('diagnoses')->where('id', $id)->doesntExist()) {
            DB::table('diagnoses')->insert([
                'id' => $id, 'patient_id' => $patientId, 'visit_id' => $visitId,
                'provider_id' => $providerId, 'code_system' => $system, 'code' => $code,
                'display_name' => $name, 'status' => $status, 'is_primary' => $isPrimary,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertAllergy(string $id, string $patientId, string $providerId,
        string $substance, string $severity, string $status): void
    {
        if (DB::table('allergy_records')->where('id', $id)->doesntExist()) {
            DB::table('allergy_records')->insert([
                'id' => $id, 'patient_id' => $patientId, 'provider_id' => $providerId,
                'substance' => $substance, 'severity' => $severity, 'status' => $status,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertAppointment(string $id, string $patientId, string $facilityId,
        string $providerId, string $type, string $status, Carbon $scheduledAt, string $reason): void
    {
        if (DB::table('appointments')->where('id', $id)->doesntExist()) {
            DB::table('appointments')->insert([
                'id' => $id, 'patient_id' => $patientId, 'facility_id' => $facilityId,
                'provider_id' => $providerId, 'appointment_type' => $type, 'status' => $status,
                'scheduled_at' => $scheduledAt, 'reason' => $reason,
                'billing_deferred' => true, 'telemedicine_deferred' => true,
                'checked_in_at' => ($status === 'checked_in') ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertInvoice(string $id, string $billingAccountId, string $patientId,
        string $facilityId, ?string $visitId, string $number, string $status,
        float $subtotal, float $patientResp, Carbon $issuedAt): void
    {
        if (DB::table('invoices')->where('id', $id)->doesntExist()) {
            DB::table('invoices')->insert([
                'id' => $id, 'billing_account_id' => $billingAccountId,
                'patient_id' => $patientId, 'facility_id' => $facilityId,
                'visit_id' => $visitId, 'invoice_number' => $number, 'status' => $status,
                'subtotal_amount' => $subtotal, 'discount_amount' => 0,
                'insurance_covered_amount' => 0,
                'patient_responsibility_amount' => $patientResp,
                'paid_amount' => 0, 'refunded_amount' => 0, 'balance_amount' => $patientResp,
                'issued_at' => $issuedAt, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function upsertQueueTicket(string $id, string $patientId, string $facilityId,
        string $number, string $queue, string $status, int $priority): void
    {
        if (DB::table('queue_tickets')->where('id', $id)->doesntExist()) {
            DB::table('queue_tickets')->insert([
                'id' => $id, 'patient_id' => $patientId, 'facility_id' => $facilityId,
                'queue_number' => $number, 'current_queue' => $queue,
                'status' => $status, 'priority_level' => $priority,
                'checked_in_at' => now(),
                'called_at' => in_array($status, ['called', 'service_started']) ? now() : null,
                'service_started_at' => ($status === 'service_started') ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
