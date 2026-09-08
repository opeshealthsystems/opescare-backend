<?php

namespace App\Services\Fhir;

/**
 * The CapabilityStatement this server actually honours.
 *
 * Public per the FHIR spec ($metadata is unauthenticated). It is written to be
 * read literally by an integration engineer, so it must not overstate: every
 * interaction listed below is implemented, and everything absent is absent.
 *
 * The most important line in it is the Patient resource, which declares NO
 * interactions at all and carries a documentation string saying so. A partner
 * cannot create a Health ID here. There is no POST /Patient, no upsert, no
 * conditional create, and no side effect on any other endpoint that mints an
 * identity — an unresolvable Health ID ends the request.
 */
class FhirCapabilityStatement
{
    public function toArray(): array
    {
        return [
            'resourceType' => 'CapabilityStatement',
            'id'           => 'opescare-finder-fhir-ingest',
            'url'          => url('/api/fhir/R4/metadata'),
            'version'      => '1.0.0',
            'name'         => 'OpesCareFinderR4Ingest',
            'title'        => 'OpesCare Finder FHIR R4 Ingest Capability Statement',
            'status'       => 'active',
            'experimental' => false,
            'date'         => now()->toDateString(),
            'publisher'    => 'OpesCare',
            'kind'         => 'instance',
            'fhirVersion'  => '4.0.1',
            'format'       => ['json', 'application/fhir+json'],
            'description'  => 'Write-only FHIR R4 door for partner systems pushing clinical data '
                . 'into OpesCare against an EXISTING Health ID. This server does not create patient '
                . 'records from partner submissions and exposes no read or search interactions.',
            'rest' => [[
                'mode'          => 'server',
                'documentation' => $this->restDocumentation(),
                'security'      => [
                    'cors'    => false,
                    'service' => [[
                        'coding' => [[
                            'system'  => 'http://terminology.hl7.org/CodeSystem/restful-security-service',
                            'code'    => 'SMART-on-FHIR',
                            'display' => 'SMART-on-FHIR',
                        ]],
                    ]],
                    'description' => 'OAuth2 client-credentials bearer token from '
                        . '/api/v1/connect/auth/token, carrying SMART-on-FHIR system scopes. '
                        . 'Every write additionally requires an active patient ConsentGrant '
                        . 'issued to the facility named in the token.',
                ],
                'resource' => [
                    [
                        'type'          => 'Patient',
                        'interaction'   => [],
                        'documentation' => 'READ AND SEARCH ONLY ON OTHER OPESCARE SURFACES, NEVER '
                            . 'CREATED HERE. This server exposes no Patient interaction of any kind. '
                            . 'A partner may not create, update, upsert or conditionally create a '
                            . 'patient: a Health ID is minted only by OpesCare identity services. '
                            . 'Supply an existing Health ID in subject.identifier; if it does not '
                            . 'resolve exactly, the write is refused with a 404 OperationOutcome and '
                            . 'no record is created.',
                    ],
                    [
                        'type'          => 'Observation',
                        'interaction'   => [['code' => 'create']],
                        'documentation' => 'Vital signs and laboratory results. Observation.category '
                            . 'must be "vital-signs" or "laboratory". Vital-sign codes are validated '
                            . 'against the FHIR R4 vital-signs profile code list; laboratory codes '
                            . 'against the published LOINC vocabulary. Every LOINC code must pass its '
                            . 'mod-10 check digit. Stored as partner-asserted, never as a facility '
                            . 'measurement.',
                        'searchParam'   => [],
                    ],
                    [
                        'type'          => 'DiagnosticReport',
                        'interaction'   => [['code' => 'create']],
                        'documentation' => 'A laboratory report with its Observations supplied in '
                            . '`contained`. The report and all of its Observations are written in one '
                            . 'transaction: if any is refused, none is stored. Contained Observations '
                            . 'inherit the report subject and may not name a different one.',
                    ],
                    [
                        'type'          => 'Immunization',
                        'interaction'   => [['code' => 'create']],
                        'documentation' => 'vaccineCode must carry a CVX coding present in the '
                            . 'imported CDC CVX vocabulary. occurrenceDateTime is required.',
                    ],
                ],
                'interaction' => [
                    [
                        'code'          => 'transaction',
                        'documentation' => 'POST a Bundle of type "transaction" whose entries are '
                            . 'Observation, DiagnosticReport or Immunization creates. All-or-nothing: '
                            . 'the whole Bundle is applied in one database transaction and any single '
                            . 'refusal rolls back every entry. Reads, updates, deletes and conditional '
                            . 'requests inside a Bundle are refused.',
                    ],
                ],
            ]],
        ];
    }

    private function restDocumentation(): string
    {
        return implode("\n", [
            'INGEST ONLY. This endpoint accepts writes and returns no patient data.',
            '',
            'Required on every write:',
            '  * Authorization: Bearer <token>   - the facility is taken from the token and from',
            '    nowhere else. A facility_id or Organization reference in the request body is ignored.',
            '  * Idempotency-Key: <unique>       - a retried POST returns the original response and',
            '    creates nothing further. Reusing a key with a different body is a 409.',
            '  * An active ConsentGrant from the patient to the facility in the token. Without one',
            '    the write is refused with a 403 OperationOutcome naming consent, and nothing is stored.',
            '',
            'SMART-on-FHIR system scopes:',
            '  system/Observation.write, system/DiagnosticReport.write, system/Immunization.write',
            '',
            'Clinical immutability: stored resources are never overwritten. A correction is a new',
            'resource carrying the extension ' . FhirIngestService::SUPERSEDES_EXTENSION . ' whose',
            'value is the id of the resource it replaces; the replaced resource is kept and its',
            'status becomes "amended".',
        ]);
    }
}
