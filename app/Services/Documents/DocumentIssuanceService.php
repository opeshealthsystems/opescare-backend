<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;
use App\Models\DocumentTemplate;
use App\Models\DocumentCodeMapping;
use App\Models\DocumentSignature;
use App\Services\AuditLogger;
use Carbon\Carbon;

class DocumentIssuanceService
{
    public function __construct(
        private DocumentNumberService $numberService,
        private DocumentVerificationService $verificationService,
    ) {}

    /**
     * Issue an official document, encapsulating the full issuance lifecycle.
     *
     * @param  array  $params
     * @return OfficialDocument
     */
    public function issue(array $params): OfficialDocument
    {
        // 1. Find or create DocumentTemplate by template_code
        $template = DocumentTemplate::where('template_code', $params['template_code'])
            ->where('status', 'published')
            ->first();

        if (! $template) {
            $template = DocumentTemplate::create([
                'template_code'   => $params['template_code'],
                'status'          => 'published',
                'version'         => '1.0',
                'language'        => 'en',
                'html_template'   => '<div>Auto-generated</div>',
                'document_type'   => $params['document_type'],
            ]);
        }

        // 2. Generate document number, verification code, and verification token
        $identifiers = $this->numberService->generateIdentifiers($params['document_type']);

        // 3. Compute payload hash
        $payloadHash = hash('sha256', json_encode($params['payload_json']));

        // 4. Create the OfficialDocument record
        $document = OfficialDocument::create([
            'document_type'      => $params['document_type'],
            'document_number'    => $identifiers['document_number'],
            'verification_code'  => $identifiers['verification_code'],
            'patient_id'         => $params['patient_id'] ?? null,
            'health_id'          => $params['health_id'] ?? null,
            'facility_id'        => $params['facility_id'],
            'organization_id'    => $params['organization_id'] ?? null,
            'issuer_user_id'     => $params['issuer_user_id'] ?? null,
            'template_id'        => $template->id,
            'template_version'   => $template->version,
            'status'             => 'issued',
            'version'            => 1,
            'title'              => $params['title'],
            'payload_json'       => $params['payload_json'],
            'payload_hash'       => $payloadHash,
            'standard_mapping_json' => $params['standard_mapping_json'] ?? null,
            'issued_at'          => Carbon::now(),
            'released_at'        => Carbon::now(),
            'expires_at'         => isset($params['expires_at'])
                ? Carbon::parse($params['expires_at'])
                : null,
        ]);

        // 5. Issue verification token
        $this->verificationService->issueToken($document->id, $identifiers['verification_token']);

        // 6. Audit log
        AuditLogger::log(
            $params['actor_id'] ?? $params['issuer_user_id'] ?? 'system',
            'document_issued',
            'document',
            $document->id,
            $params['patient_id'] ?? null,
            false,
            'Issued via DocumentIssuanceService'
        );

        // 7. Store code_mappings if provided
        if (! empty($params['code_mappings']) && is_array($params['code_mappings'])) {
            foreach ($params['code_mappings'] as $mapping) {
                DocumentCodeMapping::create(array_merge(
                    ['official_document_id' => $document->id],
                    $mapping
                ));
            }
        }

        // 8. Store signature if provided
        if (! empty($params['signature']) && is_array($params['signature'])) {
            DocumentSignature::create(array_merge(
                [
                    'official_document_id' => $document->id,
                    'signed_at'            => Carbon::now(),
                ],
                $params['signature']
            ));
        }

        // 9. Return the document
        return $document;
    }

    /**
     * Convenience wrapper — call issue() with sensible defaults.
     */
    public function issueFromModel(
        string $documentType,
        string $title,
        array $payload,
        string $facilityId,
        ?string $patientId = null,
        ?string $healthId = null,
        ?string $issuerId = null,
    ): OfficialDocument {
        return $this->issue([
            'document_type'  => $documentType,
            'template_code'  => $documentType,
            'title'          => $title,
            'payload_json'   => $payload,
            'facility_id'    => $facilityId,
            'patient_id'     => $patientId,
            'health_id'      => $healthId,
            'issuer_user_id' => $issuerId,
            'actor_id'       => $issuerId,
        ]);
    }
}
