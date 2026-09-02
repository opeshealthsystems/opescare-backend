<?php

namespace App\Http\Controllers\Api\V1\Bridge;

use App\Http\Controllers\Controller;
use App\Models\BridgeAgent;
use App\Models\BridgeSyncBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BridgeSyncController extends Controller
{
    /**
     * Receive a sync batch posted by a Bridge Agent.
     *
     * POST /v1/bridge/sync
     * Header: X-Bridge-Agent-Key: <raw_key>
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sync_type' => 'required|in:ehr_records,lab_results,appointments,billing,pharmacy_stock,blood_stock',
            'records'   => 'required|array|min:1|max:500',
            'checksum'  => 'nullable|string|size:64',
        ]);

        /** @var BridgeAgent $agent */
        $agent = $request->attributes->get('bridge_agent');

        $batch = BridgeSyncBatch::create([
            'bridge_agent_id' => $agent->id,
            'facility_id'     => $agent->facility_id,
            'sync_type'       => $data['sync_type'],
            'status'          => 'processing',
            'record_count'    => count($data['records']),
            'checksum'        => $data['checksum'] ?? null,
        ]);

        try {
            $result = $this->processRecords(
                $agent->facility_id,
                $data['sync_type'],
                $data['records'],
                $batch
            );

            $batch->update([
                'status'         => 'completed',
                'inserted_count' => $result['inserted'],
                'updated_count'  => $result['updated'],
                'skipped_count'  => $result['skipped'],
                'error_count'    => count($result['errors']),
                'errors'         => $result['errors'] ?: null,
                'completed_at'   => now(),
            ]);

            $agent->update(['last_sync_at' => now()]);

            return response()->json([
                'batch_id'   => $batch->id,
                'status'     => 'completed',
                'sync_type'  => $data['sync_type'],
                'inserted'   => $result['inserted'],
                'updated'    => $result['updated'],
                'skipped'    => $result['skipped'],
                'errors'     => count($result['errors']),
                'synced_at'  => now()->toIso8601String(),
            ], 201);

        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'errors' => [['message' => $e->getMessage()]]]);
            Log::error('Bridge sync failure', [
                'batch_id'  => $batch->id,
                'agent_id'  => $agent->id,
                'sync_type' => $data['sync_type'],
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'batch_id' => $batch->id,
                'status'   => 'failed',
                'error'    => __('api.sync_processing_error'),
            ], 500);
        }
    }

    /**
     * Heartbeat — agent announces its presence and capabilities.
     *
     * POST /v1/bridge/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version'      => 'nullable|string|max:20',
            'hostname'     => 'nullable|string|max:150',
            'capabilities' => 'nullable|array',
        ]);

        /** @var BridgeAgent $agent */
        $agent = $request->attributes->get('bridge_agent');

        $agent->update(array_filter([
            'version'      => $data['version'] ?? null,
            'hostname'     => $data['hostname'] ?? null,
            'capabilities' => $data['capabilities'] ?? null,
            'last_seen_at' => now(),
        ]));

        return response()->json([
            'status'      => 'ok',
            'agent_id'    => $agent->id,
            'facility_id' => $agent->facility_id,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Status — agent queries its last sync results.
     *
     * GET /v1/bridge/status
     */
    public function status(Request $request): JsonResponse
    {
        /** @var BridgeAgent $agent */
        $agent = $request->attributes->get('bridge_agent');

        $lastBatches = BridgeSyncBatch::where('bridge_agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'sync_type', 'status', 'record_count', 'inserted_count', 'error_count', 'created_at', 'completed_at']);

        return response()->json([
            'agent_id'      => $agent->id,
            'facility_id'   => $agent->facility_id,
            'agent_status'  => $agent->status,
            'last_sync_at'  => $agent->last_sync_at?->toIso8601String(),
            'last_seen_at'  => $agent->last_seen_at?->toIso8601String(),
            'recent_batches'=> $lastBatches->map(fn($b) => [
                'batch_id'   => $b->id,
                'sync_type'  => $b->sync_type,
                'status'     => $b->status,
                'records'    => $b->record_count,
                'inserted'   => $b->inserted_count,
                'errors'     => $b->error_count,
                'created_at' => $b->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Process incoming records according to sync_type.
     * Returns counts of inserted/updated/skipped/errors.
     */
    private function processRecords(string $facilityId, string $syncType, array $records, BridgeSyncBatch $batch): array
    {
        $inserted = 0; $updated = 0; $skipped = 0; $errors = [];

        foreach ($records as $i => $record) {
            try {
                $outcome = match($syncType) {
                    'appointments'    => $this->upsertAppointment($facilityId, $record),
                    'pharmacy_stock'  => $this->upsertPharmacyStock($facilityId, $record),
                    'blood_stock'     => $this->upsertBloodStock($facilityId, $record),
                    default           => 'skipped',
                };

                match($outcome) {
                    'inserted' => $inserted++,
                    'updated'  => $updated++,
                    default    => $skipped++,
                };
            } catch (\Throwable $e) {
                $errors[] = ['index' => $i, 'message' => $e->getMessage()];
            }
        }

        return compact('inserted', 'updated', 'skipped', 'errors');
    }

    private function upsertAppointment(string $facilityId, array $record): string
    {
        // Minimal upsert — extend per EMR source schema
        if (empty($record['external_id'])) return 'skipped';

        $existing = DB::table('appointments')
            ->where('facility_id', $facilityId)
            ->where('external_id', $record['external_id'])
            ->first();

        if ($existing) {
            DB::table('appointments')
                ->where('id', $existing->id)
                ->update([
                    'status'       => $record['status'] ?? $existing->status,
                    'scheduled_at' => $record['scheduled_at'] ?? $existing->scheduled_at,
                    'updated_at'   => now(),
                ]);
            return 'updated';
        }

        return 'skipped'; // new appointments from bridge are queued for human review
    }

    private function upsertPharmacyStock(string $facilityId, array $record): string
    {
        if (empty($record['item_code']) || !isset($record['quantity'])) return 'skipped';

        $updated = DB::table('pharmacy_inventory')
            ->where('facility_id', $facilityId)
            ->where('item_code', $record['item_code'])
            ->update([
                'quantity_available' => (int) $record['quantity'],
                'updated_at'         => now(),
            ]);

        return $updated ? 'updated' : 'skipped';
    }

    /**
     * Blood stock pushed by a facility's on-prem bridge agent.
     *
     * This wrote to `blood_inventory` / `units_available` — neither the table
     * nor the column exists (they are `blood_inventories` / `available_units`),
     * so every blood_stock batch threw and was recorded as an error. The
     * facility feed into the operational blood record was dead on arrival.
     *
     * A blood row is (group × component), so `component` is part of the key;
     * an agent that does not send one is reporting whole blood. Once written,
     * the patient-facing availability is re-published from it — the bridge and
     * the Blood Finder now answer with the same number.
     */
    private function upsertBloodStock(string $facilityId, array $record): string
    {
        if (empty($record['blood_group']) || !isset($record['units_available'])) return 'skipped';

        $component = $record['component'] ?? 'whole_blood';

        if (\App\Modules\CareMap\Services\BloodAvailabilityProjector::publishedComponent($component) === null
            && $component !== 'cryoprecipitate') {
            return 'skipped';   // unknown component vocabulary — do not guess
        }

        $updated = DB::table('blood_inventories')
            ->where('facility_id', $facilityId)
            ->where('blood_group', $record['blood_group'])
            ->where('component', $component)
            ->update([
                'available_units'   => max(0, (int) $record['units_available']),
                'last_stock_update' => now(),
                'updated_at'        => now(),
            ]);

        if ($updated) {
            app(\App\Modules\CareMap\Services\BloodAvailabilityProjector::class)->projectFacility($facilityId);
        }

        return $updated ? 'updated' : 'skipped';
    }
}
