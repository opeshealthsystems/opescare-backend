<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\CertificationBadge;
use App\Models\CertificationRequirement;
use App\Models\CertificationTestRun;
use App\Models\IntegrationCertification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Integration Certification Admin Portal Controller
 *
 * Allows super_admin and developer roles to manage integration certifications,
 * run test suites, and issue/revoke certification badges.
 */
class IntegrationCertificationController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $status = $request->input('status');
        $type   = $request->input('type');

        $query = IntegrationCertification::with(['latestTestRun', 'badge'])->latest();

        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('integration_type', $type);
        }

        $certifications = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => IntegrationCertification::count(),
            'passed'      => IntegrationCertification::where('status', 'passed')->count(),
            'in_progress' => IntegrationCertification::where('status', 'in_progress')->count(),
            'badges'      => CertificationBadge::whereNull('revoked_at')->count(),
        ];

        $types    = ['his', 'lis', 'erp', 'mobile', 'sdk', 'bridge', 'pharmacy', 'insurance'];
        $statuses = ['in_progress', 'passed', 'failed', 'expired', 'revoked'];

        return view('portals.admin.certifications.index', compact(
            'certifications', 'stats', 'types', 'statuses', 'status', 'type'
        ));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(IntegrationCertification $certification): View
    {
        $certification->load(['testRuns', 'badge']);
        $requirements = CertificationRequirement::active()->orderBy('sort_order')->get();

        return view('portals.admin.certifications.show', compact('certification', 'requirements'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $types = ['his', 'lis', 'erp', 'mobile', 'sdk', 'bridge', 'pharmacy', 'insurance'];

        return view('portals.admin.certifications.form', compact('types'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'integration_name' => 'required|string|max:200',
            'integration_type' => 'required|in:his,lis,erp,mobile,sdk,bridge,pharmacy,insurance',
            'vendor_name'      => 'nullable|string|max:200',
            'vendor_contact'   => 'nullable|string|max:200',
            'version'          => 'nullable|string|max:50',
            'scope_description'=> 'nullable|string',
        ]);

        $certification = IntegrationCertification::create(array_merge($validated, [
            'status'     => 'in_progress',
            'created_by' => $this->demoActorId(),
        ]));

        return redirect()
            ->route('portals.admin.certifications.show', $certification)
            ->with('success', __('flash.certification_started', ['name' => $certification->integration_name]));
    }

    // ── Record Test Run ───────────────────────────────────────────────────────

    public function recordTestRun(Request $request, IntegrationCertification $certification): RedirectResponse
    {
        $validated = $request->validate([
            'run_label'   => 'nullable|string|max:100',
            'run_notes'   => 'nullable|string',
            'results'     => 'required|array',
            'results.*.requirement_id' => 'required|string',
            'results.*.result'         => 'required|in:passed,failed,skipped',
            'results.*.notes'          => 'nullable|string',
        ]);

        $testRun = CertificationTestRun::createFromResults(
            certificationId: $certification->id,
            requirementResults: $validated['results'],
            runBy: $this->demoActorId(),
            label: $validated['run_label'] ?? null,
        );

        if ($validated['run_notes'] ?? null) {
            $testRun->update(['run_notes' => $validated['run_notes']]);
        }

        // Update certification status based on run result
        if ($testRun->isPassed()) {
            $certification->update(['status' => 'passed', 'certified_at' => now(), 'certified_by' => $this->demoActorId()]);
        } elseif ($testRun->isFailed()) {
            $certification->update(['status' => 'failed']);
        }

        return redirect()
            ->route('portals.admin.certifications.show', $certification)
            ->with('success', __('flash.certification_test_recorded', ['rate' => $testRun->passRate()]));
    }

    // ── Issue Badge ───────────────────────────────────────────────────────────

    public function issueBadge(Request $request, IntegrationCertification $certification): RedirectResponse
    {
        if (!$certification->isPassed()) {
            return back()->with('error', __('flash.certification_badge_only_passed'));
        }

        if ($certification->badge) {
            return back()->with('error', __('flash.certification_badge_exists'));
        }

        $validated = $request->validate([
            'certification_level' => 'required|in:bronze,silver,gold,platinum',
            'expires_months'      => 'nullable|integer|min:1|max:36',
        ]);

        $expiresAt = isset($validated['expires_months'])
            ? now()->addMonths((int) $validated['expires_months'])
            : null;

        CertificationBadge::create([
            'integration_certification_id' => $certification->id,
            'badge_code'          => CertificationBadge::generateBadgeCode(),
            'certification_level' => $validated['certification_level'],
            'integration_name'    => $certification->integration_name,
            'integration_type'    => $certification->integration_type,
            'issued_by'           => $this->demoActorId(),
            'issued_at'           => now(),
            'expires_at'          => $expiresAt,
            'is_public'           => true,
        ]);

        $certification->update(['certification_level' => $validated['certification_level']]);

        return redirect()
            ->route('portals.admin.certifications.show', $certification)
            ->with('success', __('flash.certification_badge_issued', ['level' => strtoupper($validated['certification_level'])]));
    }

    // ── Revoke Badge ──────────────────────────────────────────────────────────

    public function revokeBadge(Request $request, CertificationBadge $badge): RedirectResponse
    {
        $validated = $request->validate([
            'revoke_reason' => 'required|string|max:300',
        ]);

        $badge->revoke($validated['revoke_reason']);
        $badge->certification->update(['status' => 'revoked']);

        return redirect()
            ->route('portals.admin.certifications.show', $badge->certification)
            ->with('success', __('flash.certification_badge_revoked'));
    }

    // ── Seed Core Requirements ─────────────────────────────────────────────

    public function seedRequirements(): RedirectResponse
    {
        $this->ensureCoreRequirements();

        return back()->with('success', __('flash.certification_requirements_seeded'));
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }

    private function ensureCoreRequirements(): void
    {
        $requirements = [
            ['slug' => 'fhir_r4_patient_read',        'name' => 'FHIR R4 Patient Read',                'category' => 'fhir',         'severity' => 'required',     'sort_order' => 10],
            ['slug' => 'fhir_r4_encounter_read',       'name' => 'FHIR R4 Encounter Read',             'category' => 'fhir',         'severity' => 'required',     'sort_order' => 20],
            ['slug' => 'fhir_r4_medication_request',   'name' => 'FHIR R4 MedicationRequest',          'category' => 'fhir',         'severity' => 'required',     'sort_order' => 30],
            ['slug' => 'fhir_r4_diagnostic_report',    'name' => 'FHIR R4 DiagnosticReport',           'category' => 'fhir',         'severity' => 'required',     'sort_order' => 40],
            ['slug' => 'fhir_r4_capability_statement', 'name' => 'FHIR R4 CapabilityStatement',        'category' => 'fhir',         'severity' => 'required',     'sort_order' => 50],
            ['slug' => 'oauth2_authentication',        'name' => 'OAuth2 Authentication',              'category' => 'security',     'severity' => 'required',     'sort_order' => 60],
            ['slug' => 'tls_1_2_minimum',              'name' => 'TLS 1.2+ Encryption',                'category' => 'security',     'severity' => 'required',     'sort_order' => 70],
            ['slug' => 'api_rate_limiting',            'name' => 'API Rate Limit Compliance',          'category' => 'security',     'severity' => 'required',     'sort_order' => 80],
            ['slug' => 'no_phi_in_logs',               'name' => 'No PHI in Logs',                     'category' => 'security',     'severity' => 'required',     'sort_order' => 90],
            ['slug' => 'audit_trail_support',          'name' => 'Audit Trail Support',                'category' => 'security',     'severity' => 'required',     'sort_order' => 100],
            ['slug' => 'patient_id_uniqueness',        'name' => 'Patient ID Uniqueness Handling',     'category' => 'data_quality', 'severity' => 'required',     'sort_order' => 110],
            ['slug' => 'mandatory_field_compliance',   'name' => 'Mandatory Field Compliance',         'category' => 'data_quality', 'severity' => 'required',     'sort_order' => 120],
            ['slug' => 'date_format_iso8601',          'name' => 'ISO 8601 Date Formats',              'category' => 'data_quality', 'severity' => 'required',     'sort_order' => 130],
            ['slug' => 'uptime_99_percent',            'name' => '99% Uptime SLA',                     'category' => 'availability', 'severity' => 'recommended',  'sort_order' => 140],
            ['slug' => 'graceful_degradation',         'name' => 'Graceful Degradation on Failure',   'category' => 'availability', 'severity' => 'recommended',  'sort_order' => 150],
            ['slug' => 'consent_check_before_access',  'name' => 'Consent Check Before Data Access',  'category' => 'consent',      'severity' => 'required',     'sort_order' => 160],
            ['slug' => 'minimum_necessary_principle',  'name' => 'Minimum Necessary Data Principle',  'category' => 'consent',      'severity' => 'required',     'sort_order' => 170],
        ];

        foreach ($requirements as $req) {
            CertificationRequirement::updateOrCreate(
                ['slug' => $req['slug']],
                array_merge($req, ['is_active' => true])
            );
        }
    }
}
