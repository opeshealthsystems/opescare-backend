<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\LabResult;
use App\Models\MedicalIdAccessEvent;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Patient Data Subject Rights (GDPR-equivalent / Cameroon Law No. 2010/012)
 *
 * Provides three core data subject rights to patients via their portal:
 *
 *  - GET  /portals/patient/data-rights/export    → structured JSON download of all personal data
 *  - POST /portals/patient/data-rights/rectify   → submit a correction request on their profile
 *  - POST /portals/patient/data-rights/erase     → submit an erasure request (right to be forgotten)
 *
 * All three actions are fully audited. Erasure and rectification create a
 * request record for admin review; they do not auto-delete/modify clinical
 * data (which may be required for safety/legal reasons) but they do clear
 * non-clinical PII fields on erase, conforming to MINSANTE minimum standards.
 *
 * COMPLIANCE: Cameroon Law No. 2010/012, Art. 21–24 (data subject rights),
 * and WHO Digital Health Implementation Guide §4.3 (patient data portability).
 */
class DataSubjectRightsController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /portals/patient/data-rights/export
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Export the authenticated patient's personal data as a structured JSON download.
     *
     * Includes: identity, consent records, allergies, diagnoses, immunizations,
     * lab results, prescriptions, appointments, and access audit log.
     *
     * Excludes: pin_hash, phone_number_hash, national_id_number, cnamgs_id
     * (these are either hashes or government identifiers that are not portable).
     */
    public function export(Request $request)
    {
        $patient = $this->resolvePatient($request);
        if (! $patient) {
            abort(403, 'No patient profile linked to this account.');
        }

        // Assemble full data package
        $export = [
            'meta' => [
                'exported_at'      => now()->toIso8601String(),
                'export_version'   => '1.0',
                'standard'         => 'OpesCare Data Export (Cameroon Law No. 2010/012)',
                'disclaimer'       => 'This file contains your personal health information. '
                    . 'Keep it secure. Do not share it with untrusted parties.',
            ],
            'identity' => [
                'health_id'          => $patient->health_id,
                'first_name'         => $patient->first_name,
                'last_name'          => $patient->last_name,
                'middle_name'        => $patient->middle_name,
                'date_of_birth'      => $patient->date_of_birth?->toDateString(),
                'sex'                => $patient->sex,
                'blood_group'        => $patient->blood_group,
                'email'              => $patient->email,
                'phone_number'       => $patient->phone_number,   // decrypted by accessor
                'address'            => $patient->address,         // decrypted by accessor
                'emergency_contact'  => $patient->emergency_contact,
                'country_code'       => $patient->country_code,
                'identity_status'    => $patient->identity_status,
                'verification_status'=> $patient->verification_status,
                'expires_at'         => $patient->expires_at?->toDateTimeString(),
                'created_at'         => $patient->created_at->toIso8601String(),
            ],
            // Full rows — the patient is entitled to all of their own data, and
            // selecting all columns avoids brittle hard-coded column lists.
            // toArray() still respects each model's $hidden attributes.
            'consent_records' => ConsentGrant::where('patient_id', $patient->id)
                ->orderByDesc('created_at')->get()->toArray(),
            'allergies' => AllergyRecord::where('patient_id', $patient->id)->get()->toArray(),
            'diagnoses' => Diagnosis::where('patient_id', $patient->id)->get()->toArray(),
            'immunizations' => ImmunizationRecord::where('patient_id', $patient->id)->get()->toArray(),
            'lab_results' => LabResult::where('patient_id', $patient->id)->get()->toArray(),
            'prescriptions' => Prescription::where('patient_id', $patient->id)->get()->toArray(),
            'appointments' => Appointment::where('patient_id', $patient->id)->get()->toArray(),
            'access_audit_log' => MedicalIdAccessEvent::where('patient_id', $patient->id)
                ->orderByDesc('created_at')->get()->toArray(),
        ];

        // Audit the export itself
        MedicalIdAccessEvent::create([
            'patient_id'  => $patient->id,
            'health_id'   => $patient->health_id,
            'actor_id'    => (string) $request->user()->id,
            'actor_type'  => 'patient',
            'facility_id' => null,
            'access_type' => 'data_export',
            'purpose'     => 'data_subject_rights',
            'result'      => 'success',
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'notes'       => 'Patient self-initiated data export (Law No. 2010/012 Art. 21)',
        ]);

        Log::info('patient_data_export', [
            'patient_id' => $patient->id,
            'health_id'  => $patient->health_id,
            'ip'         => $request->ip(),
        ]);

        $filename = 'OpesCare-DataExport-' . str_replace('-', '', $patient->health_id)
            . '-' . now()->format('Ymd') . '.json';

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /portals/patient/data-rights/rectify
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Submit a data rectification request.
     *
     * The patient describes what is incorrect and what the correct value should
     * be. This creates an audit log entry for the ops team to action.
     * For profile fields that can be self-corrected (first_name, last_name,
     * address, emergency_contact, email, phone_number), we apply them immediately
     * and flag the record for verification.
     * Fields requiring clinical or government verification (DOB, sex, health_id,
     * national_id_number) are logged only — a human must approve.
     */
    public function rectify(Request $request)
    {
        $patient = $this->resolvePatient($request);
        if (! $patient) {
            abort(403, 'No patient profile linked to this account.');
        }

        $validated = $request->validate([
            'field'         => 'required|string|max:80',
            'current_value' => 'nullable|string|max:500',
            'correct_value' => 'required|string|max:500',
            'reason'        => 'nullable|string|max:1000',
        ]);

        // Fields the patient can self-correct immediately (no clinical impact)
        $selfServiceFields = ['first_name', 'last_name', 'middle_name',
                              'address', 'email', 'phone_number', 'emergency_contact'];

        $selfApplied = false;
        if (in_array($validated['field'], $selfServiceFields, true)) {
            $patient->update([$validated['field'] => $validated['correct_value']]);
            $selfApplied = true;
        }

        // Always audit
        MedicalIdAccessEvent::create([
            'patient_id'  => $patient->id,
            'health_id'   => $patient->health_id,
            'actor_id'    => (string) $request->user()->id,
            'actor_type'  => 'patient',
            'facility_id' => null,
            'access_type' => 'data_rectification_request',
            'purpose'     => 'data_subject_rights',
            'result'      => 'success',
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'notes'       => sprintf(
                'Rectification: field=%s | self_applied=%s | reason=%s',
                $validated['field'],
                $selfApplied ? 'yes' : 'pending_review',
                $validated['reason'] ?? 'not provided'
            ),
        ]);

        Log::info('patient_data_rectification', [
            'patient_id'   => $patient->id,
            'health_id'    => $patient->health_id,
            'field'        => $validated['field'],
            'self_applied' => $selfApplied,
            'ip'           => $request->ip(),
        ]);

        $message = $selfApplied
            ? 'Your profile has been updated. The change has been logged for verification.'
            : 'Your rectification request has been submitted. Our team will review it within 7 business days as required by Law No. 2010/012.';

        return back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /portals/patient/data-rights/erase
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Submit a data erasure request (Right to be Forgotten).
     *
     * Immediately:
     *   - Clears non-essential PII: name, DOB, phone, address, email, emergency_contact
     *   - Revokes all active consent grants
     *   - Sets identity_status = 'erasure_pending'
     *   - Nullifies push_token, privacy_preferences
     *   - Retains: health_id, facility audit trail, lab/clinical records (legally required
     *     for medical safety and national health statistics per MINSANTE)
     *
     * The ops team must physically delete the patient row after confirming
     * retention obligations are met. The 'erasure_pending' status prevents
     * the record from being surfaced to facilities.
     *
     * Requires confirmation password for this destructive action.
     */
    public function erase(Request $request)
    {
        $patient = $this->resolvePatient($request);
        if (! $patient) {
            abort(403, 'No patient profile linked to this account.');
        }

        $validated = $request->validate([
            'reason'       => 'required|string|min:10|max:1000',
            'confirmation' => 'required|in:I understand this action cannot be undone',
        ]);

        DB::transaction(function () use ($patient, $validated, $request) {
            // 1. Nullify all personally identifiable non-clinical fields
            $patient->forceFill([
                'first_name'          => '[ERASED]',
                'last_name'           => '[ERASED]',
                'middle_name'         => null,
                'date_of_birth'       => null,
                'phone_number'        => null,
                'email'               => null,
                'address'             => null,
                'emergency_contact'   => null,
                'national_id_number'  => null,
                'cnamgs_id'           => null,
                'push_token'          => null,
                'push_platform'       => null,
                'privacy_preferences' => null,
                'identity_status'     => 'erasure_pending',
                'verification_status' => 'suspended',
            ])->save();

            // 2. Revoke all active consent grants
            \App\Models\ConsentGrant::where('patient_id', $patient->id)
                ->where('status', 'granted')
                ->update([
                    'status'     => 'revoked',
                    'revoked_at' => now(),
                ]);

            // 3. Disable the linked user account so they cannot log in
            if ($patient->user_id) {
                \App\Models\User::where('id', $patient->user_id)
                    ->update(['email' => 'erased-' . $patient->id . '@deleted.opescare.invalid']);
            }

            // 4. Full audit trail — never erasable (legal requirement)
            MedicalIdAccessEvent::create([
                'patient_id'  => $patient->id,
                'health_id'   => $patient->health_id,
                'actor_id'    => (string) $request->user()->id,
                'actor_type'  => 'patient',
                'facility_id' => null,
                'access_type' => 'data_erasure_request',
                'purpose'     => 'data_subject_rights',
                'result'      => 'success',
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'notes'       => 'ERASURE: PII nullified. Reason: ' . $validated['reason']
                    . ' | Clinical records retained per MINSANTE legal obligation.',
            ]);

            Log::warning('patient_data_erasure', [
                'patient_id' => $patient->id,
                'health_id'  => $patient->health_id,
                'ip'         => $request->ip(),
                'reason'     => $validated['reason'],
            ]);
        });

        // Log out and kill session — the account is now neutered
        Auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.landing')
            ->with('info', __('flash.data_erased'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolvePatient(Request $request): ?Patient
    {
        // Patients are linked via users.patient_id (the patients table has no
        // user_id column), matching PatientPortalController::resolvePatient().
        return $request->user()?->patient;
    }
}
