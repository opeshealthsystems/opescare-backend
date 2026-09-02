<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\BridgeAgent;
use App\Models\BridgeSyncBatch;
use App\Models\Facility;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * BridgeAdminController — registration and monitoring of the on-prem sync
 * agents that push a facility's data into OpesCare.
 *
 * A bridge agent key is a long-lived credential that writes on a facility's
 * behalf, so which facility it is minted for is the whole security property.
 * It used to be `Facility::value('id')` — whichever row Postgres returned
 * first — which meant a key issued here could authorise writes into a hospital
 * nobody chose, and `toggle()`/`batches()` did a bare `findOrFail($id)`, so any
 * agent in the country could be suspended, re-enabled, or have its sync history
 * read by pasting its id.
 *
 * The list already failed closed when no facility resolved (`whereRaw('1 = 0')`
 * — platform-tier accounts see nothing, because a bridge agent belongs to a
 * facility and they have none). That behaviour is kept for the reads; the
 * writes now fail closed loudly instead of guessing.
 */
class BridgeAdminController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    /**
     * The facility this request acts for, or null when none is resolved.
     *
     * Null is a legitimate answer here: RequireFacilityContext waves
     * platform-admin roles through without a facility, and this screen lives
     * under portals/admin. Reads treat null as "nothing to show"; the write
     * path calls facilityIdOrFail() instead.
     */
    private function facilityId(): ?string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '' && Str::isUuid($resolved)) {
            return $resolved;
        }

        // Single-facility deployments only — the one case where "the facility"
        // is unambiguous. With more than one there is no safe guess.
        if (Facility::count() === 1) {
            $id = Facility::value('id');

            return ($id && Str::isUuid($id)) ? $id : null;
        }

        return null;
    }

    /** The facility for a write. No context, no key: 409 rather than a guess. */
    private function facilityIdOrFail(): string
    {
        $facilityId = $this->facilityId();

        abort_if(
            $facilityId === null,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which facility this bridge agent would write for. Select a facility first.'
        );

        return $facilityId;
    }

    /** A bridge agent, only if it belongs to the acting facility. */
    private function agentAtFacility(string $id): BridgeAgent
    {
        abort_unless(Str::isUuid($id), 404);

        return BridgeAgent::where('id', $id)
            ->where('facility_id', $this->facilityIdOrFail())
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $facilityId = $this->facilityId();

        $agents = BridgeAgent::query()
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->when(! $facilityId, fn ($q) => $q->whereRaw('1 = 0'))
            ->withCount('syncBatches')
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        $agentBase = fn () => BridgeAgent::query()->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))->when(! $facilityId, fn ($q) => $q->whereRaw('1 = 0'));
        $batchBase = fn () => BridgeSyncBatch::query()->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))->when(! $facilityId, fn ($q) => $q->whereRaw('1 = 0'));

        $stats = [
            'total'        => $agentBase()->count(),
            'active'       => $agentBase()->where('status', 'active')->count(),
            'totalBatches' => $batchBase()->count(),
            'failedBatches'=> $batchBase()->where('status', 'failed')->count(),
        ];

        return view('portals.admin.bridge.index', compact('agents', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $facilityId = $this->facilityIdOrFail();

        $rawKey = 'bak_' . Str::random(40);
        $hash   = hash('sha256', $rawKey);
        $prefix = substr($rawKey, 0, 12);

        BridgeAgent::create([
            'facility_id'    => $facilityId,
            'name'           => $request->name,
            'agent_key'      => $hash,
            'agent_key_prefix' => $prefix,
            'status'         => 'active',
            'notes'          => $request->notes,
            'registered_by'  => session('auth_email') ?: 'demo-admin',
        ]);

        return redirect()->route('portals.admin.bridge')
            ->with('success', __('flash.bridge_agent_registered'))
            ->with('new_agent_key', $rawKey);
    }

    public function toggle(string $id)
    {
        $agent = $this->agentAtFacility($id);
        $agent->update(['status' => $agent->status === 'active' ? 'suspended' : 'active']);

        return redirect()->route('portals.admin.bridge')
            ->with('success', __('flash.bridge_agent_status_updated'));
    }

    public function batches(string $id)
    {
        $agent = $this->agentAtFacility($id);

        $batches = BridgeSyncBatch::where('bridge_agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();

        return view('portals.admin.bridge.batches', compact('agent', 'batches'));
    }
}
