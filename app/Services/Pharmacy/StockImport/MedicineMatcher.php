<?php

namespace App\Services\Pharmacy\StockImport;

use App\Models\Medicine;

/**
 * Finds the catalogue medicine a spreadsheet line names — or says it cannot.
 *
 *   exact       the name is the catalogue name (case-insensitive)          100
 *   normalised  equal once accents, spacing, "500 mg"/"500mg" and French
 *               form words (comprimé → tablet, gélule → capsule …) are
 *               folded, or the same words in another order                 90–95
 *   fuzzy       a close spelling (≥ 85 % similar) with EXACTLY the same
 *               numbers — 250 mg never fuzzes into 500 mg                  85–94
 *
 * A line that matches nothing, or matches two medicines equally well, is
 * UNMATCHED and is not imported. A medicine is never created from a file.
 */
class MedicineMatcher
{
    public const FUZZY_THRESHOLD = 85.0;

    private const FORM_WORDS = [
        'comprime' => 'tablet', 'comprimes' => 'tablet', 'cp' => 'tablet', 'cpr' => 'tablet', 'cpm' => 'tablet', 'tab' => 'tablet', 'tabs' => 'tablet', 'tablets' => 'tablet',
        'gelule' => 'capsule', 'gelules' => 'capsule', 'gel' => 'capsule', 'caps' => 'capsule', 'cap' => 'capsule', 'capsules' => 'capsule',
        'sirop' => 'syrup', 'inj' => 'injection', 'injectable' => 'injection', 'pommade' => 'ointment', 'creme' => 'cream',
        'suppositoire' => 'suppository', 'suppositoires' => 'suppository', 'collyre' => 'eye drops', 'gouttes' => 'drops',
        'buvable' => 'oral', 'poudre' => 'powder', 'flacon' => 'bottle', 'sachets' => 'sachet', 'ovule' => 'pessary', 'ovules' => 'pessary',
    ];

    /** @var array<string, array{id: string, name: string, atc: ?string, numbers: string, tokens: string}> */
    private array $medicines = [];

    /** @var array<string, list<string>> normalised key => medicine ids */
    private array $keys = [];

    /** @var array<string, string> lowercase name => id */
    private array $exact = [];

    public function __construct(?iterable $catalogue = null)
    {
        $catalogue ??= Medicine::query()->active()->get(['id', 'name', 'generic_name', 'brand_name', 'strength', 'form', 'atc_code']);

        foreach ($catalogue as $m) {
            $this->medicines[$m->id] = [
                'id'      => $m->id,
                'name'    => $m->name,
                'atc'     => $m->atc_code ? strtoupper($m->atc_code) : null,
                'numbers' => self::numbers(self::norm($m->name . ' ' . $m->strength)),
                'tokens'  => self::tokenSet(self::norm($m->name)),
            ];
            $this->exact[mb_strtolower(trim($m->name))] = $m->id;

            foreach (array_filter([
                $m->name,
                trim("{$m->generic_name} {$m->strength} {$m->form}"),
                $m->brand_name ? trim("{$m->brand_name} {$m->strength} {$m->form}") : null,
            ]) as $text) {
                $this->addKey(self::norm($text), $m->id);
                $this->addKey(self::tokenSet(self::norm($text)), $m->id);
            }
            // generic + strength alone is a key too, but only resolves if unique.
            if ($m->generic_name && $m->strength) {
                $this->addKey(self::norm("{$m->generic_name} {$m->strength}"), $m->id);
            }
        }
    }

    private function addKey(string $key, string $id): void
    {
        if ($key === '') {
            return;
        }
        $this->keys[$key] ??= [];
        if (! in_array($id, $this->keys[$key], true)) {
            $this->keys[$key][] = $id;
        }
    }

    public static function norm(string $text): string
    {
        $t = ColumnMapper::normalise($text);
        $t = preg_replace('/(\d) (?=(mg|g|mcg|ug|ml|iu|ui|%)\b)/', '$1', $t);   // 500 mg → 500mg
        $t = preg_replace('/\bui\b/', 'iu', $t);
        $words = array_map(fn ($w) => self::FORM_WORDS[$w] ?? $w, explode(' ', (string) $t));

        return trim(implode(' ', $words));
    }

    private static function tokenSet(string $norm): string
    {
        $tokens = array_unique(array_filter(explode(' ', $norm)));
        sort($tokens);

        return implode(' ', $tokens);
    }

    private static function numbers(string $norm): string
    {
        preg_match_all('/\d+(?:\.\d+)?/', $norm, $m);
        $n = array_values(array_unique($m[0]));
        sort($n);

        return implode(',', $n);
    }

    /**
     * @param  array{medicine?: string, inn?: string, strength?: string, form?: string, atc?: string}  $line
     * @return array{medicine_id: ?string, name: ?string, method: string, confidence: int}
     *         method: exact|normalised|fuzzy|ambiguous|unmatched
     */
    public function match(array $line): array
    {
        $name     = trim((string) ($line['medicine'] ?? ''));
        $inn      = trim((string) ($line['inn'] ?? ''));
        $strength = trim((string) ($line['strength'] ?? ''));
        $form     = trim((string) ($line['form'] ?? ''));
        $atc      = strtoupper(trim((string) ($line['atc'] ?? '')));

        if ($name === '' && $inn === '') {
            return $this->none('unmatched');
        }

        if (($id = $this->exact[mb_strtolower($name)] ?? null) !== null) {
            return $this->hit($id, 'exact', 100);
        }

        $candidates = array_values(array_unique(array_filter([
            self::norm("{$name} {$strength} {$form}"),
            self::norm("{$name} {$strength}"),
            self::norm($name),
            $inn !== '' ? self::norm("{$inn} {$strength} {$form}") : '',
            $inn !== '' ? self::norm("{$inn} {$strength}") : '',
        ])));

        $ambiguous = false;
        foreach ([95 => fn ($c) => $c, 90 => fn ($c) => self::tokenSet($c)] as $confidence => $keyOf) {
            foreach ($candidates as $c) {
                $ids = $this->narrow($this->keys[$keyOf($c)] ?? [], $atc);
                if (count($ids) === 1) {
                    return $this->hit($ids[0], 'normalised', $confidence);
                }
                if (count($ids) > 1) {
                    $ambiguous = true;
                }
            }
        }
        if ($ambiguous) {
            return $this->none('ambiguous');
        }

        // Fuzzy: same numbers, close spelling, a clear winner.
        $scores = [];
        foreach ($candidates as $c) {
            $nums = self::numbers($c);
            foreach ($this->medicines as $id => $m) {
                if ($m['numbers'] !== $nums || ($atc !== '' && $m['atc'] !== null && $m['atc'] !== $atc)) {
                    continue;
                }
                similar_text(self::tokenSet($c), $m['tokens'], $pct);
                if ($pct >= self::FUZZY_THRESHOLD && $pct > ($scores[$id] ?? 0)) {
                    $scores[$id] = $pct;
                }
            }
        }
        if ($scores === []) {
            return $this->none('unmatched');
        }
        arsort($scores);
        $ids = array_keys($scores);
        $top = $scores[$ids[0]];
        if (isset($ids[1]) && $top - $scores[$ids[1]] < 3) {
            return $this->none('ambiguous');
        }

        return $this->hit($ids[0], 'fuzzy', (int) min(94, floor($top)));
    }

    /** @param list<string> $ids @return list<string> */
    private function narrow(array $ids, string $atc): array
    {
        if (count($ids) > 1 && $atc !== '') {
            $byAtc = array_values(array_filter($ids, fn ($id) => $this->medicines[$id]['atc'] === $atc));
            if ($byAtc !== []) {
                return $byAtc;
            }
        }

        return $ids;
    }

    private function hit(string $id, string $method, int $confidence): array
    {
        return ['medicine_id' => $id, 'name' => $this->medicines[$id]['name'], 'method' => $method, 'confidence' => $confidence];
    }

    private function none(string $method): array
    {
        return ['medicine_id' => null, 'name' => null, 'method' => $method, 'confidence' => 0];
    }
}
