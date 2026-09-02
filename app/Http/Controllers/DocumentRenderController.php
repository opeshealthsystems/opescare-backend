<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Admission;
use App\Services\Documents\DocumentDataAssembler;
use App\Services\Documents\DocumentOnDemandAssembler;
use App\Services\Documents\QrCodeGenerationService;
use App\Services\Documents\DocumentNumberService;
use App\Services\Documents\DocumentVerificationService;
use App\Models\OfficialDocument;
use App\Models\DocumentTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentRenderController extends Controller
{
    /** Category B — living/continuous forms */
    private array $categoryB = [
        'medication-administration-record' => ['view' => 'documents.medication_administration_record', 'method' => 'assembleMar'],
        'icu-flowsheet'    => ['view' => 'documents.icu_flowsheet',    'method' => 'assembleIcuFlowsheet'],
        'nursing-chart'    => ['view' => 'documents.nursing_chart',    'method' => 'assembleNursingChart'],
        'daily-progress-note' => ['view' => 'documents.daily_progress_note', 'method' => 'assembleDailyProgressNote'],
        'glucose-log'      => ['view' => 'documents.glucose_log',      'method' => 'assembleGlucoseLog'],
        'handover-note'    => ['view' => 'documents.handover_note',    'method' => 'assembleHandoverNote'],
        'partograph'       => ['view' => 'documents.partograph',       'method' => 'assemblePartograph'],
        'nicu-chart'       => ['view' => 'documents.nicu_chart',       'method' => 'assembleNicuChart'],
        'growth-chart'     => ['view' => 'documents.growth_chart',     'method' => 'assembleGrowthChart'],
    ];

    /** Category C — on-demand point-in-time reports */
    private array $categoryC = [
        'surgical-safety-checklist'    => ['view' => 'documents.surgical_safety_checklist',    'method' => 'assembleSurgicalSafetyChecklist'],
        'nursing-admission-assessment' => ['view' => 'documents.nursing_admission_assessment', 'method' => 'assembleNursingAdmissionAssessment'],
        'investigation-request'        => ['view' => 'documents.investigation_request',        'method' => 'assembleInvestigationRequest'],
        'fall-risk-assessment'         => ['view' => 'documents.fall_risk_assessment',         'method' => 'assembleFallRiskAssessment'],
        'pressure-ulcer-assessment'    => ['view' => 'documents.pressure_ulcer_assessment',    'method' => 'assemblePressureUlcerAssessment'],
        'wound-care-chart'             => ['view' => 'documents.wound_care_chart',             'method' => 'assembleWoundCareChart'],
    ];

    /**
     * Category B handler — living clinical forms.
     *
     * GET /patients/{patientId}/forms/{type}[?admission_id=&format=pdf]
     */
    public function clinicalForm(Request $request, string $patientId, string $type)
    {
        if (!array_key_exists($type, $this->categoryB)) {
            abort(404);
        }

        $patient     = Patient::findOrFail($patientId);
        $admissionId = $request->query('admission_id');

        $assembler = new DocumentDataAssembler();
        $method    = $this->categoryB[$type]['method'];
        $payload   = $assembler->{$method}($patient, $admissionId);

        $baseData = $this->buildBaseData($patient);
        $format   = $request->query('format', 'html');

        if ($format === 'pdf') {
            return Pdf::loadView(
                $this->categoryB[$type]['view'],
                array_merge($baseData, ['payload' => $payload])
            )->download($type . '-' . $patientId . '.pdf');
        }

        return view(
            $this->categoryB[$type]['view'],
            array_merge($baseData, ['payload' => $payload])
        );
    }

    /**
     * Category C handler — on-demand point-in-time reports.
     *
     * GET /patients/{patientId}/reports/{type}[?admission_id=&format=pdf&archive=1]
     */
    public function onDemand(Request $request, string $patientId, string $type)
    {
        if (!array_key_exists($type, $this->categoryC)) {
            abort(404);
        }

        $patient     = Patient::findOrFail($patientId);
        $admissionId = $request->query('admission_id');

        $assembler = new DocumentOnDemandAssembler();
        $method    = $this->categoryC[$type]['method'];
        $payload   = $assembler->{$method}($patient, $admissionId);

        $baseData = $this->buildBaseData($patient);

        // Optionally archive as an OfficialDocument
        if ($request->boolean('archive')) {
            $documentType = strtoupper(str_replace('-', '_', $type));
            $templateCode = $documentType;

            $template = DocumentTemplate::where('template_code', $templateCode)
                ->where('status', 'published')
                ->first();

            if (!$template) {
                $template = DocumentTemplate::create([
                    'template_code' => $templateCode,
                    'document_type' => $documentType,
                    'language'      => 'en',
                    'status'        => 'published',
                    'version'       => '1.0',
                    'html_template' => '<div>Default Template Output</div>',
                ]);
            }

            $numberService       = app(DocumentNumberService::class);
            $verificationService = app(DocumentVerificationService::class);

            $identifiers = $numberService->generateIdentifiers($documentType);
            $payloadHash = hash('sha256', json_encode($payload));

            $facilityId = $request->attributes->get('facility_id') ?? 'system';

            $document = OfficialDocument::create([
                'document_type'     => $documentType,
                'document_number'   => $identifiers['document_number'],
                'verification_code' => $identifiers['verification_code'],
                'patient_id'        => $patientId,
                'health_id'         => $patient->health_id,
                'facility_id'       => $facilityId,
                'template_id'       => $template->id,
                'template_version'  => $template->version,
                'status'            => 'issued',
                'version'           => '1.0',
                'title'             => ucwords(str_replace('-', ' ', $type)),
                'payload_json'      => $payload,
                'payload_hash'      => $payloadHash,
                'issued_at'         => now(),
                'released_at'       => now(),
            ]);

            $verificationService->issueToken($document->id, $identifiers['verification_token']);
        }

        $format = $request->query('format', 'html');

        if ($format === 'pdf') {
            return Pdf::loadView(
                $this->categoryC[$type]['view'],
                array_merge($baseData, ['payload' => $payload])
            )->download($type . '-' . $patientId . '.pdf');
        }

        return view(
            $this->categoryC[$type]['view'],
            array_merge($baseData, ['payload' => $payload])
        );
    }

    /**
     * Build the standard base template variables from a Patient model instance.
     */
    private function buildBaseData(Patient $patient): array
    {
        return [
            'language'          => 'en',
            'status'            => 'active',
            'facility_name'     => 'OpesCare Medical Facility',
            'facility_license'  => 'MINSANTE',
            'patient_name'      => trim($patient->last_name . ', ' . $patient->first_name . ($patient->middle_name ? ' ' . $patient->middle_name : '')),
            'health_id'         => $patient->health_id ?? 'N/A',
            'patient_sex'       => $patient->sex ?? 'N/A',
            'patient_dob'       => $patient->date_of_birth
                                    ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M Y')
                                    : 'N/A',
            'document_number'   => 'DRAFT-' . strtoupper(Str::random(8)),
            'issued_at'         => now()->format('d M Y, H:i') . ' WAT',
            'issuer_name'       => 'Clinician',
            'issuer_role'       => 'Health Professional',
            'verification_code' => 'DRAFT',
            'qr_svg'            => '',
        ];
    }
}
