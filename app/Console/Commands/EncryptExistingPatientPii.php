<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;

class EncryptExistingPatientPii extends Command
{
    protected $signature = 'opescare:encrypt-patient-pii
                            {--dry-run : Show what would be encrypted without making changes}
                            {--batch=100 : Process N patients at a time}';

    protected $description = 'Encrypt existing plain-text PII fields in the patients table (date_of_birth, phone_number, address)';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        $this->info($dryRun ? '[DRY RUN] Scanning patients...' : 'Encrypting patient PII...');

        $total     = 0;
        $encrypted = 0;
        $errors    = 0;

        Patient::withoutGlobalScopes()
            ->chunkById($batchSize, function ($patients) use ($dryRun, &$total, &$encrypted, &$errors) {
                foreach ($patients as $patient) {
                    $total++;
                    try {
                        if ($dryRun) {
                            $this->line("Would encrypt patient: {$patient->health_id}");
                            $encrypted++;
                            continue;
                        }

                        // Re-save through model — encrypted cast will encrypt the current value
                        $patient->touch();
                        $encrypted++;

                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("Failed to encrypt patient {$patient->health_id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Done. Total: {$total} | Encrypted: {$encrypted} | Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
