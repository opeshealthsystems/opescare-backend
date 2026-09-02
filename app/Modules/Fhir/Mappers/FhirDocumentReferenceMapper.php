<?php

namespace App\Modules\Fhir\Mappers;

use App\Models\OfficialDocument;

/**
 * FhirDocumentReferenceMapper
 *
 * Maps OpesCare OfficialDocument to FHIR R4 DocumentReference resource.
 * Read-only transformation — no writes to any table.
 */
class FhirDocumentReferenceMapper
{
    /**
     * Map an OfficialDocument to a FHIR R4 DocumentReference resource.
     */
    public function toFhir(OfficialDocument $document): array
    {
        $statusMap = [
            'issued'   => 'current',
            'revoked'  => 'superseded',
            'draft'    => 'current',
            'archived' => 'superseded',
        ];

        $fhirStatus = $statusMap[$document->status ?? ''] ?? 'current';

        $resource = [
            'resourceType' => 'DocumentReference',
            'id'           => $document->id,
            'meta'         => [
                'lastUpdated' => optional($document->updated_at)->toIso8601String(),
            ],
            'identifier'   => [
                [
                    'system' => 'https://opescare.com/fhir/documents',
                    'value'  => $document->document_number ?? $document->id,
                ],
            ],
            'status'       => $fhirStatus,
            'type'         => [
                'coding' => [
                    [
                        'system'  => 'https://opescare.com/fhir/document-types',
                        'code'    => $document->document_type ?? 'document',
                        'display' => ucfirst(str_replace('_', ' ', $document->document_type ?? '')),
                    ],
                ],
                'text' => $document->title ?? ($document->document_type ?? ''),
            ],
            'subject' => $document->patient_id ? [
                'reference' => 'Patient/' . $document->patient_id,
            ] : null,
            'date'    => optional($document->issued_at)->toIso8601String(),
            'content' => [],
        ];

        // Remove null subject
        if ($resource['subject'] === null) {
            unset($resource['subject']);
        }

        // Content attachment
        if ($document->pdf_path) {
            $resource['content'][] = [
                'attachment' => [
                    'contentType' => 'application/pdf',
                    'url'         => 'https://opescare.com/documents/' . $document->id . '/download',
                    'hash'        => $document->document_hash ?? null,
                    'title'       => $document->title ?? 'Official Document',
                    'creation'    => optional($document->issued_at)->toIso8601String(),
                ],
            ];
        }

        // Author (issuer)
        if ($document->issuer_user_id) {
            $resource['author'][] = [
                'reference' => 'Practitioner/' . $document->issuer_user_id,
            ];
        }

        // Custodian (facility)
        if ($document->facility_id) {
            $resource['custodian'] = [
                'reference' => 'Organization/' . $document->facility_id,
            ];
        }

        return $resource;
    }

    /**
     * Map a collection of OfficialDocuments to a FHIR Bundle.
     *
     * @param \Illuminate\Support\Collection<OfficialDocument> $documents
     */
    public function toBundle(\Illuminate\Support\Collection $documents): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $documents->count(),
            'entry'        => $documents->map(fn($d) => ['resource' => $this->toFhir($d)])->values()->toArray(),
        ];
    }
}
