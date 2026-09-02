<?php

namespace App\Modules\Fhir\Services;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Modules\Fhir\Mappers\FhirAllergyIntoleranceMapper;
use App\Modules\Fhir\Mappers\FhirConditionMapper;
use App\Modules\Fhir\Mappers\FhirConsentMapper;
use App\Modules\Fhir\Mappers\FhirCoverageMapper;
use App\Modules\Fhir\Mappers\FhirDiagnosticReportMapper;
use App\Modules\Fhir\Mappers\FhirDocumentReferenceMapper;
use App\Modules\Fhir\Mappers\FhirEncounterMapper;
use App\Modules\Fhir\Mappers\FhirImmunizationMapper;
use App\Modules\Fhir\Mappers\FhirMedicationRequestMapper;
use App\Modules\Fhir\Mappers\FhirObservationMapper;
use App\Modules\Fhir\Mappers\FhirOrganizationMapper;
use App\Modules\Fhir\Mappers\FhirPatientMapper;
use App\Modules\Fhir\Mappers\FhirPractitionerMapper;

/**
 * FHIR R4 Service
 *
 * Central service for generating FHIR R4-compliant JSON resources and bundles
 * from OpesCare internal models.
 *
 * This is a READ-ONLY mapping layer. It does not accept FHIR writes.
 * Supported resources: Patient, Encounter, Observation, MedicationRequest,
 * DiagnosticReport, Practitioner, Organization, DocumentReference, Consent, Coverage
 */
class FhirService
{
    public function __construct(
        private readonly FhirPatientMapper             $patientMapper,
        private readonly FhirEncounterMapper           $encounterMapper,
        private readonly FhirObservationMapper         $observationMapper,
        private readonly FhirMedicationRequestMapper   $medicationMapper,
        private readonly FhirDiagnosticReportMapper    $diagnosticMapper,
        private readonly FhirPractitionerMapper        $practitionerMapper,
        private readonly FhirOrganizationMapper        $organizationMapper,
        private readonly FhirDocumentReferenceMapper   $documentReferenceMapper,
        private readonly FhirConsentMapper             $consentMapper,
        private readonly FhirCoverageMapper            $coverageMapper,
        private readonly FhirImmunizationMapper        $immunizationMapper,
        private readonly FhirAllergyIntoleranceMapper  $allergyIntoleranceMapper,
        private readonly FhirConditionMapper           $conditionMapper,
    ) {}

    // -------------------------------------------------------------------------
    // Single Resource
    // -------------------------------------------------------------------------

    public function patient(Patient $patient): array
    {
        return $this->patientMapper->toFhir($patient);
    }

    public function encounter(Visit $visit): array
    {
        return $this->encounterMapper->toFhir($visit);
    }

    public function diagnosticReport(LabOrder $order): array
    {
        $order->loadMissing('results');
        return $this->diagnosticMapper->toFhir($order);
    }

    public function medicationRequest(Prescription $prescription): array
    {
        $prescription->loadMissing('items');
        return $this->medicationMapper->prescriptionToBundle($prescription);
    }

    public function medicationRequestBundle(Prescription $prescription): array
    {
        $prescription->loadMissing('items');
        return $this->wrapBundle($this->medicationMapper->prescriptionToBundle($prescription));
    }

    public function observationBundle(VitalSign $vital): array
    {
        $vital->loadMissing('triageRecord.visit');
        return $this->wrapBundle($this->observationMapper->toFhirBundle($vital));
    }

    // -------------------------------------------------------------------------
    // Patient-Centric Bundles
    // -------------------------------------------------------------------------

    /**
     * Build a FHIR Bundle with all available resources for a patient.
     * Includes: Patient, Encounters, DiagnosticReports, MedicationRequests, Observations
     */
    public function patientBundle(Patient $patient, int $limit = 50): array
    {
        $entries = [];

        // Patient
        $entries[] = $this->bundleEntry($this->patient($patient));

        // Encounters (visits)
        $visits = Visit::where('patient_id', $patient->id)->latest()->take($limit)->get();
        foreach ($visits as $visit) {
            $entries[] = $this->bundleEntry($this->encounter($visit));
        }

        // DiagnosticReports (lab orders with results)
        $labOrders = LabOrder::where('patient_id', $patient->id)
            ->with('results')
            ->latest('ordered_at')
            ->take($limit)
            ->get();
        foreach ($labOrders as $order) {
            $entries[] = $this->bundleEntry($this->diagnosticReport($order));
        }

        // MedicationRequests (prescriptions)
        $prescriptions = Prescription::where('patient_id', $patient->id)
            ->with('items')
            ->latest('prescribed_at')
            ->take($limit)
            ->get();
        foreach ($prescriptions as $prescription) {
            foreach ($this->medicationMapper->prescriptionToBundle($prescription) as $med) {
                $entries[] = $this->bundleEntry($med);
            }
        }

        return [
            'resourceType' => 'Bundle',
            'id'           => 'bundle-patient-' . $patient->id,
            'meta'         => ['lastUpdated' => now()->toIso8601String()],
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ];
    }

    // -------------------------------------------------------------------------
    // Extended Resources (Phase 30)
    // -------------------------------------------------------------------------

    public function practitioner(User $user): array
    {
        return $this->practitionerMapper->toFhir($user);
    }

    public function practitionerBundle(\Illuminate\Support\Collection $users): array
    {
        return $this->practitionerMapper->toBundle($users);
    }

    public function organization(Facility $facility): array
    {
        return $this->organizationMapper->toFhir($facility);
    }

    public function organizationBundle(\Illuminate\Support\Collection $facilities): array
    {
        return $this->organizationMapper->toBundle($facilities);
    }

    public function documentReference(OfficialDocument $document): array
    {
        return $this->documentReferenceMapper->toFhir($document);
    }

    public function documentReferenceBundle(\Illuminate\Support\Collection $documents): array
    {
        return $this->documentReferenceMapper->toBundle($documents);
    }

    public function consent(ConsentGrant $grant): array
    {
        return $this->consentMapper->toFhir($grant);
    }

    public function consentBundle(\Illuminate\Support\Collection $grants): array
    {
        return $this->consentMapper->toBundle($grants);
    }

    public function coverage(PatientInsurancePolicy $policy): array
    {
        return $this->coverageMapper->toFhir($policy);
    }

    public function coverageBundle(\Illuminate\Support\Collection $policies): array
    {
        return $this->coverageMapper->toBundle($policies);
    }

    // -------------------------------------------------------------------------
    // Immunization
    // -------------------------------------------------------------------------

    public function immunization(ImmunizationRecord $record): array
    {
        return $this->immunizationMapper->toFhir($record);
    }

    public function immunizationBundle(\Illuminate\Support\Collection $records): array
    {
        return $this->immunizationMapper->toBundle($records);
    }

    // -------------------------------------------------------------------------
    // AllergyIntolerance
    // -------------------------------------------------------------------------

    public function allergyIntolerance(AllergyRecord $allergy): array
    {
        return $this->allergyIntoleranceMapper->toFhir($allergy);
    }

    public function allergyIntoleranceBundle(\Illuminate\Support\Collection $allergies): array
    {
        return $this->allergyIntoleranceMapper->toBundle($allergies);
    }

    // -------------------------------------------------------------------------
    // Condition
    // -------------------------------------------------------------------------

    public function condition(Diagnosis $diagnosis): array
    {
        return $this->conditionMapper->toFhir($diagnosis);
    }

    public function conditionBundle(\Illuminate\Support\Collection $diagnoses): array
    {
        return $this->conditionMapper->toBundle($diagnoses);
    }

    // -------------------------------------------------------------------------
    // FHIR CapabilityStatement
    // -------------------------------------------------------------------------

    public function capabilityStatement(): array
    {
        return [
            'resourceType' => 'CapabilityStatement',
            'id'           => 'opescare-fhir-capability',
            'url'          => url('/api/fhir/R4/metadata'),
            'version'      => '1.0',
            'name'         => 'OpesCareR4',
            'title'        => 'OpesCare FHIR R4 Capability Statement',
            'status'       => 'active',
            'date'         => now()->toDateString(),
            'publisher'    => 'OpesCare',
            'kind'         => 'instance',
            'fhirVersion'  => '4.0.1',
            'format'       => ['json'],
            'rest'         => [
                [
                    'mode'     => 'server',
                    'resource' => [
                        ['type' => 'Patient',              'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Encounter',            'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Observation',          'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'MedicationRequest',    'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'DiagnosticReport',     'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Practitioner',         'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Organization',         'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'DocumentReference',    'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Consent',              'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Coverage',             'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Immunization',         'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'AllergyIntolerance',   'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Condition',            'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function wrapBundle(array $resources): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'collection',
            'total'        => count($resources),
            'entry'        => array_map(fn ($r) => $this->bundleEntry($r), $resources),
        ];
    }

    private function bundleEntry(array $resource): array
    {
        $type = $resource['resourceType'];
        $id   = $resource['id'] ?? null;

        return [
            'fullUrl'  => $id ? url("/api/fhir/R4/{$type}/{$id}") : null,
            'resource' => $resource,
        ];
    }
}
