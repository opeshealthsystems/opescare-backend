<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Services\Documents\ClinicianCredential;
use App\Services\Documents\DocumentIssuanceService;
use App\Services\Patient\PatientDocumentService;
use App\Services\Portal\DeskFacilityContext;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The certificate desk: a clinician issues a medical certificate or a sick
 * note (arrêt de travail) for a patient they have standing with.
 *
 * Specs: docs/specs/documents/06-medical-certificate.md and 07-sick-note.md.
 *
 * The certificate is an `official_documents` row written by the existing
 * DocumentIssuanceService (number, verification code, token, audit). Nothing
 * is rendered or stored here: the PDF is drawn on demand from that row by
 * PatientDocumentService (documents/certificate), for the patient in their
 * portal and for the issuing facility below.
 *
 * Gate: `capability:prescribe` (doctors and medical supervisors), the same
 * set entitled to sign a prescription — a certificate is a medical act. The
 * facility comes from the desk context, never from the request body, and the
 * clinician must hold a clinical relationship with the patient.
 */
class CertificateIssuanceController extends Controller
{
    public const TYPES = ['medical_certificate', 'sick_note'];

    public const PURPOSES = ['general_fitness', 'school', 'employment', 'sport', 'travel', 'administrative', 'other'];

    public function index(): View
    {
        $documents = OfficialDocument::where('facility_id', $this->facilityId())
            ->whereIn('document_type', self::TYPES)
            ->orderByDesc('issued_at')
            ->limit(100)
            ->get();

        $patients = Patient::whereIn('id', $documents->pluck('patient_id')->filter()->unique())
            ->get(['id', 'first_name', 'last_name', 'health_id'])
            ->keyBy('id');

        return view('portals.certificates.index', compact('documents', 'patients'));
    }

    public function create(Request $request): View
    {
        $patient  = null;
        $notFound = false;

        if ($id = $request->input('patient_id')) {
            $patient = Patient::resolveId((string) $id);
        } elseif ($healthId = trim((string) $request->input('health_id'))) {
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;
        }

        $type = in_array($request->input('type'), self::TYPES, true) ? $request->input('type') : 'medical_certificate';

        return view('portals.certificates.create', [
            'patient'  => $patient,
            'notFound' => $notFound,
            'type'     => $type,
            'types'    => self::TYPES,
            'purposes' => self::PURPOSES,
        ]);
    }

    public function store(Request $request, DocumentIssuanceService $issuance): RedirectResponse
    {
        $type = (string) $request->input('type');

        $rules = [
            'patient_id'  => 'required|uuid|exists:patients,id',
            'type'        => 'required|in:' . implode(',', self::TYPES),
            'examined_on' => 'required|date|before_or_equal:today',
            'statement'   => 'required|string|max:2000',
            'recipient'   => 'nullable|string|max:200',
        ];

        if ($type === 'sick_note') {
            $rules += [
                'rest_from' => 'required|date',
                'rest_to'   => 'required|date|after_or_equal:rest_from',
            ];
        } else {
            $rules += [
                'purpose'       => 'required|in:' . implode(',', self::PURPOSES),
                'validity_days' => 'nullable|integer|min:1|max:365',
            ];
        }

        $validated = $request->validate($rules);
        $facilityId = $this->facilityId();

        if (! ClinicalRelationshipGate::evaluate((string) auth()->id(), $facilityId, $validated['patient_id'])->holds()) {
            return back()->withInput()->with('error', __('doc_ex_cert.no_relationship'));
        }

        $patient = Patient::findOrFail($validated['patient_id']);
        $issuer  = ClinicianCredential::for(auth()->id());

        // The payload is the certificate. Keys are fixed and translated on
        // the sheet (doc_ex_cert.field.*); the issuer is snapshotted so the
        // paper keeps naming who signed it even if the profile changes.
        $payload = [
            'certificate_kind'  => $type,
            'examined_on'       => Carbon::parse($validated['examined_on'])->toDateString(),
            'statement'         => trim($validated['statement']),
            'recipient'         => filled($validated['recipient'] ?? null) ? trim($validated['recipient']) : null,
            'issuer_name'       => $issuer['name'],
            'issuer_credential' => $issuer['credential'],
            'issuer_licence'    => $issuer['licence'],
        ];

        if ($type === 'sick_note') {
            $from = Carbon::parse($validated['rest_from'])->startOfDay();
            $to   = Carbon::parse($validated['rest_to'])->startOfDay();

            $payload += [
                'rest_from' => $from->toDateString(),
                'rest_to'   => $to->toDateString(),
                'rest_days' => (int) $from->diffInDays($to) + 1,
            ];
            $expires = $to->copy()->endOfDay();
        } else {
            $payload['purpose'] = $validated['purpose'];
            $expires = now()->addDays((int) ($validated['validity_days'] ?? 90));
        }

        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        $document = $issuance->issue([
            'document_type'  => $type,
            'template_code'  => $type,
            'title'          => __('doc_ex_cert.title.' . $type),
            'payload_json'   => $payload,
            'facility_id'    => $facilityId,
            'patient_id'     => $patient->id,
            'health_id'      => $patient->health_id,
            'issuer_user_id' => auth()->id(),
            'actor_id'       => auth()->id(),
            'expires_at'     => $expires,
        ]);

        return redirect()
            ->route('portals.certificates.index')
            ->with('success', __('doc_ex_cert.issued', ['number' => $document->document_number]));
    }

    /** The issued certificate as a PDF, for the facility that issued it. */
    public function pdf(string $id, PatientDocumentService $documents): StreamedResponse
    {
        $document = OfficialDocument::where('facility_id', $this->facilityId())
            ->whereIn('document_type', self::TYPES)
            ->findOrFail($id);

        $patient = Patient::findOrFail($document->patient_id);

        return $documents->download($patient, PatientDocumentService::CERTIFICATE, (string) $document->id, (string) auth()->id());
    }

    private function facilityId(): string
    {
        $id = request()->attributes->get('facility_id') ?? app(DeskFacilityContext::class)->resolve();

        abort_if($id === null || $id === '', 403, __('prescriber.no_facility'));

        return (string) $id;
    }
}
