<?php

namespace Database\Seeders;

use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Repoints existing demo records off the five invented facilities and onto the
 * real institutions they stood in for, then removes the invented rows.
 *
 * WHY A REPAIR SEEDER AND NOT JUST A FIX IN THE SEEDERS
 * ----------------------------------------------------
 * The demo seeders now resolve real institutions at run time, which fixes any
 * database seeded from scratch. It does nothing for a database that already
 * has data, because those seeders are insert-only by design — upsertVisit()
 * and friends check `doesntExist()` and skip, precisely so a re-run never
 * clobbers clinical content. Correct behaviour, but it means the old rows keep
 * pointing at "Demo Central Hospital" for ever.
 *
 * This closes that gap once. It is the only place allowed to rewrite an
 * existing record's facility, and it rewrites nothing else — no clinical
 * values, no dates, no status.
 *
 * The column list is discovered from information_schema rather than hardcoded,
 * so a table added later is covered without anyone remembering to update a
 * list here. 87 columns reference `facilities`; only a handful ever hold these
 * ids, and the rest are no-ops.
 *
 * Idempotent: a second run finds nothing to move and no rows to delete.
 */
class RealFacilityRepairSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [];

        foreach (DemoFacilityResolver::LEGACY_DEMO_FACILITIES as $legacyId => $directoryName) {
            // Only repoint when the legacy row is actually present. On a fresh
            // database it never existed and there is nothing to repair.
            if (! DB::table('facilities')->where('id', $legacyId)->exists()) {
                continue;
            }

            $realId = DemoFacilityResolver::resolve($directoryName);

            if (! $realId) {
                $this->command?->warn(
                    "RealFacilityRepairSeeder: '{$directoryName}' is not in the directory — "
                    . "leaving {$legacyId} in place rather than orphaning its records."
                );
                continue;
            }

            $mapping[$legacyId] = $realId;
        }

        if ($mapping === []) {
            $this->command?->info('RealFacilityRepairSeeder: nothing to repair.');

            return;
        }

        $columns = $this->facilityReferencingColumns();
        $moved   = 0;
        $touched = [];

        foreach ($columns as [$table, $column]) {
            foreach ($mapping as $legacyId => $realId) {
                $n = DB::table($table)->where($column, $legacyId)->update([$column => $realId]);

                if ($n > 0) {
                    $moved += $n;
                    $touched["{$table}.{$column}"] = ($touched["{$table}.{$column}"] ?? 0) + $n;
                }
            }
        }

        // Safe to remove now: every foreign key that pointed at them has been
        // repointed above, so this cannot orphan a record.
        $deleted = DB::table('facilities')->whereIn('id', array_keys($mapping))->delete();

        foreach ($touched as $where => $n) {
            $this->command?->line("  {$where}: {$n} row(s) repointed");
        }

        $this->command?->info(sprintf(
            'RealFacilityRepairSeeder: %d row(s) moved onto real institutions across %d column(s); %d invented facility(ies) removed.',
            $moved,
            count($touched),
            $deleted
        ));
    }

    /**
     * Every column with a foreign key to `facilities`.
     *
     * @return list<array{0:string,1:string}>
     */
    private function facilityReferencingColumns(): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT tc.table_name, kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON kcu.constraint_name = tc.constraint_name
            JOIN information_schema.constraint_column_usage ccu
              ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND ccu.table_name = 'facilities'
              AND tc.table_schema = 'public'
        SQL);

        return array_map(
            static fn ($r) => [$r->table_name, $r->column_name],
            $rows
        );
    }
}
