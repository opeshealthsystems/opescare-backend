<?php
namespace App\Modules\ClinicalDecisionSupport\Services;

/**
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group C).
 *
 * The interaction pairs below are the platform's reference data VERBATIM —
 * the platform has no interactions table, seeder or import (its
 * drug_interaction_rules table is never written). Nothing is added here: a
 * pair not listed is "not checked", never "safe", and the UI says so.
 *
 * Finder adaptation: prescriptions carry catalogue display names such as
 * "Warfarin 5 mg", so a known drug is matched as a whole word inside the name
 * rather than by exact string equality (which would never fire). The
 * platform's 'nsaids' key is a class name, so it only matches a line that
 * literally says NSAIDs — no class expansion is invented.
 */
class DrugInteractionService
{
    /**
     * Known interaction pairs — keyed as sorted pair "drug_a|drug_b".
     * Production: replace with RxNorm API call or local drug DB.
     */
    private array $knownInteractions = [
        'aspirin|warfarin' => [
            'severity'    => 'major',
            'description' => 'Concurrent use significantly increases bleeding risk.',
            'is_hard_stop'=> false,
        ],
        'clopidogrel|warfarin' => [
            'severity'    => 'major',
            'description' => 'Combined anticoagulation/antiplatelet therapy increases haemorrhage risk.',
            'is_hard_stop'=> false,
        ],
        'clarithromycin|simvastatin' => [
            'severity'    => 'major',
            'description' => 'CYP3A4 inhibition raises simvastatin plasma levels — myopathy risk.',
            'is_hard_stop'=> false,
        ],
        'methotrexate|nsaids' => [
            'severity'    => 'contraindicated',
            'description' => 'NSAIDs reduce methotrexate clearance — severe toxicity risk.',
            'is_hard_stop'=> true,
        ],
    ];

    /**
     * @param  array $medications  Each element: ['name' => string, ...]
     * @return array               Alert records (empty if none found)
     */
    public function checkInteractions(array $medications): array
    {
        // Each medication line → the known drug tokens it names.
        $lines = [];
        foreach ($medications as $m) {
            $name = strtolower(trim((string) ($m['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $lines[] = ['name' => $name, 'drugs' => $this->knownDrugsIn($name)];
        }

        $alerts = [];
        $seen   = [];

        for ($i = 0; $i < count($lines); $i++) {
            for ($j = $i + 1; $j < count($lines); $j++) {
                foreach ($lines[$i]['drugs'] as $a) {
                    foreach ($lines[$j]['drugs'] as $b) {
                        $pair = [$a, $b];
                        sort($pair);
                        $key = implode('|', $pair);
                        if (isset($this->knownInteractions[$key]) && ! isset($seen[$key])) {
                            $seen[$key] = true;
                            $alerts[] = array_merge($this->knownInteractions[$key], [
                                'key'    => $key,
                                'drug_a' => $a,
                                'drug_b' => $b,
                            ]);
                        }
                    }
                }
            }
        }

        return $alerts;
    }

    /** @return list<string> */
    private function knownDrugsIn(string $name): array
    {
        $vocabulary = [];
        foreach (array_keys($this->knownInteractions) as $key) {
            foreach (explode('|', $key) as $drug) {
                $vocabulary[$drug] = true;
            }
        }

        $found = [];
        foreach (array_keys($vocabulary) as $drug) {
            if (preg_match('/\b' . preg_quote($drug, '/') . '\b/u', $name)) {
                $found[] = $drug;
            }
        }

        return $found;
    }
}
