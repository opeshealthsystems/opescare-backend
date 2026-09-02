<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfficialDocumentResource;
use App\Models\OfficialDocument;
use App\Models\DocumentTemplate;
use App\Models\DocumentSignature;
use App\Models\DocumentCodeMapping;
use App\Models\DocumentSpecimenEvent;
use App\Services\Documents\DocumentNumberService;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\DocumentAmendmentService;
use App\Services\Documents\DocumentRevocationService;
use App\Services\Documents\DocumentShareService;
use App\Services\Documents\QrCodeGenerationService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DocumentController extends Controller
{
    protected $numberService;
    protected $verificationService;
    protected $amendmentService;
    protected $revocationService;
    protected $shareService;
    protected $qrService;

    public function __construct(
        DocumentNumberService $numberService,
        DocumentVerificationService $verificationService,
        DocumentAmendmentService $amendmentService,
        DocumentRevocationService $revocationService,
        DocumentShareService $shareService,
        QrCodeGenerationService $qrService
    ) {
        $this->numberService = $numberService;
        $this->verificationService = $verificationService;
        $this->amendmentService = $amendmentService;
        $this->revocationService = $revocationService;
        $this->shareService = $shareService;
        $this->qrService = $qrService;
    }

    /**
     * List all official documents.
     */
    public function index(Request $request)
    {
        $documents = OfficialDocument::latest()->get();
        return response()->json(OfficialDocumentResource::collection($documents));
    }

    /**
     * Create and issue an official verifiable document.
     */
    public function store(Request $request)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'document_type'      => 'required|string',
            'title'              => 'required|string',
            'patient_name'       => 'required|string',
            'payload_json'       => 'required|array',
            'template_code'      => 'required|string',
            'patient_id'         => 'nullable|uuid',
            'health_id'          => 'nullable|string|max:64',
            'organization_id'    => 'nullable|uuid',
            'issuer_user_id'     => 'nullable|uuid',
            'code_mappings'      => 'nullable|array',
            'code_mappings.*'    => 'array',
            'specimen_events'    => 'nullable|array',
            'specimen_events.*'  => 'array',
            'signature'          => 'nullable|array',
        ]);

        $template = DocumentTemplate::where('template_code', $validated['template_code'])
            ->where('status', 'published')
            ->first();

        if (!$template) {
            // Self-healing: if no published template exists during migration/testing, create a default template
            $template = DocumentTemplate::create([
                'template_code' => $validated['template_code'],
                'document_type' => $validated['document_type'],
                'language' => $request->header('Accept-Language') === 'fr' ? 'fr' : 'en',
                'status' => 'published',
                'version' => '1.0',
                'html_template' => '<div>Default Template Output</div>'
            ]);
        }

        // Generate unique numbers
        $identifiers = $this->numberService->generateIdentifiers($validated['document_type']);

        $payload = $validated['payload_json'];
        $payloadHash = hash('sha256', json_encode($payload));

        $actorId = $request->attributes->get('integration_client_id') ?? auth()->id();

        $document = OfficialDocument::create([
            'document_type'      => $validated['document_type'],
            'document_number'    => $identifiers['document_number'],
            'verification_code'  => $identifiers['verification_code'],
            'patient_id'         => $validated['patient_id'] ?? null,
            'health_id'          => $validated['health_id'] ?? null,
            'facility_id'        => $facilityId,
            'organization_id'    => $validated['organization_id'] ?? null,
            'issuer_user_id'     => $actorId ?? ($validated['issuer_user_id'] ?? null),
            'template_id'        => $template->id,
            'template_version'   => $template->version,
            'status'             => 'issued',
            'version'            => '1.0',
            'title'              => $validated['title'],
            'payload_json'       => $payload,
            'standard_mapping_json' => $request->input('standard_mapping_json'),
            'payload_hash'       => $payloadHash,
            'issued_at'          => now(),
            'released_at'        => now()
        ]);

        // Issue corresponding verification token
        $this->verificationService->issueToken($document->id, $identifiers['verification_token']);

        // Log audit event
        AuditLogger::log(
            $request,
            'document_issued',
            'document',
            $document->id,
            $document->patient_id,
            false,
            'Initial document issuance'
        );

        // Store standard mappings if provided
        if (!empty($validated['code_mappings'])) {
            foreach ($validated['code_mappings'] as $mapping) {
                DocumentCodeMapping::create(array_merge($mapping, [
                    'official_document_id' => $document->id
                ]));
            }
        }

        // Store specimen events if provided
        if (!empty($validated['specimen_events'])) {
            foreach ($validated['specimen_events'] as $event) {
                DocumentSpecimenEvent::create(array_merge($event, [
                    'official_document_id' => $document->id
                ]));
            }
        }

        // Store signature if provided
        if (!empty($validated['signature'])) {
            DocumentSignature::create(array_merge($validated['signature'], [
                'official_document_id' => $document->id,
                'signed_at' => now()
            ]));
        }

        return response()->json(OfficialDocumentResource::make($document), 201);
    }

    /**
     * View details of a single document.
     */
    public function show(Request $request, $id)
    {
        $document = OfficialDocument::with(['template', 'signatures', 'versions', 'codeMappings', 'specimenEvents'])->findOrFail($id);
        
        // Log access audit
        AuditLogger::log(
            $request,
            'document_downloaded',
            'document',
            $document->id,
            $document->patient_id
        );

        return response()->json($document);
    }

    /**
     * Amend an issued document.
     */
    public function amend(Request $request, $id)
    {
        $request->validate([
            'payload_json' => 'required|array',
            'reason' => 'required|string'
        ]);

        $userId = $request->attributes->get('integration_client_id') ?? auth()->id();
        $document = $this->amendmentService->amend($id, $request->payload_json, $request->reason, $userId);

        AuditLogger::log(
            $request,
            'document_amended',
            'document',
            $document->id,
            $document->patient_id,
            false,
            $request->reason
        );

        return response()->json($document);
    }

    /**
     * Revoke a document.
     */
    public function revoke(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $document = $this->revocationService->revoke($id, $request->reason);

        AuditLogger::log(
            $request,
            'document_revoked',
            'document',
            $document->id,
            $document->patient_id,
            false,
            $request->reason
        );

        return response()->json($document);
    }

    /**
     * Mark a document entered in error.
     */
    public function enteredInError(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $document = $this->revocationService->markAsEnteredInError($id, $request->reason);

        AuditLogger::log(
            $request,
            'document_entered_in_error',
            'document',
            $document->id,
            $document->patient_id,
            false,
            $request->reason
        );

        return response()->json($document);
    }

    /**
     * Manual verification endpoint.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string',
        ]);

        $result = $this->verificationService->verifyByCode($request, $request->verification_code, $request->document_number);
        
        return response()->json([
            'status' => $result['status'],
            'document' => $result['document'] ? [
                'document_type' => $result['document']->document_type,
                'document_number' => $result['document']->document_number,
                'verification_code' => $result['document']->verification_code,
                'issued_at' => $result['document']->issued_at,
                'status' => $result['document']->status,
                'version' => $result['document']->version,
            ] : null
        ]);
    }

    /**
     * Generate share link.
     */
    public function share(Request $request, $id)
    {
        $userId = $request->attributes->get('integration_client_id') ?? auth()->id();
        $link = $this->shareService->generateShareLink($id, $userId, $request->expiry_minutes ?? 60, $request->max_access);

        AuditLogger::log(
            $request,
            'document_shared',
            'document',
            $id
        );

        return response()->json($link);
    }

    /**
     * Public Verification Page.
     * Accessible at /verify/document/{token}
     */
    public function verifyPublic(Request $request, $token)
    {
        $result = $this->verificationService->verifyByToken($request, $token);
        $document = $result['document'];

        // Compute dynamic SVG QR code fallback
        $qrSvg = $this->qrService->generateSvg($token);

        // Map facility name & patient display variables
        $facilityName = $document ? ($document->payload_json['facility_name'] ?? '[Facility Not Available]') : 'N/A';
        $patientName = $document ? ($document->payload_json['patient_name'] ?? '[Name Not Available]') : 'N/A';

        return view('documents.verify_public', [
            'status' => $result['status'],
            'document' => $document,
            'qr_svg' => $qrSvg,
            'facility_name' => $facilityName,
            'patient_name' => $patientName,
        ]);
    }

    /**
     * Download or render a fully-styled bilingual document view.
     */
    public function renderDocument(Request $request, $id)
    {
        $document = OfficialDocument::findOrFail($id);
        $template = $document->template;

        // Issue temporary token for the QR representation
        $rawToken = 'vdt_' . \Illuminate\Support\Str::random(10);
        $this->verificationService->issueToken($document->id, $rawToken);

        $qrSvg = $this->qrService->generateSvg($rawToken);

        // Select the appropriate view depending on document_type or template_code
        $typeMap = [
            // ── Original wired ────────────────────────────────────────────
            'RX'    => 'documents.prescription',
            'LAB'   => 'documents.lab_result_report',
            'INV'   => 'documents.invoice',
            'REC'   => 'documents.receipt',
            // ── Clinical records ──────────────────────────────────────────
            'DIS'   => 'documents.discharge_summary',
            'REF'   => 'documents.referral_letter',
            'MCD'   => 'documents.medical_certificate',
            'RAD'   => 'documents.radiology_report',
            'ANC'   => 'documents.antenatal_card',
            'VAX'   => 'documents.immunization_certificate',
            'SUR'   => 'documents.surgical_report',
            'CNS'   => 'documents.consent_form',
            'PAL'   => 'documents.preauthorization_letter',
            'BNF'   => 'documents.birth_notification',
            'CPL'   => 'documents.care_plan_print',
            'NRX'   => 'documents.narcotic_prescription',
            'DTH'   => 'documents.death_certificate',
            'DSU'   => 'documents.death_summary',
            'TRF'   => 'documents.transfer_letter',
            'PATH'  => 'documents.pathology_report',
            'ARV'   => 'documents.arv_card',
            'DOTS'  => 'documents.tb_dots_card',
            'PSY'   => 'documents.psychiatric_assessment',
            'OPD'   => 'documents.opd_summary',
            'CLM'   => 'documents.insurance_claim',
            'FIT'   => 'documents.fitness_certificate',
            'BTR'   => 'documents.blood_transfusion',
            'ANS'   => 'documents.anaesthesia_record',
            'LAMA'  => 'documents.lama_form',
            'AER'   => 'documents.aer_report',
            'MLR'   => 'documents.medicolegal_report',
            'PMR'   => 'documents.autopsy_report',
            'NBA'   => 'documents.newborn_assessment',
            'CHC'   => 'documents.child_health_card',
            'DLY'   => 'documents.dialysis_record',
            'CTX'   => 'documents.chemotherapy_record',
            'ECHO'  => 'documents.echo_report',
            'ENDO'  => 'documents.endoscopy_report',
            'PHY'   => 'documents.physio_report',
            'MRC'   => 'documents.medication_reconciliation',
            'INC'   => 'documents.incident_report',
            'WND'   => 'documents.wound_care_chart',
            'PNC'   => 'documents.postnatal_record',
            'RAL'   => 'documents.referral_acknowledgement',
            'ADM'   => 'documents.admission_form',
            'DPR'   => 'documents.pharmacy_record',
            'ADR'   => 'documents.adr_report',
            'GCH'   => 'documents.growth_chart',
            // ── Batch A ───────────────────────────────────────────────────
            'MAR'   => 'documents.medication_administration_record',
            'PRG'   => 'documents.daily_progress_note',
            'SSC'   => 'documents.surgical_safety_checklist',
            'ICU'   => 'documents.icu_flowsheet',
            'REQ'   => 'documents.investigation_request',
            'NAA'   => 'documents.nursing_admission_assessment',
            // ── Batch B ───────────────────────────────────────────────────
            'SBC'   => 'documents.stillbirth_certificate',
            'AEF'   => 'documents.aefi_report',
            'NDR'   => 'documents.notifiable_disease_report',
            'MAL'   => 'documents.malaria_report',
            'HCR'   => 'documents.hiv_counselling_record',
            'BBR'   => 'documents.blood_bank_request',
            'POR'   => 'documents.postop_recovery_record',
            // ── Batch C ───────────────────────────────────────────────────
            'ECG'   => 'documents.ecg_report',
            'FRA'   => 'documents.fall_risk_assessment',
            'PUA'   => 'documents.pressure_ulcer_assessment',
            'DGL'   => 'documents.glucose_log',
            'HOV'   => 'documents.handover_note',
            'MHI'   => 'documents.mental_health_involuntary',
            // ── Batch D ───────────────────────────────────────────────────
            'DNR'   => 'documents.dnr_order',
            'PALL'  => 'documents.palliative_care_plan',
            'OTA'   => 'documents.occupational_therapy',
            'OHA'   => 'documents.occupational_health_assessment',
            'SLT'   => 'documents.speech_therapy_report',
            'NTR'   => 'documents.nutritional_assessment',
            'SWA'   => 'documents.social_work_assessment',
            // ── Batch E ───────────────────────────────────────────────────
            'ORT'   => 'documents.orthopaedic_chart',
            'CPR'   => 'documents.resuscitation_record',
            'NIC'   => 'documents.nicu_chart',
            'PCF'   => 'documents.patient_complaint',
            'PCS'   => 'documents.procedure_consent',
            // ── Batch F — Mortuary ────────────────────────────────────────
            'BRF'   => 'documents.mortuary_admission',
            'BRL'   => 'documents.body_release',
            'PMC'   => 'documents.autopsy_consent',
            'EMB'   => 'documents.embalming_record',
            'BPN'   => 'documents.burial_permit',
            'CAR'   => 'documents.clinical_autopsy_report',
            'FAR'   => 'documents.forensic_autopsy_report',
            // ── Batch G — Death Review ────────────────────────────────────
            'MDR'   => 'documents.maternal_death_review',
            'PMV'   => 'documents.perinatal_mortality_review',
            'CMN'   => 'documents.coroners_notification',
            'VBA'   => 'documents.verbal_autopsy',
            'MSL'   => 'documents.mortuary_storage_log',
            'BIR'   => 'documents.body_identification',
        ];

        // Also map template_code aliases (some codes differ between template_code and document_type)
        $templateCodeMap = [
            'LAB_RES'  => 'documents.lab_result_report',
            'LAB_REQ'  => 'documents.investigation_request',
            'PALL_CP'  => 'documents.palliative_care_plan',
        ];

        $docType   = strtoupper($document->document_type ?? '');
        $tmplCode  = strtoupper($template->template_code ?? '');
        $viewName  = $typeMap[$docType]
                  ?? $typeMap[$tmplCode]
                  ?? $templateCodeMap[$tmplCode]
                  ?? 'documents.base';

        return view($viewName, [
            'language' => $template->language,
            'title' => $document->title,
            'status' => $document->status,
            'version' => $document->version,
            'document_number' => $document->document_number,
            'verification_code' => $document->verification_code,
            'issued_at' => $document->issued_at ? $document->issued_at->format('d M Y, H:i') : 'N/A',
            'patient_name' => $document->payload_json['patient_name'] ?? '[Name Not Available]',
            'health_id' => $document->health_id,
            'patient_sex' => $document->payload_json['patient_sex'] ?? 'M',
            'issuer_name' => $document->payload_json['issuer_name'] ?? 'Clinical Officer',
            'issuer_role' => $document->payload_json['issuer_role'] ?? 'Physician',
            'facility_name' => $document->payload_json['facility_name'] ?? '[Facility Not Available]',
            'facility_license' => $document->payload_json['facility_license'] ?? null,
            'payload' => $document->payload_json,
            'qr_svg' => $qrSvg
        ]);
    }
}
