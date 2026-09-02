<?php
namespace App\Modules\ClinicalDecisionSupport\Services;

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
        $names  = array_map(fn($m) => strtolower(trim($m['name'])), $medications);
        $alerts = [];

        for ($i = 0; $i < count($names); $i++) {
            for ($j = $i + 1; $j < count($names); $j++) {
                $pair = [$names[$i], $names[$j]];
                sort($pair);
                $key = implode('|', $pair);
                if (isset($this->knownInteractions[$key])) {
                    $interaction = $this->knownInteractions[$key];
                    $alerts[] = array_merge($interaction, [
                        'drug_a' => $names[$i],
                        'drug_b' => $names[$j],
                    ]);
                }
            }
        }

        return $alerts;
    }
}
