<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PurgeRevokedTokens
 *
 * Nightly cleanup of the `revoked_tokens` table.
 *
 * A revocation entry is only needed until the token's original `exp` timestamp
 * passes — once the token has expired on its own it can no longer be used, so
 * the revocation record serves no purpose and can be pruned.
 *
 * Schedule: nightly at 00:30 (after midnight, low-traffic window).
 *
 * Dry run:
 *     php artisan health-id:purge-revoked-tokens --dry-run
 */
class PurgeRevokedTokens extends Command
{
    protected $signature = 'health-id:purge-revoked-tokens
                            {--dry-run : Count eligible rows without deleting}';

    protected $description = 'Remove expired rows from the revoked_tokens JTI blacklist table.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $count = DB::table('revoked_tokens')
            ->where('expires_at', '<', now())
            ->count();

        if ($dryRun) {
            $this->info("Would purge {$count} expired revocation record(s). No changes made.");
            return self::SUCCESS;
        }

        $deleted = DB::table('revoked_tokens')
            ->where('expires_at', '<', now())
            ->delete();

        Log::info('revoked_tokens_purged', ['deleted' => $deleted]);
        $this->info("Purged {$deleted} expired revocation record(s) from revoked_tokens.");

        return self::SUCCESS;
    }
}
