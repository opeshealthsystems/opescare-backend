<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteImportJob;
use App\Models\ImportJob;
use App\Modules\DataImport\Services\ImportMappingService;
use App\Modules\DataImport\Services\ImportRollbackService;
use App\Modules\DataImport\Services\ImportService;
use App\Modules\DataImport\Services\ImportValidationService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * DataImportController — the bulk-load desk: upload, map, validate, preview,
 * approve, roll back.
 *
 * An import job is the highest-leverage object in the admin console: approving
 * one writes rows (patients among them) into a facility's data at scale, and
 * rolling one back reverses them. So every lookup after the list resolves
 * through `jobAtFacility()` — the id of another facility's job, which is all a
 * URL carries, must never be enough to read its headers and error rows, re-map
 * it, approve it, cancel it, or roll it back.
 *
 * Two deliberate departures from the main platform's version:
 *
 *  - The facility comes ONLY from the session context. Main fell back to
 *    `Facility::value('id')` whenever exactly one facility existed; a fallback
 *    default is not a chosen facility, so this fails closed with 409 instead.
 *  - The actor is the authenticated user, not `session('auth_email')`.
 */
class DataImportController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * Never a header, body field, query parameter or "the only facility we
     * could find". If no facility is in context there is no safe guess.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        abort_if(
            $resolved === null || $resolved === '',
            409,
            __('flash.no_facility_context_select')
        );

        return (string) $resolved;
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

    private function actorId(): string
    {
        return (string) ($this->context->actorId() ?: Auth::user()?->email ?: 'unknown');
    }

    // -- History / Index ------------------------------------------------------

    public function index(Request $request): View
    {
        $query = ImportJob::where('facility_id', $this->facilityId())
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('import_type')) {
            $query->where('import_type', $request->string('import_type')->toString());
        }

        $jobs        = $query->limit(100)->get();
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.admin.data_import.index', compact('jobs', 'importTypes'));
    }

    // -- Upload (step 1) ------------------------------------------------------

    public function create(): View
    {
        $this->facilityId();

        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.admin.data_import.upload', compact('importTypes'));
    }

    public function store(Request $request, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'import_type' => 'required|string',
            'file'        => 'required|file|mimes:csv,txt,xlsx,xls|max:25600',
        ]);

        $facilityId = $this->facilityId();

        try {
            $job = $svc->uploadFile(
                $request->file('file'),
                $request->string('import_type')->toString(),
                $facilityId,
                $this->actorId()
            );

            return redirect()->route('portals.admin.data_import.mapping', $job->id)
                ->with('success', __('flash.import_file_uploaded'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // -- Mapping (step 2) -----------------------------------------------------

    public function mapping(string $id, ImportMappingService $mappingSvc, ImportService $svc): View
    {
        $job          = $this->jobAtFacility($id);
        $importTypes  = ImportService::IMPORT_TYPES;
        $systemFields = $mappingSvc->systemFields($job->import_type);
        $saved        = $mappingSvc->savedMappings($this->facilityId(), $job->import_type);
        $suggested    = $job->mapping ?? $svc->suggestMapping($job->detected_headers ?? [], $job->import_type) ?? [];

        return view('portals.admin.data_import.mapping', compact(
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
            // The form is keyed by SYSTEM FIELD (one row per field, the column
            // picked in the select). Everything downstream — the job's
            // `mapping`, saved mappings, validation, execution — reads
            // { file_column => system_field }, so flip it here, once.
            //
            // array_flip would silently drop duplicates; doing it by hand lets
            // two fields never quietly collapse into one column mapping.
            $mapping = [];
            foreach ($request->input('mapping', []) as $systemField => $fileColumn) {
                if (! is_string($fileColumn) || $fileColumn === '') {
                    continue;
                }
                $mapping[$fileColumn] = $systemField;
            }

            $mappingSvc->applyMapping($job, $mapping, $this->actorId(), $request->input('save_as') ?: null);

            return redirect()->route('portals.admin.data_import.validate', $id)
                ->with('success', __('flash.import_mapping_saved'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // -- Validation (step 3) --------------------------------------------------

    public function validate(string $id, ImportValidationService $validationSvc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            $validationSvc->validate($job);

            return redirect()->route('portals.admin.data_import.preview', $id)
                ->with('success', __('flash.import_validation_complete'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // -- Preview (step 4) -----------------------------------------------------

    public function preview(string $id): View
    {
        $job         = $this->jobAtFacility($id, ['rowErrors' => fn ($q) => $q->orderBy('row_number')->limit(200)]);
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.admin.data_import.preview', compact('job', 'importTypes'));
    }

    // -- Approve (step 5) -----------------------------------------------------

    public function approve(Request $request, string $id, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            if (! $job->canBeApproved()) {
                throw new \RuntimeException("Job cannot be approved in status: {$job->status}");
            }

            $actor = $this->actorId();

            $job->forceFill([
                'status'      => 'approved_for_import',
                'approved_by' => $actor,
                'approved_at' => now(),
            ])->save();

            $svc->audit($job, 'approved', $actor);

            ExecuteImportJob::dispatch($job->id, $actor)->onQueue('imports');

            return redirect()->route('portals.admin.data_import.index')
                ->with('success', __('flash.import_approved_queued', ['count' => $job->valid_rows]));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // -- Rollback -------------------------------------------------------------

    public function rollback(Request $request, string $id, ImportRollbackService $rollbackSvc): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $job = $this->jobAtFacility($id);

        try {
            $rollbackSvc->rollback($job, $this->actorId(), $request->input('reason'));

            return redirect()->route('portals.admin.data_import.index')
                ->with('success', __('flash.import_rolled_back'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // -- Cancel ---------------------------------------------------------------

    public function cancel(string $id, ImportService $svc): \Illuminate\Http\RedirectResponse
    {
        $job = $this->jobAtFacility($id);

        try {
            $svc->cancelJob($job, $this->actorId());

            return redirect()->route('portals.admin.data_import.index')
                ->with('success', __('flash.import_cancelled'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // -- Audit log ------------------------------------------------------------

    public function auditLog(string $id): View
    {
        $job         = $this->jobAtFacility($id, ['auditEvents']);
        $importTypes = ImportService::IMPORT_TYPES;

        return view('portals.admin.data_import.audit', compact('job', 'importTypes'));
    }
}
