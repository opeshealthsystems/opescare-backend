<?php

namespace App\Services\Fhir;

use App\Exceptions\FhirIngestRefusal;
use App\Models\PartnerDiagnosticReport;
use App\Models\PartnerImmunization;
use App\Models\PartnerObservation;
use App\Models\Patient;
use App\Support\Fhir\FhirOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * FHIR R4 -> OpesCare, for the INGEST direction.
 *
 * The platform's `app/Modules/Fhir/Mappers/*` convert internal rows OUT to
 * FHIR. This is not their inverse and could not be: a mapper reads a row that
 * is already true, whereas this reads a claim from outside and has to decide
 * whether to believe it. Everything here therefore validates first and maps
 * second, and anything it cannot verify it refuses rather than storing with a
 * shrug.
 *
 * The resource is also kept verbatim in `fhir_source`, so a later dispute
 * about what a partner sent is answered from the submission and not from this
 * interpretation of it.
 */
class FhirIngestService
{
    /** FHIR R4 ObservationStatus values this server will store. */
    private const OBSERVATION_STATUS = [
        'registered', 'preliminary', 'final', 'amended', 'corrected', 'entered-in-error',
    ];

    /** FHIR R4 DiagnosticReportStatus values this server will store. */
    private const REPORT_STATUS = [
        'registered', 'partial', 'preliminary', 'final', 'amended', 'corrected',
        'appended', 'cancelled', 'entered-in-error',
    ];

    /** FHIR R4 ImmunizationStatus (a closed, three-value set in R4). */
    private const IMMUNIZATION_STATUS = ['completed', 'entered-in-error', 'not-done'];

    /** The extension a partner uses to replace a resource it already sent. */
    public const SUPERSEDES_EXTENSION = 'https://opescare.com/fhir/StructureDefinition/supersedes';

    public function __construct(private readonly FhirCodeValidator $codes) {}

    // -- Observation -------------------------------------------------------

    /**
     * @param  array<string,mixed>  $resource
     *
     * @throws FhirIngestRefusal
     */
    public function observation(
        array $resource,
        Patient $patient,
        string $facilityId,
        string $clientId,
        string $sourceSystem,
        ?string $idempotencyKey = null,
        ?PartnerDiagnosticReport $report = null,
    ): PartnerObservation {
        $this->requireResourceType($resource, 'Observation');

        $kind   = $this->observationKind($resource);
        $status = $this->requireStatus($resource, self::OBSERVATION_STATUS, 'final', 'Observation');

        [$code, $display] = $this->codes->requireLoinc(
            (array) ($resource['code'] ?? []),
            $kind
        );

        $effectiveAt = $this->requireInstant(
            $resource['effectiveDateTime']
                ?? ($resource['effectivePeriod']['start'] ?? null)
                ?? ($resource['issued'] ?? null),
            'effectiveDateTime'
        );

        $value      = $this->readValue($resource);
        $components = $this->readComponents($resource, $kind);

        // A reading with neither a value nor components asserts nothing. It is
        // refused rather than stored as an empty row somebody later reads as
        // "measured, result unknown".
        if ($value['quantity'] === null && $value['string'] === null && $components === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::businessRule(
                    'An Observation must carry a valueQuantity, a valueString, a valueCodeableConcept '
                    . 'or at least one component. An Observation with no value asserts nothing.',
                    'value[x]'
                ),
                'observation_without_value',
                'no value[x] and no component'
            );
        }

        $observation = new PartnerObservation([
            'patient_id'             => $patient->id,
            'facility_id'            => $facilityId,
            'integration_client_id'  => $clientId,
            'diagnostic_report_id'   => $report?->id,
            'observation_kind'       => $kind,
            'loinc_code'             => $code,
            'loinc_display'          => $display,
            'value_quantity'         => $value['quantity'],
            'value_unit'             => $value['unit'],
            'value_string'           => $value['string'],
            'components'             => $components,
            'partner_interpretation' => $this->readInterpretation($resource),
            'reference_range'        => $this->readReferenceRange($resource),
            'effective_at'           => $effectiveAt,
            'status'                 => $status,
            'fhir_source'            => $resource,
            'idempotency_key'        => $idempotencyKey,
        ]);

        $observation->source_system = $sourceSystem;

        return $this->persist($observation, $resource, $patient, $facilityId, PartnerObservation::class);
    }

    // -- DiagnosticReport --------------------------------------------------

    /**
     * A lab report and the analytes it released, written together.
     *
     * The caller wraps this in a transaction. Half a report is a worse record
     * than none: a clinician reading three of five analytes has no way to
     * know that two are missing.
     *
     * @param  array<string,mixed>  $resource
     * @return array{0:PartnerDiagnosticReport,1:PartnerObservation[]}
     *
     * @throws FhirIngestRefusal
     */
    public function diagnosticReport(
        array $resource,
        Patient $patient,
        string $facilityId,
        string $clientId,
        string $sourceSystem,
        ?string $idempotencyKey = null,
    ): array {
        $this->requireResourceType($resource, 'DiagnosticReport');

        $status = $this->requireStatus($resource, self::REPORT_STATUS, 'final', 'DiagnosticReport');

        [$code, $display] = $this->codes->requireLoinc(
            (array) ($resource['code'] ?? []),
            PartnerObservation::KIND_LABORATORY
        );

        $effectiveAt = $this->requireInstant(
            $resource['effectiveDateTime']
                ?? ($resource['effectivePeriod']['start'] ?? null)
                ?? ($resource['issued'] ?? null),
            'effectiveDateTime'
        );

        $report = new PartnerDiagnosticReport([
            'patient_id'            => $patient->id,
            'facility_id'           => $facilityId,
            'integration_client_id' => $clientId,
            'loinc_code'            => $code,
            'loinc_display'         => $display,
            'category'              => PartnerObservation::KIND_LABORATORY,
            'status'                => $status,
            'conclusion'            => $this->trimOrNull($resource['conclusion'] ?? null),
            'effective_at'          => $effectiveAt,
            'issued_at'             => isset($resource['issued'])
                ? $this->requireInstant($resource['issued'], 'issued')
                : null,
            'fhir_source'           => $resource,
            'idempotency_key'       => $idempotencyKey,
        ]);

        $report->source_system = $sourceSystem;

        $report = $this->persist($report, $resource, $patient, $facilityId, PartnerDiagnosticReport::class);

        $contained = $this->containedObservations($resource);

        if ($contained === []) {
            throw FhirIngestRefusal::make(
                FhirOutcome::businessRule(
                    'A DiagnosticReport must carry at least one contained Observation. A report with '
                    . 'no results is a report about nothing, and this server will not store one.',
                    'contained'
                ),
                'report_without_results',
                'no contained Observation'
            );
        }

        $observations = [];

        foreach ($contained as $i => $entry) {
            // A contained Observation inherits the report's subject. It must
            // not name a different one: that would let one report write into
            // two patients' records under a single consent check.
            if (isset($entry['subject'])) {
                $containedPatientId = $this->containedSubjectMatches($entry, $patient);

                if (! $containedPatientId) {
                    throw FhirIngestRefusal::make(
                        FhirOutcome::businessRule(
                            'A contained Observation may not name a different subject from its '
                            . 'DiagnosticReport. Omit subject on contained resources; it is '
                            . 'inherited from the report.',
                            'contained[' . $i . '].subject'
                        ),
                        'contained_subject_mismatch',
                        'contained Observation named a different subject'
                    );
                }
            }

            $entry['subject'] = $resource['subject'];

            $observations[] = $this->observation(
                $entry,
                $patient,
                $facilityId,
                $clientId,
                $sourceSystem,
                $idempotencyKey,
                $report,
            );
        }

        return [$report, $observations];
    }

    // -- Immunization ------------------------------------------------------

    /**
     * @param  array<string,mixed>  $resource
     *
     * @throws FhirIngestRefusal
     */
    public function immunization(
        array $resource,
        Patient $patient,
        string $facilityId,
        string $clientId,
        string $sourceSystem,
        ?string $idempotencyKey = null,
    ): PartnerImmunization {
        $this->requireResourceType($resource, 'Immunization');

        $status = $this->requireStatus($resource, self::IMMUNIZATION_STATUS, null, 'Immunization');

        [$code, $display] = $this->codes->requireCvx((array) ($resource['vaccineCode'] ?? []));

        $occurrenceAt = $this->requireInstant(
            $resource['occurrenceDateTime'] ?? null,
            'occurrenceDateTime'
        );

        $immunization = new PartnerImmunization([
            'patient_id'            => $patient->id,
            'facility_id'           => $facilityId,
            'integration_client_id' => $clientId,
            'cvx_code'              => $code,
            'vaccine_display'       => $display,
            'status'                => $status,
            'occurrence_at'         => $occurrenceAt,
            'lot_number'            => $this->trimOrNull($resource['lotNumber'] ?? null),
            'manufacturer'          => $this->trimOrNull($resource['manufacturer']['display'] ?? null),
            'dose_quantity'         => isset($resource['doseQuantity']['value'])
                ? (float) $resource['doseQuantity']['value']
                : null,
            'dose_unit'             => $this->trimOrNull($resource['doseQuantity']['unit'] ?? null),
            'route_code'            => $this->firstCodingCode($resource['route'] ?? null),
            'site_code'             => $this->firstCodingCode($resource['site'] ?? null),
            'fhir_source'           => $resource,
            'idempotency_key'       => $idempotencyKey,
        ]);

        $immunization->source_system = $sourceSystem;

        return $this->persist($immunization, $resource, $patient, $facilityId, PartnerImmunization::class);
    }

    // -- Shared ------------------------------------------------------------

    /**
     * Save, honouring a supersedes extension if the partner sent one.
     *
     * Clinical immutability: the row being replaced is NOT edited and NOT
     * deleted. It stays exactly as it was written, with its status moved to
     * 'amended' so a reader can see both what was asserted and that it was
     * withdrawn. The new row points back at it.
     *
     * @template T of Model
     *
     * @param  T  $model
     * @return T
     *
     * @throws FhirIngestRefusal
     */
    private function persist(Model $model, array $resource, Patient $patient, string $facilityId, string $class): Model
    {
        $supersededId = $this->supersedesId($resource);

        if ($supersededId !== null) {
            /** @var Model|null $previous */
            $previous = $class::query()->whereKey($supersededId)->first();

            // Same patient AND same facility. A partner may correct what it
            // asserted; it may not reach across into another facility's row,
            // and a 404 rather than a 403 keeps that row's existence private.
            if ($previous === null
                || $previous->patient_id !== $patient->id
                || $previous->facility_id !== $facilityId) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::error(
                        404,
                        'not-found',
                        'The resource named in the supersedes extension was not found for this '
                        . 'patient and facility.',
                        'extension'
                    ),
                    'supersedes_target_not_found',
                    'supersedes target missing or not this facility/patient'
                );
            }

            $model->supersedes_id = $previous->getKey();
            $model->save();

            // The single mutation the append-only guard allows.
            $previous->status = 'amended';
            $previous->save();

            return $model;
        }

        $model->save();

        return $model;
    }

    /** @throws FhirIngestRefusal */
    private function requireResourceType(array $resource, string $expected): void
    {
        if (($resource['resourceType'] ?? null) !== $expected) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    sprintf('Expected resourceType "%s", got "%s".', $expected, (string) ($resource['resourceType'] ?? '')),
                    'resourceType'
                ),
                'wrong_resource_type',
                'expected ' . $expected
            );
        }
    }

    /** @throws FhirIngestRefusal */
    private function requireStatus(array $resource, array $allowed, ?string $default, string $type): string
    {
        $status = $this->trimOrNull($resource['status'] ?? null) ?? $default;

        if ($status === null || ! in_array($status, $allowed, true)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalid(
                    sprintf('%s.status must be one of: %s.', $type, implode(', ', $allowed)),
                    'status'
                ),
                'status_invalid',
                'bad status on ' . $type
            );
        }

        return $status;
    }

    /**
     * A clinical event without a time is not a clinical event.
     *
     * @throws FhirIngestRefusal
     */
    private function requireInstant(mixed $raw, string $field): CarbonImmutable
    {
        $value = $this->trimOrNull(is_string($raw) ? $raw : null);

        if ($value === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    sprintf('%s is required. A clinical value with no time cannot be placed in a '
                        . 'patient\'s history.', $field),
                    $field
                ),
                'effective_time_missing',
                $field . ' missing'
            );
        }

        try {
            $instant = CarbonImmutable::parse($value);
        } catch (\Throwable) {
            throw FhirIngestRefusal::make(
                FhirOutcome::invalid(
                    sprintf('%s must be a FHIR dateTime (ISO 8601).', $field),
                    $field
                ),
                'effective_time_invalid',
                $field . ' unparseable'
            );
        }

        // A future clinical event is a clock error somewhere, and a wrong
        // timestamp silently reorders a patient's history. A minute of skew is
        // tolerated; a day is not.
        if ($instant->isAfter(CarbonImmutable::now()->addMinutes(5))) {
            throw FhirIngestRefusal::make(
                FhirOutcome::businessRule(
                    sprintf('%s is in the future. Check the clock on the submitting system.', $field),
                    $field
                ),
                'effective_time_in_future',
                $field . ' in the future'
            );
        }

        return $instant;
    }

    /** @return array{quantity:?float,unit:?string,string:?string} */
    private function readValue(array $resource): array
    {
        if (isset($resource['valueQuantity']) && is_array($resource['valueQuantity'])) {
            $q = $resource['valueQuantity'];

            return [
                'quantity' => isset($q['value']) && is_numeric($q['value']) ? (float) $q['value'] : null,
                'unit'     => $this->trimOrNull($q['unit'] ?? ($q['code'] ?? null)),
                'string'   => null,
            ];
        }

        if (isset($resource['valueString'])) {
            return ['quantity' => null, 'unit' => null, 'string' => $this->trimOrNull($resource['valueString'])];
        }

        if (isset($resource['valueCodeableConcept'])) {
            $cc = (array) $resource['valueCodeableConcept'];
            $coding = (array) ($cc['coding'][0] ?? []);

            return [
                'quantity' => null,
                'unit'     => null,
                'string'   => $this->trimOrNull($coding['display'] ?? ($cc['text'] ?? ($coding['code'] ?? null))),
            ];
        }

        return ['quantity' => null, 'unit' => null, 'string' => null];
    }

    /**
     * Components, each with its own verified LOINC.
     *
     * A blood pressure is one Observation with two components; storing the
     * panel and dropping the components would leave a reading with no numbers.
     *
     * @throws FhirIngestRefusal
     */
    private function readComponents(array $resource, string $kind): ?array
    {
        $raw = $resource['component'] ?? null;

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $out = [];

        foreach ($raw as $i => $component) {
            if (! is_array($component)) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::structure('component entries must be objects.', 'component[' . $i . ']'),
                    'component_malformed',
                    'component not an object'
                );
            }

            [$code, $display] = $this->codes->requireLoinc(
                (array) ($component['code'] ?? []),
                $kind,
                'component[' . $i . '].code.coding'
            );

            $value = $this->readValue($component);

            $out[] = [
                'loinc_code'    => $code,
                'loinc_display' => $display,
                'value'         => $value['quantity'],
                'unit'          => $value['unit'],
                'value_string'  => $value['string'],
            ];
        }

        return $out;
    }

    /**
     * Which of the two lists the code is checked against.
     *
     * Read from Observation.category, never guessed from the code. A server
     * that inferred the category from the code would be using the value it is
     * about to validate to choose how to validate it.
     *
     * @throws FhirIngestRefusal
     */
    private function observationKind(array $resource): string
    {
        foreach ((array) ($resource['category'] ?? []) as $category) {
            foreach ((array) ($category['coding'] ?? []) as $coding) {
                $code = $coding['code'] ?? null;

                if ($code === PartnerObservation::KIND_VITAL_SIGNS) {
                    return PartnerObservation::KIND_VITAL_SIGNS;
                }

                if ($code === PartnerObservation::KIND_LABORATORY) {
                    return PartnerObservation::KIND_LABORATORY;
                }
            }
        }

        throw FhirIngestRefusal::make(
            FhirOutcome::structure(
                'Observation.category must carry a coding of "vital-signs" or "laboratory" from '
                . 'http://terminology.hl7.org/CodeSystem/observation-category. This server will not '
                . 'infer the category from the code.',
                'category'
            ),
            'category_missing',
            'no supported Observation.category'
        );
    }

    private function readInterpretation(array $resource): ?string
    {
        return $this->firstCodingCode($resource['interpretation'][0] ?? null);
    }

    private function readReferenceRange(array $resource): ?string
    {
        $range = $resource['referenceRange'][0] ?? null;

        if (! is_array($range)) {
            return null;
        }

        if (isset($range['text'])) {
            return $this->trimOrNull($range['text']);
        }

        $low  = $range['low']['value'] ?? null;
        $high = $range['high']['value'] ?? null;

        if ($low === null && $high === null) {
            return null;
        }

        return trim(sprintf('%s-%s', $low ?? '', $high ?? ''), '-') === ''
            ? null
            : sprintf('%s-%s', $low ?? '', $high ?? '');
    }

    /** Contained Observation resources, in the order they were sent. */
    private function containedObservations(array $resource): array
    {
        $out = [];

        foreach ((array) ($resource['contained'] ?? []) as $entry) {
            if (is_array($entry) && ($entry['resourceType'] ?? null) === 'Observation') {
                $out[] = $entry;
            }
        }

        return $out;
    }

    private function containedSubjectMatches(array $entry, Patient $patient): bool
    {
        $value = trim((string) ($entry['subject']['identifier']['value'] ?? ''));

        return $value !== '' && strtoupper($value) === strtoupper((string) $patient->health_id);
    }

    private function supersedesId(array $resource): ?string
    {
        foreach ((array) ($resource['extension'] ?? []) as $extension) {
            if (is_array($extension) && ($extension['url'] ?? null) === self::SUPERSEDES_EXTENSION) {
                $value = $this->trimOrNull($extension['valueString'] ?? ($extension['valueId'] ?? null));

                if ($value !== null && \Illuminate\Support\Str::isUuid($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function firstCodingCode(mixed $codeableConcept): ?string
    {
        if (! is_array($codeableConcept)) {
            return null;
        }

        return $this->trimOrNull($codeableConcept['coding'][0]['code'] ?? null);
    }

    private function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
