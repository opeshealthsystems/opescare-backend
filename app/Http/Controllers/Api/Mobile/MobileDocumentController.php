<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OfficialDocument;
use App\Services\Documents\DocumentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Official Documents
 *
 * Patients can view their issued official documents (discharge summaries,
 * referral letters, lab reports, etc.) and retrieve QR-verifiable links.
 *
 * Document content is NOT streamed here to avoid exposing raw file paths.
 * Clients use the provided `verify_url` (minted fresh on `show()`) for
 * public verification instead of a direct file download.
 */
class MobileDocumentController extends Controller
{
    public function __construct(private DocumentVerificationService $verification)
    {
    }

    /**
     * List official documents belonging to the authenticated patient.
     *
     * GET /api/mobile/documents
     * Query params: document_type, limit (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $patientId    = $this->resolvePatientId($request);
        $documentType = $request->query('document_type');
        $limit        = min((int) $request->query('limit', 20), 100);

        $query = OfficialDocument::where('patient_id', $patientId)
            ->where('status', 'issued')
            ->with('facility:id,name')
            ->orderByDesc('issued_at');

        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        $docs = $query->paginate($limit);

        return response()->json([
            'data'       => $docs->map(fn ($d) => $this->formatDocument($d)),
            'pagination' => [
                'total'        => $docs->total(),
                'per_page'     => $docs->perPage(),
                'current_page' => $docs->currentPage(),
                'last_page'    => $docs->lastPage(),
            ],
        ]);
    }

    /**
     * Get metadata for a single document.
     *
     * GET /api/mobile/documents/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $doc = OfficialDocument::where('id', $id)
            ->where('patient_id', $patientId)
            ->where('status', 'issued')
            ->with('facility:id,name')
            ->firstOrFail();

        // Mint a fresh, short-lived verification token for this view — raw
        // tokens are never persisted (only their hash is), so a working
        // verify_url can only be produced on demand, not read back from a
        // prior issuance.
        $rawToken = $this->verification->issueToken($doc->id, null, 60);
        $verifyUrl = url('/verify/document/' . $rawToken);

        return response()->json(['data' => $this->formatDocumentDetail($doc, $verifyUrl)]);
    }

    // -------------------------------------------------------------------------

    private function formatDocument(OfficialDocument $d): array
    {
        return [
            'id'                => $d->id,
            'document_type'     => $d->document_type,
            'title'             => $d->title ?? $d->document_type,
            'facility_name'     => $d->facility?->name,
            'issued_at'         => $d->issued_at?->toIso8601String(),
            'document_number'   => $d->document_number,
            'verification_code' => $d->verification_code,
        ];
    }

    private function formatDocumentDetail(OfficialDocument $d, ?string $verifyUrl = null): array
    {
        $base = $this->formatDocument($d);
        $base['expires_at'] = $d->expires_at?->toIso8601String();
        $base['sensitivity_level'] = $d->sensitivity_level;
        $base['has_file'] = ! empty($d->pdf_path);
        $base['verify_url'] = $verifyUrl;
        return $base;
    }

    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
