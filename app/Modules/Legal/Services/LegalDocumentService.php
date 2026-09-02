<?php

namespace App\Modules\Legal\Services;

use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\PartnerAgreementAcceptance;
use App\Models\UserLegalAcceptance;
use Illuminate\Support\Collection;

class LegalDocumentService
{
    /**
     * List of required legal documents every OpesCare installation must have.
     */
    public const REQUIRED_DOCUMENTS = [
        'terms-of-use'           => ['title' => 'Terms of Use',             'type' => 'terms'],
        'privacy-policy'         => ['title' => 'Privacy Policy',           'type' => 'privacy'],
        'patient-consent-policy' => ['title' => 'Patient Consent Policy',   'type' => 'consent'],
        'data-processing-agreement' => ['title' => 'Data Processing Agreement', 'type' => 'dpa'],
        'facility-agreement'     => ['title' => 'Facility Agreement',       'type' => 'facility_agreement'],
        'api-developer-terms'    => ['title' => 'API / Developer Terms',    'type' => 'api_terms'],
    ];

    /**
     * Get or create a legal document by slug.
     */
    public function ensureDocument(string $slug, string $title, string $type, string $lang = 'en'): LegalDocument
    {
        return LegalDocument::firstOrCreate(
            ['slug' => $slug],
            ['title' => $title, 'document_type' => $type, 'language' => $lang, 'is_active' => true],
        );
    }

    /**
     * Publish a new version of a legal document.
     * Marks all other versions of the same document as non-current.
     */
    public function publishVersion(
        LegalDocument $document,
        string        $version,
        string        $contentHtml,
        string        $publishedBy,
        bool          $requiresReacceptance = false,
        string        $changeSummary = '',
        ?string       $effectiveAt = null,
    ): LegalDocumentVersion {
        // Unset current flag on previous versions
        $document->versions()->where('is_current', true)->update(['is_current' => false]);

        return LegalDocumentVersion::create([
            'legal_document_id'      => $document->id,
            'version'                => $version,
            'content_html'           => $contentHtml,
            'content_markdown'       => null,
            'content_hash'           => hash('sha256', $contentHtml),
            'is_current'             => true,
            'requires_reacceptance'  => $requiresReacceptance,
            'change_summary'         => $changeSummary,
            'published_by'           => $publishedBy,
            'published_at'           => now(),
            'effective_at'           => $effectiveAt ? \Carbon\Carbon::parse($effectiveAt) : now(),
        ]);
    }

    /**
     * Record a user's acceptance of a legal document version.
     */
    public function recordUserAcceptance(
        string  $userId,
        string  $documentVersionId,
        string  $via = 'web',
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): UserLegalAcceptance {
        return UserLegalAcceptance::firstOrCreate(
            ['user_id' => $userId, 'legal_document_version_id' => $documentVersionId],
            [
                'accepted_via' => $via,
                'ip_address'   => $ipAddress,
                'user_agent'   => $userAgent,
                'accepted_at'  => now(),
            ],
        );
    }

    /**
     * Record a partner's acceptance of a legal document version.
     */
    public function recordPartnerAcceptance(
        string  $documentVersionId,
        string  $partnerType,
        string  $partnerId,
        string  $acceptedByName,
        string  $acceptedByEmail,
        ?string $expiresAt = null,
    ): PartnerAgreementAcceptance {
        return PartnerAgreementAcceptance::create([
            'legal_document_version_id' => $documentVersionId,
            'partner_type'              => $partnerType,
            'partner_id'                => $partnerId,
            'accepted_by_name'          => $acceptedByName,
            'accepted_by_email'         => $acceptedByEmail,
            'accepted_via'              => 'web',
            'accepted_at'               => now(),
            'expires_at'                => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null,
        ]);
    }

    /**
     * Get all active documents with their current versions, for a given language.
     */
    public function getPublicDocuments(string $lang = 'en'): Collection
    {
        return LegalDocument::active()
            ->ofLanguage($lang)
            ->with(['versions' => fn ($q) => $q->where('is_current', true)])
            ->get();
    }

    /**
     * Check whether a user has accepted all required current document versions.
     * Returns array of slugs not yet accepted.
     */
    public function getMissingAcceptances(string $userId): array
    {
        $missing = [];

        $requiredDocs = LegalDocument::active()
            ->where('requires_acceptance', true)
            ->with(['versions' => fn ($q) => $q->where('is_current', true)])
            ->get();

        foreach ($requiredDocs as $doc) {
            $currentVersion = $doc->versions->first();
            if (!$currentVersion) {
                continue;
            }

            $accepted = UserLegalAcceptance::where('user_id', $userId)
                ->where('legal_document_version_id', $currentVersion->id)
                ->whereNull('revoked_at')
                ->exists();

            if (!$accepted) {
                $missing[] = $doc->slug;
            }
        }

        return $missing;
    }

    /**
     * Admin stats.
     */
    public function getAdminStats(): array
    {
        return [
            'total_documents'        => LegalDocument::count(),
            'active_documents'       => LegalDocument::where('is_active', true)->count(),
            'total_versions'         => LegalDocumentVersion::count(),
            'user_acceptances'       => UserLegalAcceptance::count(),
            'partner_acceptances'    => PartnerAgreementAcceptance::count(),
        ];
    }
}
