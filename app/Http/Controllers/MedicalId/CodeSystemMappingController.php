<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\CodeSystemMapping;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Code System Mapping Admin Portal Controller
 *
 * Allows super_admin and data_steward to manage mappings between
 * OpesCare local codes and standard terminologies (LOINC, ICD-10, ATC).
 */
class CodeSystemMappingController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $system       = $request->input('system');       // loinc|icd10|atc|snomed|cpt
        $status       = $request->input('status');       // pending|approved|rejected|deprecated
        $resourceType = $request->input('resource_type');
        $search       = $request->input('q');

        $query = CodeSystemMapping::query()->latest();

        if ($system) {
            $query->where('standard_system', $system);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('local_code', 'like', "%{$search}%")
                  ->orWhere('local_name', 'like', "%{$search}%")
                  ->orWhere('standard_code', 'like', "%{$search}%")
                  ->orWhere('standard_display', 'like', "%{$search}%");
            });
        }

        $mappings = $query->paginate(30)->withQueryString();

        $stats = [
            'total'    => CodeSystemMapping::count(),
            'approved' => CodeSystemMapping::where('status', 'approved')->count(),
            'pending'  => CodeSystemMapping::where('status', 'pending')->count(),
            'loinc'    => CodeSystemMapping::where('standard_system', 'loinc')->count(),
            'icd10'    => CodeSystemMapping::where('standard_system', 'icd10')->count(),
            'atc'      => CodeSystemMapping::where('standard_system', 'atc')->count(),
        ];

        $systems       = ['loinc', 'icd10', 'atc', 'snomed', 'cpt'];
        $statuses      = ['pending', 'approved', 'rejected', 'deprecated'];
        $resourceTypes = ['LabTest', 'Diagnosis', 'Medication', 'Observation'];

        return view('portals.admin.code_mappings.index', compact(
            'mappings', 'stats', 'systems', 'statuses', 'resourceTypes',
            'system', 'status', 'resourceType', 'search'
        ));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $systems       = ['loinc', 'icd10', 'atc', 'snomed', 'cpt'];
        $resourceTypes = ['LabTest', 'Diagnosis', 'Medication', 'Observation'];
        $confidences   = ['exact', 'broader', 'narrower', 'approximate', 'manual'];

        return view('portals.admin.code_mappings.form', compact('systems', 'resourceTypes', 'confidences'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'local_code'          => 'required|string|max:100',
            'local_name'          => 'nullable|string|max:300',
            'local_unit'          => 'nullable|string|max:100',
            'resource_type'       => 'required|in:LabTest,Diagnosis,Medication,Observation',
            'standard_system'     => 'required|in:loinc,icd10,atc,snomed,cpt',
            'standard_code'       => 'required|string|max:100',
            'standard_display'    => 'nullable|string|max:300',
            'standard_version'    => 'nullable|string|max:20',
            'mapping_confidence'  => 'required|in:exact,broader,narrower,approximate,manual',
            'notes'               => 'nullable|string|max:500',
        ]);

        CodeSystemMapping::create(array_merge($validated, [
            'status'     => 'pending',
            'created_by' => $this->demoActorId(),
        ]));

        return redirect()
            ->route('portals.admin.code_mappings.index')
            ->with('success', __('flash.mapping_created_pending'));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(CodeSystemMapping $mapping): RedirectResponse
    {
        if (!$mapping->isPending()) {
            return back()->with('error', __('flash.mapping_only_pending_approvable'));
        }

        $mapping->approve($this->demoActorId());

        return back()->with('success', __('flash.mapping_approved', ['from' => $mapping->local_code, 'to' => $mapping->standard_code]));
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(CodeSystemMapping $mapping): RedirectResponse
    {
        $mapping->reject();
        return back()->with('success', __('flash.mapping_rejected'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(CodeSystemMapping $mapping): RedirectResponse
    {
        $mapping->delete();
        return back()->with('success', __('flash.mapping_deleted'));
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }
}
