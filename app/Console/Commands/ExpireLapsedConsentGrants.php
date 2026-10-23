<?php

namespace App\Console\Commands;

use App\Models\ConsentGrant;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Move consent grants whose validity has lapsed from `active` to `expired`.
 *
 * `consent_grants.status` was written once at approval and never swept — there
 * was no command, job or schedule entry that touched it. A grant ninety days
 * past its `expires_at` still read `status = 'active'` in the table.
 *
 * That is NOT how any access decision was being made, and it is worth being
 * precise about why, because it changes what this command is for. Every reader
 * that gates access independently checks the date as well as the column —
 * `ConsentService::grantsFor()`, `ClinicalRelationshipGate`,
 * `MobileDoctorChartController::hasActiveConsentGrant()` and both consent reads
 * in `MobileMessagingController` all require
 * `status = 'active' AND expires_at >= now()`. So a stale column never opened a
 * record it should not have. Defence in depth did its job.
 *
 * Where it did bite is reporting. `GenerateMinsanteMonthlyReport` groups grants
 * by `status` alone, so lapsed consents were being counted as active in a return
 * to the health ministry — the platform over-stating how much live consent it
 * holds. A patient's own consent screen had the same problem until it started
 * deriving state from the date.
 *
 * The fix belongs here rather than in each reader: one writer, so the column
 * stops disagreeing with reality, and every future reader inherits the truth
 * instead of the bug.
 *
 * Deliberately NOT touched:
 *  - grants with a NULL `expires_at`. The column is NOT NULL in the schema, so
 *    such a row cannot exist — verified by trying to insert one and getting
 *    SQLSTATE 23502. The guard stays as a cheap assertion of that fact rather
 *    than as handling for a case that can occur.
 *  - anything not `active`. `revoked` is a patient's decision and must never be
 *    rewritten by a sweeper.
 */
class ExpireLapsedConsentGrants extends Command
{
    protected $signature = 'consent:expire-lapsed {--dry-run : Report what would change without writing}';

    protected $description = 'Flip consent grants whose expires_at has passed from active to expired';

    public function handle(): int
    {
        $now = Carbon::now();

        $query = ConsentGrant::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now);

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No lapsed consent grants.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("{$count} grant(s) would move from active to expired.");

            foreach ((clone $query)->limit(20)->get(['id', 'patient_id', 'facility_id', 'expires_at']) as $g) {
                $this->line("  {$g->id}  patient={$g->patient_id}  expired={$g->expires_at}");
            }

            return self::SUCCESS;
        }

        // updateQuietly-equivalent: a bulk update fires no model events, which is
        // what we want — this is a clock catching up with itself, not a consent
        // decision, and it must not look like one in any listener or audit trail.
        //
        // The ids are read first so each partner facility can be told
        // (consent.expired) — a webhook is a notification to the grantee, not a
        // consent decision, and it is sent only for rows this run flipped.
        $ids = (clone $query)->pluck('id')->all();

        $updated = ConsentGrant::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->update([
                'status'     => 'expired',
                'updated_at' => $now,
            ]);

        foreach (array_chunk($ids, 500) as $chunk) {
            ConsentGrant::withoutGlobalScopes()->whereIn('id', $chunk)->where('status', 'expired')->get()
                ->each(fn (ConsentGrant $grant) => \App\Modules\Connect\Services\WebhookEventPublisher::consentExpired($grant));
        }

        $this->info("Expired {$updated} lapsed consent grant(s).");

        return self::SUCCESS;
    }
}
