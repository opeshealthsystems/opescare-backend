<?php
namespace App\Services\Compliance;

use App\Models\DataRetentionPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionService
{
    public function getActivePolicies(): Collection
    {
        return DataRetentionPolicy::where('is_active', true)->get();
    }

    /**
     * Enforce all active retention policies.
     *
     * @param  bool $dryRun  If true, log what would be deleted without actually deleting
     * @return array         Summary: ['table' => ['action', 'count', 'cutoff']]
     */
    public function enforce(bool $dryRun = false): array
    {
        $summary = [];

        foreach ($this->getActivePolicies() as $policy) {
            $cutoff = now()->subDays($policy->retention_days)->toDateTimeString();

            try {
                if (!DB::getSchemaBuilder()->hasColumn($policy->table_name, 'created_at')) {
                    Log::warning("DataRetention: table {$policy->table_name} has no created_at — skipping");
                    continue;
                }

                $query = DB::table($policy->table_name)->where('created_at', '<', $cutoff);
                $count = $query->count();

                if (!$dryRun && $count > 0) {
                    match ($policy->purge_action) {
                        'delete'    => $query->delete(),
                        'anonymise' => $query->update(['anonymised_at' => now()]),
                        default     => null,
                    };
                    $policy->update(['last_run_at' => now(), 'last_run_purged' => $count]);
                }

                $summary[$policy->table_name] = [
                    'action'  => $dryRun ? 'dry_run' : $policy->purge_action,
                    'count'   => $count,
                    'cutoff'  => $cutoff,
                ];

                Log::info("DataRetention [{$policy->table_name}]: {$count} record(s)" . ($dryRun ? ' (dry run)' : " {$policy->purge_action}d"));
            } catch (\Throwable $e) {
                Log::error("DataRetention: failed for {$policy->table_name}", ['error' => $e->getMessage()]);
            }
        }

        return $summary;
    }
}
