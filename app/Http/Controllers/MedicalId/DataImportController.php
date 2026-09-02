<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteImportJob;
use App\Models\Facility;
use App\Models\ImportJob;
use App\Modules\DataImport\Services\ImportMappingService;
use App\Modules\DataImport\Services\ImportRollbackService;
use App\Modules\DataImport\Services\ImportService;
use App\Modules\DataImport\Services\ImportValidationService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * DataImportController — the bulk-load wizard: upload, map, validate, preview,
 * approve, roll back.
 *
 * An import job is the highest-leverage object in the portal: approving one
 * writes rows (patients among them) into a facility's data at scale, and
 * rolling one back deletes them again. The list was already facility-scoped,
 * but every step after it did a bare `ImportJob::findOrFail($id)` — so the id
 * of another facility's job, which is all a URL carries, was enough to read
 * their file's headers and error rows, re-map it, approve it, cancel it, or
 * roll it back. Eight lookups, one bug.
 *
 * All of them now resolve through `jobAtFacility()`, and the facility itself
 * comes from the session rather than `Facility::value('id')`.
 */
class DataImportController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * The single-facility fallback holds only when there is exactly one
     * facility; otherwise there is no safe guess and this 409s rather than
     * importing a file into a hospital nobody chose.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which facility this import belongs to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /** An import job, only if it belongs to the acting facility. */
    private function jobAtFacility(string $id, array $with = []): ImportJob
    {
        return ImportJob::with($with)
            ->where('id', $this->assertUuid($id))
            ->where('facility_id', $this->facilityId())
            ->firstOrFail();
    }

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    // ── History / Index ───────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = ImportJob::where('facility_id', $this->facilityId())
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('import_type')) {
            $query->where('import_type', $request->import_type);
        }

        $jobs       = $query->limit(100)->get();
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.staff.data_import.index', compact('jobs', 'importTypes'));
    }

    // ── Upload Wizard Step 1 ──────────────────────────────────────

    public function create(): View
    {
        $importTypes = ImportService::IMPORT_TYPES;
        return view('portals.staff.data_import.upload', compact('importTypes'));
    }

    public function store(Request $request, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'import_type' => 'required|string',
            'file'        => 'required|file|mimes:csv,xlsx,xls|max:25600',
        ]);

        try {
            $job = $svc->uploadFile(
                $request->file('file'),
                $request->import_type,
                $this->facilityId(),
                $this->demoActorId()
            );

            return redirect()->route('portals.staff.data_import.mapping', $job->id)
                ->with('success', __('flash.import_file_uploaded'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Mapping Step 2 ────────────────────────────────────────────

    public function mapping(string $id, ImportMappingService $mappingSvc): View
    {
        $job          = $this->jobAtFacility($id);
        $importTypes  = ImportService::IMPORT_TYPES;
        $systemFields = $mappingSvc->systemFields($job->import_type);
        $saved        = $mappingSvc->savedMappings($this->facilityId(), $job->import_type);
        $suggested    = $job->mapping ?? (new ImportService())->suggestMapping($job->detected_headers ?? [], $job->import_type) ?? [];

        return view('portals.staff.data_import.mapping', compact(
            'job', 'importTypes', 'systemFields', 'saved', 'suggested'
        ));
    }

    public function mappingStore(Request $request, string $id, ImportMappingService $mappingSvc): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'mapping'   => 'required|array',
            'mapping.*' => 'nullable|string',
            'save_as'   => 'nullable|string|max:80',
        ]);

        // Resolved OUTSIDE the try: a 404 for someone else's job must surface
        // as a 404, not be swallowed by the catch and shown as a flash message.
        $job = $this->jobAtFacility($id);

        try {
            $mapping = array_filter($request->mapping, fn($v) => !empty($v));

            $mappingSvc->applyMapping($job, $mapping, $this->demoActorId(), $request->save_as ?: null);

            return redirect()->route('portals.staff.data_import.validate', $id)
                ->with('success', __('flash.import_mapping_saved'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Validation Step 3 ─────────────────────────────────────────

    public function validate(string $id, ImportValidationService $validationSvc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            $validationSvc->validate($job);

            return redirect()->route('portals.staff.data_import.preview', $id)
                ->with('success', __('flash.import_validation_complete'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Preview Step 4 ────────────────────────────────────────────

    public function preview(string $id): View
    {
        $job    = $this->jobAtFacility($id, ['rowErrors' => fn($q) => $q->orderBy('row_number')->limit(200)]);
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.staff.data_import.preview', compact('job', 'importTypes'));
    }

    // ── Approve Step 5 ────────────────────────────────────────────

    public function approve(Request $request, string $id, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            if (!$job->canBeApproved()) {
                throw new \RuntimeException("Job cannot be approved in status: {$job->status}");
            }

            $job->forceFill([
                'status'      => 'approved_for_import',
                'approved_by' => $this->demoActorId(),
                'approved_at' => now(),
            ])->save();

            $svc->audit($job, 'approved', $this->demoActorId());

            // Dispatch the real queued import job; UI will poll status.
            ExecuteImportJob::dispatch($job->id, $this->demoActorId())->onQueue('imports');

            return redirect()->route('portals.staff.data_import.index')
                ->with('success', __('flash.import_approved_queued', ['count' => $job->valid_rows]));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Rollback ──────────────────────────────────────────────────

    public function rollback(Request $request, string $id, ImportRollbackService $rollbackSvc): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $job = $this->jobAtFacility($id);

        try {
            $rollbackSvc->rollback($job, $this->demoActorId(), $request->reason);

            return redirect()->route('portals.staff.data_import.index')
                ->with('success', __('flash.import_rolled_back'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Cancel ────────────────────────────────────────────────────

    public function cancel(string $id, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            $svc->cancelJob($job, $this->demoActorId());

            return redirect()->route('portals.staff.data_import.index')
                ->with('success', __('flash.import_cancelled'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Audit Log ─────────────────────────────────────────────────

    public function auditLog(string $id): View
    {
        $job    = $this->jobAtFacility($id, ['auditEvents']);
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.staff.data_import.audit', compact('job', 'importTypes'));
    }
}
