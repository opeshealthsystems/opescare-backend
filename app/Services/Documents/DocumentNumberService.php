<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;
use Illuminate\Support\Str;

class DocumentNumberService
{
    /**
     * Generate a unique, structured, non-predictable document number and verification code.
     * Format: {TYPE}-{COUNTRY}-{YEAR}-{RANDOM}-{CHECK}
     * Verification Code Format: VFY-{COUNTRY}-{TYPE}-{YEAR}-{RANDOM}-{CHECK}
     */
    public function generateIdentifiers(string $type, string $country = 'CM'): array
    {
        $year = date('Y');
        $retryLimit = 10;
        $attempt = 0;

        do {
            $random = strtoupper(Str::random(4));
            $base = "{$type}-{$country}-{$year}-{$random}";
            $check = $this->calculateCheckDigit($base);
            $documentNumber = "{$base}-{$check}";
            
            $vBase = "VFY-{$country}-{$type}-{$year}-{$random}";
            $vCheck = $this->calculateCheckDigit($vBase);
            $verificationCode = "{$vBase}-{$vCheck}";

            $exists = OfficialDocument::where('document_number', $documentNumber)
                ->orWhere('verification_code', $verificationCode)
                ->exists();

            $attempt++;
        } while ($exists && $attempt < $retryLimit);

        if ($exists) {
            throw new \RuntimeException('Critical document identifier collision limits reached.');
        }

        return [
            'document_number' => $documentNumber,
            'verification_code' => $verificationCode,
            'verification_token' => 'vdt_' . Str::random(10), // Unique secure verification token
        ];
    }

    /**
     * Calculate a check character based on Modulo 36 algorithm.
     */
    public function calculateCheckDigit(string $input): string
    {
        $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $input));

        for ($i = 0; $i < strlen($clean); $i++) {
            $char = $clean[$i];
            $val = strpos($charset, $char);
            if ($val === false) {
                continue;
            }
            // Simple checksum calculation
            $sum += ($val * ($i + 1));
        }

        return $charset[$sum % 36];
    }
}
