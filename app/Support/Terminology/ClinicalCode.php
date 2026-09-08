<?php

namespace App\Support\Terminology;

/**
 * Format and check-digit validation for the clinical code systems this
 * platform stores.
 *
 * ── Why this exists ───────────────────────────────────────────────────────
 * `lab_results.loinc_code`, `lab_test_availability.loinc_code`,
 * `diagnoses.code` and `problem_lists.icd_code` were plain nullable strings
 * with nothing checking them. A typo, a local code, or a number from the
 * wrong system stored just as happily as a real one — and a partner system
 * receiving it over FHIR would trust it.
 *
 * A wrong code is worse than no code. No code says "we do not know"; a wrong
 * code says something false about a patient, confidently, in a format other
 * systems are built to believe.
 *
 * ── What this can and cannot tell you ────────────────────────────────────
 * It can prove a code is MALFORMED. It cannot prove a code is the RIGHT one
 * for the test in front of you — that judgement belongs to a person, which is
 * why proposed mappings land in `code_system_mappings` as `pending` and are
 * used only once approved.
 *
 * The LOINC check digit is real arithmetic and catches transcription errors
 * outright. It found one while this file was being written: 70563-7, offered
 * for a malaria rapid test, fails its own check digit and is therefore not a
 * LOINC code at all. That test is now deliberately unmapped rather than
 * mapped wrongly.
 */
final class ClinicalCode
{
    /**
     * A LOINC code is digits, a hyphen, and a mod-10 check digit.
     *
     * The check digit is a Luhn variant: walking from the right, every other
     * digit starting with the rightmost is doubled, digit sums over 9 are
     * reduced by 9, and the check digit is whatever makes the total a
     * multiple of ten.
     */
    public static function isLoinc(?string $code): bool
    {
        if ($code === null || ! preg_match('/^(\d{1,7})-(\d)$/', trim($code), $m)) {
            return false;
        }

        return self::loincCheckDigit($m[1]) === (int) $m[2];
    }

    /** The check digit a LOINC number should carry. */
    public static function loincCheckDigit(string $number): int
    {
        $total = 0;

        foreach (array_reverse(str_split($number)) as $i => $char) {
            $digit = (int) $char;

            // Rightmost is position 0 and is doubled.
            if ($i % 2 === 0) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        return (10 - $total % 10) % 10;
    }

    /**
     * ICD-10: a letter, two digits, then optionally a dot and up to four more
     * characters — E11, I10, E11.9, O80 and so on.
     *
     * There is NO check digit in ICD-10, so this is a shape test only. It
     * will happily accept a well-formed code that does not exist, which is
     * exactly why an ICD-10 mapping still needs a human to approve it.
     * U and the letters beyond are allowed: ICD-10 uses U07.1 for COVID-19.
     */
    public static function isIcd10(?string $code): bool
    {
        if ($code === null) {
            return false;
        }

        // A-Z, not A-TV-Z. The excluded-U convention comes from ICD-9 and is
        // wrong here: ICD-10 uses the U chapter for provisional assignments,
        // and U07.1 is COVID-19. The docblock above already said so while the
        // pattern quietly disagreed — the sort of contradiction that only
        // shows up when something real gets rejected.
        return (bool) preg_match('/^[A-Z][0-9][0-9A-Z](\.[0-9A-Z]{1,4})?$/i', trim($code));
    }

    /**
     * SNOMED CT concept ids are 6-18 digits and carry a Verhoeff check digit.
     *
     * The Verhoeff tables are not reproduced here: this returns a shape test
     * only, and says so, rather than pretending to a verification it is not
     * performing. A SNOMED mapping is therefore always a human decision.
     */
    public static function looksLikeSnomed(?string $code): bool
    {
        return $code !== null && (bool) preg_match('/^\d{6,18}$/', trim($code));
    }
}
