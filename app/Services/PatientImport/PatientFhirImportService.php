<?php

namespace App\Services\PatientImport;

use App\Models\Patient;
use App\Models\PatientImportedRecord;
use App\Services\Fhir\FhirIngestGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A patient importing a FHIR R4 Bundle from another provider into their OWN
 * record.
 *
 * Shares the partner ingest's identity rules (FhirIngestGuard::HEALTH_ID_SYSTEMS,
 * Patient::resolveHealthId) but not its write path: partner ingest writes
 * clinician-attested rows for a consented facility; this writes
 * patient-provided, unverified rows to patient_imported_records only.
 *
 * The record ALWAYS belongs to the authenticated patient. Identifiers inside
 * the bundle are never used to choose the owner; an OpesCare Health ID that
 * names somebody else refuses the whole bundle (BundleForAnotherPatient),
 * because importing another person's history into this chart is the one
 * outcome worse than importing nothing.
 */
class PatientFhirImportService
{
    public const ALLOWED_TYPES = [
        'Observation',
        'Condition',
        'AllergyIntolerance',
        'MedicationStatement',
        'Immunization',
        'DiagnosticReport',
        'DocumentReference',
    ];

    /** A bundle beyond this many entries is cut off — the rest are skipped. */
    public const MAX_ENTRIES = 1000;

    /** One resource's JSON beyond this is skipped rather than stored. */
    public const MAX_RESOURCE_BYTES = 65536;

    /**
     * @param  array<string,mixed>  $bundle
     * @return array<string,mixed>  the summary
     *
     * @throws PatientImportRefusal
     */
    public function import(Patient $patient, array $bundle): array
    {
        if (($bundle['resourceType'] ?? null) !== 'Bundle' || ! is_array($bundle['entry'] ?? null)) {
            throw new PatientImportRefusal('NOT_A_BUNDLE', __('patient_import.not_a_bundle'));
        }

        $entries = array_values(array_filter($bundle['entry'], 'is_array'));

        $this->assertBelongsTo($patient, $entries);

        $bundleSource = $this->str(data_get($bundle, 'meta.source'))
            ?? $this->str(data_get($bundle, 'identifier.system'));
        $sourceName = $this->organizationName($entries);

        $batchId = (string) Str::uuid();
        $summary = new ImportSummary();

        DB::transaction(function () use ($entries, $patient, $batchId, $bundleSource, $sourceName, $summary) {
            foreach ($entries as $i => $entry) {
                $resource = $entry['resource'] ?? null;
                $type     = is_array($resource) ? (string) ($resource['resourceType'] ?? '') : '';

                if ($i >= self::MAX_ENTRIES) {
                    $summary->skip($type ?: 'unknown', 'too_many_entries');
                    continue;
                }
                if (! is_array($resource) || $type === '') {
                    $summary->skip('unknown', 'invalid_resource');
                    continue;
                }
                if (in_array($type, ['Patient', 'Organization', 'Practitioner', 'PractitionerRole', 'Encounter', 'Provenance'], true)) {
                    // Context, not content: never stored, never used to re-own the record.
                    $summary->skip($type, 'context_resource_ignored');
                    continue;
                }
                if (! in_array($type, self::ALLOWED_TYPES, true)) {
                    $summary->skip($type, 'unsupported_type');
                    continue;
                }
                if (($resource['status'] ?? null) === 'entered-in-error'
                    || data_get($resource, 'verificationStatus.coding.0.code') === 'entered-in-error') {
                    $summary->skip($type, 'entered_in_error');
                    continue;
                }

                $clean = $this->stripHeavy($resource);
                $json  = json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($json === false || strlen($json) > self::MAX_RESOURCE_BYTES) {
                    $summary->skip($type, 'resource_too_large');
                    continue;
                }

                $source     = $this->str(data_get($resource, 'meta.source')) ?? $bundleSource;
                $externalId = $this->str($resource['id'] ?? null);
                $hash       = $this->dedupeHash($type, $source, $externalId, $clean);

                $exists = PatientImportedRecord::query()
                    ->where('patient_id', $patient->id)
                    ->where('dedupe_hash', $hash)
                    ->exists();
                if ($exists) {
                    $summary->skip($type, 'duplicate');
                    continue;
                }

                [$codeSystem, $code, $title] = $this->codeAndTitle($type, $resource);

                PatientImportedRecord::create([
                    'patient_id'             => $patient->id,
                    'import_batch_id'        => $batchId,
                    'resource_type'          => $type,
                    'original_source_system' => $source ? Str::limit($source, 250, '') : null,
                    'original_source_name'   => $sourceName ? Str::limit($sourceName, 250, '') : null,
                    'external_id'            => $externalId ? Str::limit($externalId, 250, '') : null,
                    'dedupe_hash'            => $hash,
                    'title'                  => $title ? Str::limit($title, 490) : null,
                    'code_system'            => $codeSystem ? Str::limit($codeSystem, 250, '') : null,
                    'code'                   => $code ? Str::limit($code, 100, '') : null,
                    'value_text'             => ($v = $this->valueText($type, $resource)) ? Str::limit($v, 490) : null,
                    'status'                 => $this->statusOf($resource),
                    'effective_at'           => $this->effectiveAt($resource),
                    'payload'                => $clean,
                ]);
                $summary->import($type);
            }
        });

        return $summary->toArray($batchId);
    }

    /**
     * Refuse the bundle if any OpesCare Health ID inside it names a person
     * other than the authenticated patient. Other identifier systems (another
     * provider's MRN) are ignored: they cannot name an OpesCare record.
     *
     * @param  list<array<string,mixed>>  $entries
     *
     * @throws PatientImportRefusal
     */
    private function assertBelongsTo(Patient $patient, array $entries): void
    {
        foreach ($entries as $entry) {
            $resource = $entry['resource'] ?? null;
            if (! is_array($resource)) {
                continue;
            }

            $identifiers = [];
            if (($resource['resourceType'] ?? null) === 'Patient') {
                $identifiers = array_filter((array) ($resource['identifier'] ?? []), 'is_array');
            }
            foreach (['subject', 'patient'] as $field) {
                $id = data_get($resource, $field . '.identifier');
                if (is_array($id)) {
                    $identifiers[] = $id;
                }
            }

            foreach ($identifiers as $identifier) {
                $system = (string) ($identifier['system'] ?? '');
                $value  = trim((string) ($identifier['value'] ?? ''));
                if ($value === '' || ! in_array($system, FhirIngestGuard::HEALTH_ID_SYSTEMS, true)) {
                    continue;
                }
                $resolved = Patient::resolveHealthId($value);
                if ($resolved === null || $resolved->id !== $patient->id) {
                    throw new PatientImportRefusal('BUNDLE_FOR_ANOTHER_PATIENT', __('patient_import.another_patient'));
                }
            }
        }
    }

    /** @param list<array<string,mixed>> $entries */
    private function organizationName(array $entries): ?string
    {
        foreach ($entries as $entry) {
            if (data_get($entry, 'resource.resourceType') === 'Organization') {
                return $this->str(data_get($entry, 'resource.name'));
            }
        }

        return null;
    }

    /** Inline base64 attachments are not kept — a DocumentReference is stored as its metadata. */
    private function stripHeavy(array $resource): array
    {
        unset($resource['text']);
        if (isset($resource['content']) && is_array($resource['content'])) {
            foreach ($resource['content'] as $k => $content) {
                if (is_array($content) && isset($content['attachment']['data'])) {
                    unset($resource['content'][$k]['attachment']['data']);
                }
            }
        }
        if (isset($resource['presentedForm']) && is_array($resource['presentedForm'])) {
            foreach ($resource['presentedForm'] as $k => $form) {
                if (is_array($form)) {
                    unset($resource['presentedForm'][$k]['data']);
                }
            }
        }

        return $resource;
    }

    /**
     * Same source + same resource id is the same item. Without an id, the
     * content itself (minus volatile meta) is the identity.
     */
    private function dedupeHash(string $type, ?string $source, ?string $externalId, array $resource): string
    {
        if ($externalId !== null) {
            return hash('sha256', implode('|', ['fhir', $type, $source ?? '', $externalId]));
        }

        unset($resource['meta'], $resource['id']);

        return hash('sha256', 'fhir|' . $type . '|' . json_encode($this->ksortDeep($resource)));
    }

    private function ksortDeep(array $a): array
    {
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $a[$k] = $this->ksortDeep($v);
            }
        }
        if (! array_is_list($a)) {
            ksort($a);
        }

        return $a;
    }

    /** @return array{0:?string,1:?string,2:?string} */
    private function codeAndTitle(string $type, array $r): array
    {
        $cc = match ($type) {
            'Immunization'        => $r['vaccineCode'] ?? null,
            'MedicationStatement' => $r['medicationCodeableConcept'] ?? null,
            'DocumentReference'   => $r['type'] ?? null,
            default               => $r['code'] ?? null,
        };
        $cc     = is_array($cc) ? $cc : [];
        $coding = is_array($cc['coding'][0] ?? null) ? $cc['coding'][0] : [];

        $title = $this->str($cc['text'] ?? null)
            ?? $this->str($coding['display'] ?? null)
            ?? ($type === 'DocumentReference' ? $this->str($r['description'] ?? null) : null)
            ?? ($type === 'MedicationStatement' ? $this->str(data_get($r, 'medicationReference.display')) : null)
            ?? $this->str($coding['code'] ?? null);

        return [$this->str($coding['system'] ?? null), $this->str($coding['code'] ?? null), $title];
    }

    private function valueText(string $type, array $r): ?string
    {
        if ($type === 'Observation') {
            if (is_array($r['valueQuantity'] ?? null) && is_numeric($r['valueQuantity']['value'] ?? null)) {
                return trim($r['valueQuantity']['value'] . ' ' . ($r['valueQuantity']['unit'] ?? $r['valueQuantity']['code'] ?? ''));
            }
            if (isset($r['valueString'])) {
                return $this->str($r['valueString']);
            }
            if (is_array($r['valueCodeableConcept'] ?? null)) {
                return $this->str(data_get($r, 'valueCodeableConcept.text'))
                    ?? $this->str(data_get($r, 'valueCodeableConcept.coding.0.display'));
            }
        }
        if ($type === 'DiagnosticReport') {
            return $this->str($r['conclusion'] ?? null);
        }
        if ($type === 'MedicationStatement') {
            return $this->str(data_get($r, 'dosage.0.text'));
        }
        if ($type === 'AllergyIntolerance') {
            return $this->str(data_get($r, 'reaction.0.manifestation.0.text'))
                ?? $this->str(data_get($r, 'reaction.0.manifestation.0.coding.0.display'));
        }

        return null;
    }

    private function statusOf(array $r): ?string
    {
        $s = $this->str($r['status'] ?? null)
            ?? $this->str(data_get($r, 'clinicalStatus.coding.0.code'));

        return $s ? Str::limit($s, 40, '') : null;
    }

    /** First usable clinical time; a future or unparseable one is left null, never guessed. */
    private function effectiveAt(array $r): ?CarbonImmutable
    {
        foreach (['effectiveDateTime', 'effectivePeriod.start', 'occurrenceDateTime', 'onsetDateTime',
                  'recordedDate', 'issued', 'date', 'dateAsserted'] as $path) {
            $raw = $this->str(data_get($r, $path));
            if ($raw === null) {
                continue;
            }
            try {
                $t = CarbonImmutable::parse($raw);
            } catch (\Throwable) {
                continue;
            }
            if ($t->isAfter(CarbonImmutable::now()->addDay())) {
                return null;
            }

            return $t;
        }

        return null;
    }

    private function str(mixed $v): ?string
    {
        if (! is_string($v) && ! is_numeric($v)) {
            return null;
        }
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }
}
