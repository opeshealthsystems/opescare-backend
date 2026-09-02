<?php

namespace App\Services\Identity;

use App\Models\Patient;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

class HealthIdGeneratorService
{
    /**
     * Safe alphabet: omits characters that look alike (0 vs O, 1 vs I vs L).
     * Fixed string — never interpolate into a regex; use the VALID_PATTERN constant instead.
     */
    const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Hard-coded regex pattern for format validation.
     * Uses a literal character class derived from ALPHABET — NOT interpolated from the
     * constant, to avoid regex injection if the constant is ever accidentally mutated.
     */
    const VALID_PATTERN = '/^[A-Z]{2}-HID-[A-HJKLMNPQRSTUVWXYZ23456789]{4}-[A-HJKLMNPQRSTUVWXYZ23456789]{4}-[A-HJKLMNPQRSTUVWXYZ23456789]{4}$/';

    /**
     * Maximum number of generation attempts before giving up.
     * Configurable via config('health_id.max_retries'); defaults to 10.
     */
    private function maxRetries(): int
    {
        return (int) config('health_id.max_retries', 10);
    }

    /**
     * Generate a new unique Health ID — atomic, race-condition safe.
     *
     * Strategy: generate a candidate, attempt DB INSERT with a UNIQUE constraint on
     * health_id. On UniqueConstraintViolationException (concurrent insert won the race),
     * generate a fresh candidate and retry. This is safer than the non-atomic
     * exists() → create() pattern, which has a TOCTOU race window.
     *
     * Callers MUST wrap this call inside a DB::transaction() to ensure the returned
     * health_id is committed atomically with the rest of the patient record.
     *
     * FIX (audit 2026-06-18): Now accepts an optional caller callback that is invoked
     * inside the retry loop. This allows the caller to pass the patient creation closure
     * directly into the generator, eliminating the TOCTOU window between generate()
     * and create(). When $createPatientCallback is provided, the generator handles
     * retry internally. When null, the old behaviour is preserved (caller handles retry).
     *
     * Format:  COUNTRY-HID-BLOCK1-BLOCK2-CHECKBLOCK
     * Example: CM-HID-7KQ9-MP42-X8D1
     *
     * @param  string    $countryCode            ISO 3166-1 alpha-2 country code
     * @param  callable|null $createPatientCallback fn(string $healthId): Patient
     * @return Patient|string  Patient if callback provided, health_id string otherwise
     *
     * @throws \RuntimeException If a unique ID cannot be generated within max retries.
     */
    public function generate(string $countryCode = 'CM', ?callable $createPatientCallback = null): mixed
    {
        $countryCode = strtoupper(substr($countryCode, 0, 2));
        $maxRetries  = $this->maxRetries();

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $candidate = $this->buildCandidate($countryCode);

            // When a callback is provided, attempt the full INSERT inside the loop
            // so UniqueConstraintViolationException triggers an automatic retry.
            if ($createPatientCallback !== null) {
                try {
                    return $createPatientCallback($candidate);
                } catch (UniqueConstraintViolationException $e) {
                    Log::warning('health_id_collision_retry', [
                        'attempt'      => $attempt,
                        'country_code' => $countryCode,
                        'candidate'    => substr($candidate, 0, 8) . '…',
                    ]);
                    continue;
                }
            }

            // Legacy path (no callback): use non-atomic existence check.
            if ($this->isUnique($candidate)) {
                return $candidate;
            }

            Log::warning('health_id_collision', [
                'attempt'      => $attempt,
                'country_code' => $countryCode,
            ]);
        }

        throw new \RuntimeException(
            "Failed to generate a unique Health ID after {$maxRetries} attempts. "
            . "This may indicate a namespace exhaustion or misconfiguration."
        );
    }

    /**
     * Build a single candidate Health ID string (not yet checked for uniqueness).
     */
    private function buildCandidate(string $countryCode): string
    {
        $block1          = $this->generateRandomBlock(4);
        $block2          = $this->generateRandomBlock(4);
        $baseForChecksum = $countryCode . 'HID' . $block1 . $block2;
        $checkBlock      = $this->calculateCheckBlock($baseForChecksum);

        return sprintf('%s-HID-%s-%s-%s', $countryCode, $block1, $block2, $checkBlock);
    }

    /**
     * Generate a cryptographically secure random block using the safe alphabet.
     */
    protected function generateRandomBlock(int $length): string
    {
        $block    = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $block .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $block;
    }

    /**
     * Calculate a deterministic 4-character check block from a base string.
     * Uses SHA-256 → first 8 hex chars → base-N digit extraction.
     */
    protected function calculateCheckBlock(string $baseString): string
    {
        $hash   = hash('sha256', $baseString);
        $intVal = hexdec(substr($hash, 0, 8));
        $base   = strlen(self::ALPHABET);

        $checkBlock = '';
        for ($i = 0; $i < 4; $i++) {
            $checkBlock .= self::ALPHABET[$intVal % $base];
            $intVal      = intdiv($intVal, $base);
        }

        // Reverse so higher-order bits appear first.
        return strrev($checkBlock);
    }

    /**
     * Validate a Health ID string: checks format regex AND recalculates the check block.
     */
    public function isValid(string $healthId): bool
    {
        if (!preg_match(self::VALID_PATTERN, $healthId)) {
            return false;
        }

        $parts              = explode('-', $healthId);
        $country            = $parts[0];
        $block1             = $parts[2];
        $block2             = $parts[3];
        $providedCheckBlock = $parts[4];

        $expectedCheckBlock = $this->calculateCheckBlock($country . 'HID' . $block1 . $block2);

        return hash_equals($expectedCheckBlock, $providedCheckBlock);
    }

    /**
     * Check database uniqueness.
     * NOTE: non-atomic — callers must rely on DB UNIQUE constraint as the final
     * arbitration, and catch UniqueConstraintViolationException on insert.
     */
    protected function isUnique(string $healthId): bool
    {
        return !Patient::withoutGlobalScope('isolate_demo')
            ->where('health_id', $healthId)
            ->exists();
    }
}
