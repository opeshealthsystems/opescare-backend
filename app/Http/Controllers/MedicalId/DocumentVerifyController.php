<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Patient\DocumentVerificationService;
use App\Services\Patient\VerifiedDocumentCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

/**
 * The page the QR code on an OpesCare Secure document lands on.
 *
 * Four answers, never a bare error: valid · revoked · expired · invalid. A
 * valid answer shows what the document says about itself — type, reference,
 * issue date, holder, the fingerprint of the issued file — so a facility can
 * check the paper in its hand against the record. The raw token is the bearer
 * secret and is not echoed onto the page.
 *
 * Both token families resolve here: `doc_…` (OpesCare Secure exports) and
 * `vdt_…` (OfficialDocument tokens minted at issuance).
 */
class DocumentVerifyController extends Controller
{
    public function __construct(private readonly DocumentVerificationService $documents)
    {
    }

    public function show(string $token)
    {
        ['state' => $state, 'card' => $card] = $this->documents->check($token);

        if (! $card) {
            return view('verify.document', [
                'state'       => $state,
                'document'    => null,
                'typeLabel'   => null,
                'holder'      => null,
                'verified_at' => now(),
            ]);
        }

        $patient = $card->patient;

        $this->auditSignedInCheck($card);

        return view('verify.document', [
            'state'       => 'valid',
            'document'    => $card,
            'typeLabel'   => $this->typeLabel($card->documentType),
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
    private function auditSignedInCheck(VerifiedDocumentCard $card): void
    {
        $user = Auth::user();

        if (! $user || ! $card->patientId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $card->patientId,
                actorId: $user->id,
                actorType: 'user',
                organizationId: null,
                facilityId: null,
                purpose: 'document_verification',
                dataCategory: 'documents',
                resourceType: $card->source,
                resourceId: $card->id,
                accessType: 'read',
            );
        } catch (\Throwable $e) {
            Log::warning('Document verification audit failed', [
                'document' => $card->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
