<?php

namespace App\Console\Commands;

use App\Models\CodeSystemMapping;
use App\Services\Terminology\TerminologyApplier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Approve the staged clinical code mappings and apply them.
 *
 * The mappings were seeded as `pending` so a clinician could sign them off
 * before any code reached a record. The owner's decision is that the review
 * happens during the testing phase with the codes already in place, which is
 * a reasonable call for a pre-launch environment whose purpose is to have
 * something to review.
 *
 * This invents nothing. It approves and applies exactly the mappings already
 * staged, every one check-digit verified before it was written. The malaria
 * rapid test stays unmapped: the code offered for it fails the LOINC check
 * digit and so is not a LOINC code.
 *
 * Every approval is attributed to a findable marker, so the reviewing doctor
 * can list precisely what was taken on trust:
 *
 *     CodeSystemMapping::where('approved_by', 'pending-clinical-review')
 *
 * and the applier records the before-state of every record it touches, so any
 * of it can be undone.
 */
class ApplyClinicalTerminology extends Command
{
    protected $signature = 'opescare:apply-terminology {--dry-run : Report what would happen and change nothing}';

    protected $description = 'Approve the staged LOINC/ICD-10 mappings and apply them to lab records';

    private const ACTOR = 'pending-clinical-review';

    public function handle(TerminologyApplier $applier): int
    {
        $dry = (bool) $this->option('dry-run');

        $pending = CodeSystemMapping::where('status', 'pending')->get();

        if (! $dry) {
            foreach ($pending as $mapping) {
                $mapping->approve(self::ACTOR);
            }
        }

        $this->info(($dry ? 'Would approve ' : 'Approved ') . $pending->count() . ' mapping(s) as ' . self::ACTOR . '.');
        $this->newLine();

        if ($dry) {
            return self::SUCCESS;
        }

        $written = 0;
        $skipped = 0;

        $rows = CodeSystemMapping::where('status', 'approved')
            ->orderBy('resource_type')
            ->orderBy('local_code')
            ->get();

        foreach ($rows as $mapping) {
            try {
                // Signature: (mapping, actorId, actorRole, facilityId,
                // confirmRecode, ip). confirmRecode stays FALSE — an existing
                // differing code is reported, never overwritten from a script.
                // actorId must be a UUID or null: audit_events.actor_id is a uuid
                // column, and passing the string marker here is the same
                // mistake that broke patient imports this morning. The marker
                // lives on the mapping's approved_by (a varchar), which is
                // where a reviewing clinician looks anyway.
                $result = $applier->apply($mapping, null, 'system', null, false, null);

                $refused = $result['refused'] ?? null;

                if ($refused !== null) {
                    $this->line(sprintf(
                        '  %-11s %-24s %-9s not applied - %s',
                        $mapping->resource_type,
                        $mapping->local_code,
                        $mapping->standard_code,
                        is_string($refused) ? $refused : json_encode($refused),
                    ));

                    continue;
                }

                $w = (int) ($result['applied'] ?? 0);
                // `skipped` is a map of reason => count, so SUM it. count() would
                // report the number of categories (always 3) and call an
                // untouched mapping a busy one.
                $skips = $result['skipped'] ?? [];
                $s = is_array($skips) ? (int) array_sum($skips) : (int) $skips;

                $written += $w;
                $skipped += $s;

                $this->line(sprintf(
                    '  %-11s %-24s %-9s coded=%-3d skipped=%d',
                    $mapping->resource_type,
                    $mapping->local_code,
                    $mapping->standard_code,
                    $w,
                    $s,
                ));
            } catch (Throwable $e) {
                // A refusal is information: the applier declines what it cannot
                // do safely and says why.
                $this->line(sprintf(
                    '  %-11s %-24s %-9s not applied - %s',
                    $mapping->resource_type,
                    $mapping->local_code,
                    $mapping->standard_code,
                    $e->getMessage(),
                ));
            }
        }

        $this->newLine();
        $this->info("Records coded: {$written}. Skipped: {$skipped}.");
        $this->warn('All mappings are attributed to ' . self::ACTOR . ' for review during testing.');

        return self::SUCCESS;
    }
}
