<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Services\Patient\PatientDocumentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mobile Patient API — Official Documents
 *
 * Patients can view their issued official documents (discharge summaries,
 * referral letters, etc.). Every query is scoped to the patient id resolved
 * from the bearer token by `auth.mobile` — never from caller-supplied input.
 *
 * Document content is NOT streamed here to avoid exposing raw file paths.
 *
 * `verify_url` is currently always null: this build has no public
 * document-verification page. The route this controller used to point at
 * (`/verify/document/{token}`) does not exist in routes/web.php — only
 * `/verify/qr/{token}` does, and that verifies health-ID QR tokens against a
 * different token store, not documents. Minting a DocumentVerificationToken
 * for a URL that 404s wrote an unbounded row per view and handed the patient
 * a dead link, so the minting was removed. To restore the feature, add a
 * public `/verify/document/{token}` route + view backed by
 * DocumentVerificationService::verifyByToken() (with throttling, audit
 * logging and PHI minimisation — it is an unauthenticated surface), then
 * re-inject the service and mint the token here again.
 */
class MobileDocumentController extends Controller
{
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

        // Documents the record can produce on request, appended to the issued
        // register. They are rendered at the moment of download and never
        // stored, which is why they cannot be listed from a table of files.
        $onDemand = $documentType === null && $docs->currentPage() === 1
            ? $this->downloadable($patientId)
            : [];

        return response()->json([
            'data'       => $docs->map(fn ($d) => $this->formatDocument($d))->concat($onDemand)->values(),
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

        // official_documents.id is a Postgres `uuid` column: comparing it to a
        // non-UUID string raises 22P02 and surfaces as a 500. A bad id in the
        // URL is a not-found, not a server fault.
        if (! Str::isUuid($id)) {
            abort(404);
        }

        $doc = OfficialDocument::where('id', $id)
            ->where('patient_id', $patientId)
            ->where('status', 'issued')
            ->with('facility:id,name')
            ->firstOrFail();

        return response()->json(['data' => $this->formatDocumentDetail($doc)]);
    }

    // -------------------------------------------------------------------------

    /**
     * What this patient could download right now, rendered on request.
     *
     * These have no row in `official_documents` and never will: the sheet is
     * built from the record when the patient asks for it and dropped once it
     * has been handed over. The id is a stable handle for the app, not a
     * database key.
     *
     * @return list<array<string,mixed>>
     */
    private function downloadable(string $patientId): array
    {
        $patient = Patient::withoutGlobalScopes()->find($patientId);

        if ($patient === null) {
            return [];
        }

        return array_map(fn (array $item) => [
            'id'                => $item['type'] . ':' . ($item['record'] ?? 'all'),
            'document_type'     => $item['type'],
            'title'             => $item['title'],
            'facility_name'     => null,
            'issued_at'         => $item['date'] ? Carbon::parse($item['date'])->toIso8601String() : null,
            'document_number'   => $item['reference'],
            'verification_code' => null,
            // Signed and short-lived: the app hands this to the system browser,
            // which carries no bearer token, so the link authenticates itself.
            'download_url'      => URL::temporarySignedRoute(
                'mobile.documents.download',
                now()->addMinutes(15),
                array_filter([
                    'type'    => $item['type'],
                    'patient' => (string) $patient->id,
                    'record'  => $item['record'],
                ])
            ),
        ], app(PatientDocumentService::class)->available($patient));
    }

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

    private function formatDocumentDetail(OfficialDocument $d): array
    {
        $base = $this->formatDocument($d);
        $base['expires_at'] = $d->expires_at?->toIso8601String();
        $base['sensitivity_level'] = $d->sensitivity_level;
        $base['has_file'] = ! empty($d->pdf_path);
        // Key kept in the contract (the app reads it) but always null until a
        // public document-verification page exists — see the class docblock.
        $base['verify_url'] = null;
        return $base;
    }

    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
