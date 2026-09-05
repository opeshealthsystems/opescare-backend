<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Patient\DocumentVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

/**
 * The page the QR code on an OpesCare Secure document lands on.
 *
 * Three answers, never a bare error: valid · revoked · invalid. A valid answer
 * shows what the document says about itself — type, reference, issue date,
 * holder, the fingerprint of the issued file — so a facility can check the
 * paper in its hand against the record. The raw token is the bearer secret
 * and is not echoed onto the page.
 */
class DocumentVerifyController extends Controller
{
    public function __construct(private readonly DocumentVerificationService $documents)
    {
    }

    public function show(string $token)
    {
        $record = $this->documents->verify($token);

        if (! $record) {
            return view('verify.document', [
                'state'       => $this->documents->failureState($token),
                'document'    => null,
                'typeLabel'   => null,
                'holder'      => null,
                'verified_at' => now(),
            ]);
        }

        $patient = $record->patient;

        $this->auditSignedInCheck($record);

        return view('verify.document', [
            'state'       => 'valid',
            'document'    => $record,
            'typeLabel'   => $this->typeLabel($record->document_type),
            'holder'      => $patient ? (object) [
                'name'      => trim($patient->first_name . ' ' . $patient->last_name),
                'health_id' => $patient->health_id,
            ] : null,
            'verified_at' => now(),
        ]);
    }

    private function typeLabel(string $type): string
    {
        $key = 'verify.doc_type_' . $type;

        return Lang::has($key) ? __($key) : __('verify.doc_type_unknown');
    }

    /**
     * The patient-visible audit entry when a signed-in user checks their
     * document. access_logs.actor_id is NOT NULL, so an anonymous check
     * cannot be attributed and is counted on the row instead (scan_count).
     */
    private function auditSignedInCheck(DocumentVerification $record): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $record->patient_id,
                actorId: $user->id,
                actorType: 'user',
                organizationId: null,
                facilityId: null,
                purpose: 'document_verification',
                dataCategory: 'documents',
                resourceType: 'DocumentVerification',
                resourceId: (string) $record->id,
                accessType: 'read',
            );
        } catch (\Throwable $e) {
            Log::warning('Document verification audit failed', [
                'document' => $record->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
