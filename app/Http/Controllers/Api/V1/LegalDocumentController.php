<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalDocumentResource;
use App\Http\Resources\LegalDocumentVersionResource;
use App\Http\Resources\PartnerAgreementAcceptanceResource;
use App\Http\Resources\UserLegalAcceptanceResource;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Modules\Legal\Services\LegalDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LegalDocumentController — Legal Document & Acceptance API.
 *
 * Manages the legal documents OpesCare requires for operation:
 * Terms of Use, Privacy Policy, Patient Consent Policy,
 * Data Processing Agreement, Facility Agreement, API Developer Terms.
 *
 * Compliance note: User and partner acceptances are recorded immutably
 * with IP address, user agent, and timestamp for audit/legal purposes.
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints (public-facing):
 *  GET   /v1/legal/documents                          — active docs with current versions
 *  POST  /v1/legal/documents/{doc}/accept             — record user acceptance
 *  POST  /v1/legal/documents/{doc}/partner-accept     — record partner acceptance
 *  GET   /v1/legal/users/{userId}/missing-acceptances — which docs user hasn't accepted
 *
 * Endpoints (admin):
 *  POST  /v1/legal/documents/ensure                   — get-or-create a document by slug
 *  POST  /v1/legal/documents/{doc}/versions           — publish a new version
 *  GET   /v1/admin/legal/stats                        — acceptance counts and document stats
 */
class LegalDocumentController extends Controller
{
    public function __construct(private readonly LegalDocumentService $service) {}

    // ── Public ────────────────────────────────────────────────────────────

    /**
     * Get all active legal documents with their current version.
     * ?lang=en (default)
     */
    public function publicDocuments(Request $request): JsonResponse
    {
        $lang = $request->query('lang', 'en');
        $docs = $this->service->getPublicDocuments($lang);
        return response()->json(['data' => $docs]);
    }

    /**
     * Check which required legal documents a user has not yet accepted.
     * Used on login/onboarding flows to gate access until acceptance is recorded.
     */
    public function missingAcceptances(string $userId): JsonResponse
    {
        $missing = $this->service->getMissingAcceptances($userId);
        return response()->json([
            'user_id'               => $userId,
            'missing_slugs'         => $missing,
            'acceptance_required'   => count($missing) > 0,
        ]);
    }

    /**
     * Record a user's acceptance of a legal document version.
     * Body: { user_id, document_version_id, via?, ip_address?, user_agent? }
     */
    public function recordUserAcceptance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'              => ['required', 'uuid'],
            'document_version_id'  => ['required', 'uuid', 'exists:legal_document_versions,id'],
            'via'                  => ['nullable', 'string', 'in:web,mobile,api'],
            'ip_address'           => ['nullable', 'ip'],
            'user_agent'           => ['nullable', 'string', 'max:500'],
        ]);

        $acceptance = $this->service->recordUserAcceptance(
            $validated['user_id'],
            $validated['document_version_id'],
            $validated['via']        ?? ($request->ip() ? 'api' : 'web'),
            $validated['ip_address'] ?? $request->ip(),
            $validated['user_agent'] ?? $request->userAgent()
        );

        return response()->json(['message' => __('api.acceptance_recorded'), 'data' => UserLegalAcceptanceResource::make($acceptance)], 201);
    }

    /**
     * Record a partner organisation's acceptance of a legal document version.
     * Body: { document_version_id, partner_type, partner_id, accepted_by_name, accepted_by_email, expires_at? }
     */
    public function recordPartnerAcceptance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_version_id' => ['required', 'uuid', 'exists:legal_document_versions,id'],
            'partner_type'        => ['required', 'string', 'max:100'],
            'partner_id'          => ['required', 'uuid'],
            'accepted_by_name'    => ['required', 'string', 'max:255'],
            'accepted_by_email'   => ['required', 'email', 'max:255'],
            'expires_at'          => ['nullable', 'date', 'after:today'],
        ]);

        $acceptance = $this->service->recordPartnerAcceptance(
            $validated['document_version_id'],
            $validated['partner_type'],
            $validated['partner_id'],
            $validated['accepted_by_name'],
            $validated['accepted_by_email'],
            $validated['expires_at'] ?? null
        );

        return response()->json(['message' => __('api.partner_acceptance_recorded'), 'data' => PartnerAgreementAcceptanceResource::make($acceptance)], 201);
    }

    // ── Admin ─────────────────────────────────────────────────────────────

    /**
     * Get or create a legal document by slug.
     * Body: { slug, title, type, lang? }
     * type: terms|privacy|consent|dpa|facility_agreement|api_terms
     */
    public function ensureDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug'  => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'title' => ['required', 'string', 'max:255'],
            'type'  => ['required', 'string', 'in:terms,privacy,consent,dpa,facility_agreement,api_terms'],
            'lang'  => ['nullable', 'string', 'max:5'],
        ]);

        $doc = $this->service->ensureDocument(
            $validated['slug'],
            $validated['title'],
            $validated['type'],
            $validated['lang'] ?? 'en'
        );

        return response()->json(['message' => __('api.document_ensured'), 'data' => LegalDocumentResource::make($doc)], 201);
    }

    /**
     * Publish a new version of a legal document.
     * Body: { version, content_html, published_by, requires_reacceptance?, change_summary?, effective_at? }
     */
    public function publishVersion(LegalDocument $document, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version'               => ['required', 'string', 'max:50'],
            'content_html'          => ['required', 'string'],
            'published_by'          => ['required', 'uuid'],
            'requires_reacceptance' => ['nullable', 'boolean'],
            'change_summary'        => ['nullable', 'string', 'max:1000'],
            'effective_at'          => ['nullable', 'date'],
        ]);

        $version = $this->service->publishVersion(
            $document,
            $validated['version'],
            $validated['content_html'],
            $validated['published_by'],
            $validated['requires_reacceptance'] ?? false,
            $validated['change_summary']        ?? '',
            $validated['effective_at']          ?? null
        );

        return response()->json([
            'message' => __('api.version_published', ['version' => $validated['version'], 'slug' => $document->slug]),
            'data'    => LegalDocumentVersionResource::make($version),
        ], 201);
    }

    /**
     * Admin stats — document counts and acceptance totals.
     */
    public function adminStats(): JsonResponse
    {
        return response()->json(['data' => $this->service->getAdminStats()]);
    }
}
