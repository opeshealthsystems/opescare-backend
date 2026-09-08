<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ClinicalCodeDictionary;
use App\Models\CodeSystemMapping;
use App\Services\Terminology\TerminologyApplier;
use App\Services\Terminology\TerminologyReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * TerminologyController — the clinical terminology review desk.
 *
 * `code_system_mappings` has existed since May with an `approve()` method, a
 * `reject()`, an `approved` scope and twenty pending proposals on production.
 * What it never had was a screen. Nothing in the product could approve a
 * mapping, so nothing ever was: twenty pending, zero approved, and every
 * `lab_results.loinc_code` null. The interoperability layer was a table
 * waiting for a person who had no door to walk through.
 *
 * This is that door, and it is deliberately two doors:
 *
 *  1. REVIEW — approve or reject a proposed mapping. This is a statement
 *     about the mapping only. Approving writes nothing to any clinical row.
 *
 *  2. APPLY — put an approved code onto the records it describes. A separate,
 *     explicit act, refused for anything not already approved, and refused
 *     again if the code fails its own check digit at the moment of writing.
 *
 * Keeping them apart is the whole design. If approval applied, then a
 * reviewer agreeing that 718-7 is the right code for haemoglobin would, in the
 * same click, have re-labelled every haemoglobin result on the platform.
 *
 * NO FACILITY FROM THE REQUEST. The proposals are platform-wide
 * (`facility_id` null) and everything under /admin/ is platform tier, so this
 * desk is cross-facility by definition. The only facility id it will accept is
 * the one the auth middleware put on the request attributes; a header, body
 * field, query string or "the one facility we found" is never consulted. The
 * value is used to stamp the audit trail, never to widen or narrow what is
 * shown.
 *
 * NO PATIENT ENUMERATION. The blast-radius view counts rows and shows the
 * analyte, the unit and the code already on the row. It renders no patient
 * name and no Health ID.
 */
class TerminologyController extends Controller
{
    /** Statuses `code_system_mappings.status` may hold. Matches the migration. */
    private const STATUSES = ['pending', 'approved', 'rejected', 'deprecated'];

    public function __construct(private readonly TerminologyApplier $applier)
    {
    }

    // ── Queue ────────────────────────────────────────────────────────────

    /**
     * The review queue.
     *
     * Pending first, and inside pending the ones needing a JUDGEMENT before
     * the ones needing a nod: a `review` confidence means a defensible
     * alternative code exists and the approver has to choose. Those must not
     * be buried below thirty rubber stamps.
     */
    public function index(Request $request)
    {
        $query = CodeSystemMapping::query();

        $status = $request->string('status')->toString();
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $system = $request->string('system')->toString();
        if ($system !== '') {
            $query->whereRaw('lower(replace(standard_system, \'-\', \'\')) = ?', [
                strtolower(str_replace('-', '', $system)),
            ]);
        }

        $mappings = $query
            ->orderByRaw("case when status = 'pending' then 0 when status = 'approved' then 1 else 2 end")
            ->orderByRaw("case when mapping_confidence = 'review' then 0 else 1 end")
            ->orderBy('resource_type')
            ->orderBy('local_code')
            ->paginate(50)
            ->withQueryString();

        // Blast radius per row. Cheap here (tens of rows, indexed lookups) and
        // the number is the point: an approver who cannot see that a mapping
        // reaches 34 results is approving blind.
        $radius = [];
        foreach ($mappings as $mapping) {
            $radius[$mapping->id] = $this->applier->radius($mapping);
        }

        // Reading a blast radius reads patient-linked rows, so the queue read
        // is access-logged like any other read of them. No mapping id: this is
        // a read of the whole queue, not of one proposal.
        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);
        $this->applier->log(
            TerminologyApplier::ACTION_PREVIEW, null,
            $actorId, $actorRole, $facilityId, $ip,
            ['after' => ['scope' => 'queue', 'mappings_read' => $mappings->count()]],
        );

        $systems = CodeSystemMapping::query()
            ->select('standard_system')->distinct()->orderBy('standard_system')->pluck('standard_system');

        $counts = CodeSystemMapping::query()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $statuses = self::STATUSES;

        return view('portals.admin.terminology.index', compact(
            'mappings', 'radius', 'systems', 'counts', 'statuses', 'status', 'system'
        ));
    }

    /**
     * Browse the published vocabularies themselves — WHO ICD-10 and LOINC.
     *
     * The review queue answers "is OUR local code mapped correctly". This
     * answers "what IS the code for X", which is the question a clinician
     * choosing a diagnosis or a lab manager building a test menu actually
     * has, and which until now had no answer inside the platform at all.
     *
     * Not access-logged, and deliberately so: this reads WHO and LOINC
     * reference data and touches no patient row. Logging it would bury the
     * log that matters — who opened whose record — under lookups of a public
     * code table.
     */
    public function dictionary(Request $request)
    {
        // The allow-list comes from the TABLE, never from a literal.
        //
        // This read `in_array($system, ['ICD-10', 'LOINC'])` when the dictionary
        // held exactly those two. It now holds eight, and the filter silently
        // discarded any request for the other six — ATC, UCUM, CVX, EDQM-route,
        // SNOMED-CT, HL7-service-type were all in the table and unreachable
        // from this screen. A hardcoded list of what a data table contains is a
        // bug with a delay on it: correct on the day it is written, wrong the
        // first time somebody adds a row.
        //
        // Still an allow-list, not a pass-through: the value reaches a WHERE,
        // so it is checked against what actually exists rather than trusted.
        $systems = ClinicalCodeDictionary::query()
            ->select('system')
            ->distinct()
            ->orderBy('system')
            ->pluck('system');

        $system = $request->query('system');
        $system = $systems->contains($system) ? $system : null;

        $codes = ClinicalCodeDictionary::query()
            ->search($request->query('q'))
            ->when($system, fn ($q) => $q->where('system', $system))
            ->when($request->boolean('common'), fn ($q) => $q->where('is_common', true))
            ->when(! $request->boolean('headings'), fn ($q) => $q->assignable())
            ->orderBy('system')
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        // The standing state of the dictionary. A total of zero means the
        // importers have never been run, which is a very different thing from
        // a search that found nothing — and the screen must not let those two
        // look the same.
        $totals = ClinicalCodeDictionary::query()
            ->selectRaw('system, count(*) as total')
            ->selectRaw('count(*) filter (where is_common) as common')
            ->groupBy('system')
            ->get()
            ->keyBy('system');

        // Provenance, shown on the page. Reference data whose release nobody
        // can see is reference data nobody can check.
        $releases = ClinicalCodeDictionary::query()
            ->select('system', 'source', 'source_version')
            ->distinct()
            ->orderBy('system')
            ->get();

        return view('portals.admin.terminology.dictionary', compact('codes', 'totals', 'releases', 'system', 'systems'));
    }

    /**
     * The reconciliation report: this deployment's own clinical values read
     * back against the dictionary, and what does not resolve.
     *
     * READ-ONLY, AND THERE IS DELIBERATELY NO COUNTERPART POST. The dictionary
     * screen asks "what is the code for this"; this one asks "does what we
     * already wrote down still mean anything". Neither writes. The page carries
     * no fix button on purpose: the remedy for a superseded code on a clinical
     * record is an amendment by an accountable person through the mapping desk
     * above, not a bulk update launched from a report.
     *
     * NOT LOGGED AS PATIENT ACCESS, unlike show() below. show() reads the rows
     * one mapping would land on, which are patient-linked; this reads counts
     * and distinct code values across whole tables and can say nothing about
     * any one patient. The reasoning is written out on TerminologyReconciler.
     */
    public function gaps(TerminologyReconciler $reconciler)
    {
        $findings = $reconciler->report();
        $vocabularies = $reconciler->vocabularies();

        return view('portals.admin.terminology.gaps', compact('findings', 'vocabularies'));
    }

    /**
     * One mapping in full: the local term with its unit, the proposed code,
     * the reviewer's note, and every row the code would land on.
     */
    public function show(Request $request, string $id)
    {
        $mapping = $this->findOrFail($id);

        $candidates = $this->applier->candidates($mapping);
        $radius     = $this->applier->radius($mapping);
        $refusal    = $this->applier->refusalReason($mapping);
        $wellFormed = $this->applier->codeIsWellFormed($mapping);

        $history = AuditEvent::query()
            ->where('resource_type', 'code_system_mapping')
            ->where('resource_id', $mapping->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Reading a blast radius reads patient-linked rows, so it is logged
        // like any other read of them.
        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);
        $this->applier->log(
            TerminologyApplier::ACTION_PREVIEW, (string) $mapping->id,
            $actorId, $actorRole, $facilityId, $ip,
            ['after' => ['scope' => 'mapping', 'candidates' => $candidates->count()]],
        );

        return view('portals.admin.terminology.show', compact(
            'mapping', 'candidates', 'radius', 'refusal', 'wellFormed', 'history'
        ));
    }

    // ── Review ───────────────────────────────────────────────────────────

    /** Approve a mapping. Writes to the mapping only — never to a record. */
    public function approve(Request $request, string $id)
    {
        $mapping = $this->findOrFail($id);

        if (! $mapping->isPending()) {
            return back()->with('error', __('terminology.flash_not_pending'));
        }

        // A malformed code must not be approvable. Approving it would put a
        // code that cannot exist one button away from being written.
        if (! $this->applier->codeIsWellFormed($mapping)) {
            return back()->with('error', __('terminology.flash_malformed'));
        }

        $mapping->approve((string) $request->user()->id);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);
        $this->applier->log(
            'terminology.approved', (string) $mapping->id,
            $actorId, $actorRole, $facilityId, $ip,
            ['after' => ['status' => 'approved', 'standard_code' => $mapping->standard_code]],
        );

        return back()->with('success', __('terminology.flash_approved'));
    }

    /** Reject a mapping. The reason is mandatory and is kept on the record. */
    public function reject(Request $request, string $id)
    {
        $mapping = $this->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ]);

        if (! $mapping->isPending()) {
            return back()->with('error', __('terminology.flash_not_pending'));
        }

        $reason = trim($validated['reason']);
        $before = $mapping->notes;

        $mapping->reject();

        // There is no rejection-reason column, and adding one would mean a
        // migration. The reason is appended to `notes` — never replacing what
        // was there, because the seeder's "an alternative exists, confirm it"
        // note is the context the rejection answers — and is also written to
        // the audit trail in full, where nothing can truncate it.
        $mapping->forceFill([
            'notes' => Str::limit(trim(implode("\n", array_filter([
                $before,
                'Rejected ' . now()->toDateTimeString() . ': ' . $reason,
            ]))), 500, ''),
            'approved_by' => (string) $request->user()->id,
            'approved_at' => now(),
        ])->save();

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);
        $this->applier->log(
            'terminology.rejected', (string) $mapping->id,
            $actorId, $actorRole, $facilityId, $ip,
            [
                'reason' => $reason,
                'before' => ['status' => 'pending', 'notes' => $before],
                'after'  => ['status' => 'rejected'],
            ],
        );

        return back()->with('success', __('terminology.flash_rejected'));
    }

    // ── Apply ────────────────────────────────────────────────────────────

    /**
     * Put an approved code onto the records it describes.
     *
     * Every refusal path lands here as a flash message naming the reason, not
     * as a silent no-op: "nothing happened" and "we refused, because" must
     * never look the same on a clinical desk.
     */
    public function apply(Request $request, string $id)
    {
        $mapping = $this->findOrFail($id);

        $request->validate([
            'confirm_recode' => ['nullable', 'boolean'],
        ]);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);

        $result = $this->applier->apply(
            $mapping,
            $actorId,
            $actorRole,
            $facilityId,
            $request->boolean('confirm_recode'),
            $ip,
        );

        if ($result['refused'] !== null) {
            return back()->with('error', __($result['refused']));
        }

        return back()->with('success', __('terminology.flash_applied', [
            'applied'  => $result['applied'],
            'recoded'  => $result['recoded'],
            'conflict' => $result['skipped'][TerminologyApplier::CONFLICT] ?? 0,
            'mismatch' => $result['skipped'][TerminologyApplier::UNIT_MISMATCH] ?? 0,
            'already'  => $result['skipped'][TerminologyApplier::ALREADY] ?? 0,
        ]));
    }

    /** Everything this desk has read, approved, rejected or written. */
    public function log(Request $request)
    {
        $events = AuditEvent::query()
            ->where('resource_type', 'code_system_mapping')
            ->whereIn('action_type', [
                TerminologyApplier::ACTION_APPLY,
                TerminologyApplier::ACTION_RUN,
                'terminology.approved',
                'terminology.rejected',
            ])
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('portals.admin.terminology.log', compact('events'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * A mapping by id, or 404.
     *
     * The uuid shape is checked before the query so a malformed id is an
     * honest 404 rather than a Postgres 22P02 cast error surfacing as a 500.
     */
    private function findOrFail(string $id): CodeSystemMapping
    {
        abort_unless(Str::isUuid($id), 404);

        return CodeSystemMapping::query()->findOrFail($id);
    }

    /**
     * Actor, role, facility and IP for the audit trail.
     *
     * The facility comes from `$request->attributes` — set by the auth
     * middleware — and from nowhere else. Not `?facility_id=`, not a posted
     * field, not a header, not "the only facility in the table". If the
     * middleware did not set one, this desk records none; a platform-tier
     * cross-facility action legitimately has no facility, and inventing one
     * would put a false facility on an audit row.
     *
     * @return array{0:?string,1:?string,2:?string,3:?string}
     */
    private function actor(Request $request): array
    {
        $user = $request->user();
        $facilityId = $request->attributes->get('facility_id');

        return [
            $user?->id ? (string) $user->id : null,
            $user?->role?->name,
            is_string($facilityId) && Str::isUuid($facilityId) ? $facilityId : null,
            $request->ip(),
        ];
    }
}
