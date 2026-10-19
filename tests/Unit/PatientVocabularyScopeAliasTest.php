<?php

namespace Tests\Unit;

use App\Support\PatientVocabulary;
use Tests\TestCase;

/**
 * Legacy short scope codes must reach the patient as plain language, not as a
 * raw code with a "not part of our vocabulary" warning.
 */
class PatientVocabularyScopeAliasTest extends TestCase
{
    public function test_legacy_codes_resolve_to_named_scopes_in_both_languages(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $view = PatientVocabulary::scopes(['conditions', 'medications', 'vitals', 'lab_results'], $locale);

            $this->assertNull($view['note'], "No 'unknown code' note in {$locale}");
            foreach ($view['items'] as $item) {
                $this->assertTrue($item['recognised'], "{$item['code']} recognised in {$locale}");
                $this->assertNotSame($item['code'], $item['label']);
            }
        }
    }

    public function test_truly_unknown_codes_stay_flagged(): void
    {
        $view = PatientVocabulary::scopes(['genome_full'], 'en');

        $this->assertFalse($view['items'][0]['recognised']);
        $this->assertNotNull($view['note']);
    }
}
