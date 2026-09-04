<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Clinical\PrescriptionAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * The scan-target for a printed prescription.
 *
 * A pharmacist holding a paper script is not logged in, so this page is public
 * and everything it may reveal is decided here — not in the view, and not by
 * handing the Blade an Eloquent model that would happily render whatever it is
 * asked for. What leaves this controller is a flat projection built field by
 * field: enough to dispense safely and to confirm the person at the counter is
 * the person on the script, and nothing else. No address, no phone, no patient
 * id, no other prescription, no clinical notes.
 *
 * Two rules the Health ID verifier taught us the hard way:
 *
 *  1. The view is handed an OBJECT. `verify/qr.blade.php` reads `$result->name`;
 *     it was passed an array, PHP returns null for `->prop` on an array, and a
 *     correctly verified patient rendered as four em dashes under the words
 *     "Identity Verified". Every field named below is a field the Blade reads.
 *
 *  2. A failed lookup gets ONE message. Distinguishing "unknown" from "expired"
 *     from "withdrawn" tells a scanner whether a reference exists, which is an
 *     enumeration oracle over prescriptions. PrescriptionAccessService::verify()
 *     already collapses all three into null; this controller keeps them collapsed.
 */
class PrescriptionVerifyController extends Controller
{
    public function __construct(private PrescriptionAccessService $access) {}

    /**
     * GET /verify/rx/{token} — public.
     */
    public function rx(string $token)
    {
        $accessToken = $this->access->verify($token);

        if (! $accessToken) {
            return $this->refuse($token);
        }

        $prescription = $accessToken->prescription()
            ->with(['items', 'facility', 'prescriber', 'patient'])
            ->first();

        // The token verified but its prescription is gone or unreadable in this
        // context (demo isolation, for one). Same single message — a scanner
        // must not learn the difference.
        if (! $prescription) {
            return $this->refuse($token);
        }

        // The patient is entitled to see that a disclosure happened, so the scan
        // is counted before the page is rendered, not after.
        $this->access->recordUse($accessToken);
        $this->audit($prescription);

        return view('verify.rx', [
            'token'  => $token,
            'rx'     => $this->project($prescription),
            'error'  => null,
        ]);
    }

    /**
     * One refusal, one message. Invalid, expired and withdrawn are the same
     * answer to whoever is holding the paper.
     */
    private function refuse(string $token)
    {
        return view('verify.rx', [
            'token' => $token,
            'rx'    => null,
            'error' => __('verify.rx_invalid_body'),
        ]);
    }

    /**
     * The strict projection. Every key here is read by resources/views/verify/rx.blade.php.
     */
    private function project(Prescription $prescription): object
    {
        $patient  = $prescription->patient;
        $expired  = $prescription->expires_at !== null && $prescription->expires_at->isPast();
        $status   = (string) $prescription->status;

        // `dispensed` is terminal but it is not a retraction — a fully dispensed
        // script is a completed one, and shouting "DO NOT DISPENSE" in red at a
        // pharmacist who just handed the drugs over trains them to ignore red.
        // The retractions are the four that mean the order was taken back.
        $retracted = in_array($status, ['voided', 'entered_in_error', 'cancelled', 'amended'], true);

        $state = match (true) {
            $retracted                                 => 'retracted',
            $expired || $status === 'expired'          => 'expired',
            $status === 'dispensed'                    => 'dispensed',
            default                                    => 'valid',
        };

        return (object) [
            // Identity of the order itself — what a pharmacist reads aloud.
            'reference'      => $prescription->reference,

            // Enough to confirm the right person is at the counter. Deliberately
            // NOT the patient id, date of birth, phone, address or email.
            'patient_name'      => $patient
                ? trim($patient->first_name . ' ' . $patient->last_name)
                : null,
            'patient_health_id' => $patient?->health_id,

            // Provenance.
            'facility_name'   => $prescription->facility?->name,
            'prescriber_name' => $prescription->prescriber?->name,

            // Validity window and outcome.
            'prescribed_at' => $prescription->prescribed_at,
            'expires_at'    => $prescription->expires_at,
            'dispensed_at'  => $prescription->dispensed_at,
            'status'        => $status,
            'status_label'  => $this->statusLabel($status),

            // How the page must present itself: retracted | expired | dispensed | valid.
            'state'         => $state,

            'items' => $prescription->items->map(fn ($item) => (object) [
                'drug_name'     => $item->drug_name,
                'dose'          => $item->dose,
                'frequency'     => $item->frequency,
                'route'         => $item->route,
                'duration_days' => $item->duration_days,
                'quantity'      => $item->quantity,
                'status'        => (string) $item->status,
                'status_label'  => $this->statusLabel((string) $item->status),
                'dispensed_at'  => $item->dispensed_at,
            ])->values(),
        ];
    }

    /**
     * Status values are rendered through the translation files, never raw. The
     * list is closed — it is exactly what Prescription::statusColor() and
     * PrescriptionService write — so an unrecognised value falls back rather
     * than leaking an untranslated database string onto a public page.
     */
    private function statusLabel(string $status): string
    {
        $known = [
            'active', 'pending', 'partially_dispensed', 'dispensed',
            'expired', 'cancelled', 'voided', 'entered_in_error', 'amended',
        ];

        return in_array($status, $known, true)
            ? __('verify.rx_status_' . $status)
            : __('verify.rx_status_unknown');
    }

    /**
     * Audit the disclosure — as far as the audit table can express it.
     *
     * `access_logs.actor_id` is a NOT NULL uuid with a foreign key onto `users`,
     * so AccessLogService cannot record a scan by someone who is not a user of
     * this system — which is the normal case here, a pharmacist with a piece of
     * paper. There is no sentinel actor that would not be a lie or an FK
     * violation, so the anonymous scan is recorded where it CAN be recorded:
     * PrescriptionAccessService::recordUse() stamps `last_used_at` and increments
     * `use_count` on the token, which is patient-visible.
     *
     * When the scanner IS signed in, the entry is real and patient-scoped, so it
     * is written. Never fatal: an audit failure must not take down the page a
     * pharmacist is standing in front of.
     */
    private function audit(Prescription $prescription): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $prescription->patient_id,
                actorId: $user->id,
                actorType: 'user',
                organizationId: null,
                facilityId: $prescription->facility_id,
                purpose: 'prescription_dispensing',
                dataCategory: 'medication',
                resourceType: 'Prescription',
                resourceId: $prescription->id,
                accessType: 'read',
            );
        } catch (\Throwable $e) {
            Log::warning('Prescription scan audit failed', [
                'prescription_id' => $prescription->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
