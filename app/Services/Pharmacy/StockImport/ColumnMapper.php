<?php

namespace App\Services\Pharmacy\StockImport;

use Illuminate\Support\Str;

/**
 * Guesses which spreadsheet column holds which stock field, from its header,
 * in English or French, ignoring case, accents and punctuation
 * ("Désignation", "Qté", "Prix", "Péremption", "DCI" …).
 *
 * The guess is only a starting point: the pharmacist confirms or changes it
 * on the mapping screen before anything is validated.
 */
class ColumnMapper
{
    /** The fields a stock line can carry, in template order. */
    public const FIELDS = [
        'medicine', 'inn', 'strength', 'form', 'pack', 'quantity',
        'unit_price', 'unit_cost', 'expiry', 'batch', 'barcode', 'atc',
    ];

    public const REQUIRED = ['medicine', 'quantity'];

    /** Normalised synonyms per field. Longer, more specific phrases win over shorter ones. */
    private const SYNONYMS = [
        'medicine'   => ['medicine', 'medicine name', 'medicament', 'nom du medicament', 'designation', 'produit', 'product', 'product name',
                         'nom', 'name', 'libelle', 'article', 'item', 'drug', 'specialite', 'nom commercial', 'brand', 'description'],
        'inn'        => ['dci', 'inn', 'generic', 'generic name', 'generique', 'molecule', 'principe actif', 'active ingredient', 'denomination commune'],
        'strength'   => ['strength', 'dosage', 'dose', 'force', 'concentration', 'teneur'],
        'form'       => ['form', 'forme', 'forme galenique', 'dosage form', 'galenique'],
        'pack'       => ['pack', 'pack size', 'conditionnement', 'presentation', 'colisage', 'packaging', 'unite', 'unit', 'taille'],
        'quantity'   => ['quantity', 'qty', 'qte', 'quantite', 'stock', 'qte stock', 'quantite en stock', 'en stock', 'stock disponible',
                         'packs', 'boites', 'nombre', 'nb', 'quantity in stock', 'on hand', 'stock qty', 'quantite disponible'],
        'unit_price' => ['price', 'prix', 'prix de vente', 'pv', 'selling price', 'prix unitaire', 'unit price', 'ppv', 'prix public',
                         'sale price', 'prix vente', 'tarif', 'unit price xaf', 'prix fcfa', 'prix xaf'],
        'unit_cost'  => ['cost', 'cout', 'prix d achat', 'prix achat', 'pa', 'purchase price', 'unit cost', 'cout unitaire', 'buying price', 'pght'],
        'expiry'     => ['expiry', 'expiry date', 'exp', 'exp date', 'peremption', 'date de peremption', 'date peremption', 'dlu', 'dluo',
                         'expiration', 'date d expiration', 'use by', 'date exp', 'expires', 'expire le'],
        'batch'      => ['batch', 'lot', 'n lot', 'no lot', 'numero de lot', 'num lot', 'batch number', 'lot number', 'batch no', 'lot no', 'n de lot'],
        'barcode'    => ['barcode', 'code barre', 'code barres', 'ean', 'gtin', 'cip', 'ean13', 'code ean'],
        'atc'        => ['atc', 'code atc', 'atc code', 'classe atc'],
    ];

    public static function normalise(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', (string) $text));
    }

    /**
     * Score how well a header names a field (0 = not at all).
     */
    private function score(string $header, string $field): int
    {
        $h = self::normalise($header);
        if ($h === '') {
            return 0;
        }
        $best = 0;
        foreach (self::SYNONYMS[$field] as $syn) {
            if ($h === $syn) {
                $best = max($best, 1000 + strlen($syn));
            } elseif (preg_match('/(^| )' . preg_quote($syn, '/') . '( |$)/', $h)) {
                $best = max($best, 100 + strlen($syn) * 2);
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int|null> field => column index
     */
    public function guess(array $headers): array
    {
        $pairs = [];
        foreach ($headers as $col => $header) {
            foreach (self::FIELDS as $field) {
                $s = $this->score($header, $field);
                if ($s > 0) {
                    $pairs[] = [$s, $col, $field];
                }
            }
        }
        usort($pairs, fn ($a, $b) => $b[0] <=> $a[0] ?: $a[1] <=> $b[1]);

        $mapping  = array_fill_keys(self::FIELDS, null);
        $usedCols = [];
        foreach ($pairs as [, $col, $field]) {
            if ($mapping[$field] === null && ! isset($usedCols[$col])) {
                $mapping[$field] = $col;
                $usedCols[$col]  = true;
            }
        }

        // No "name" column but a DCI one: the DCI is the name to match on.
        if ($mapping['medicine'] === null && $mapping['inn'] !== null) {
            $mapping['medicine'] = $mapping['inn'];
            $mapping['inn']      = null;
        }

        return $mapping;
    }

    /** How many fields the row names — used to find the header row under a title block. */
    public function recognised(array $row): int
    {
        return count(array_filter($this->guess($row), fn ($c) => $c !== null));
    }

    /**
     * The header row: the first of the opening rows that names a medicine and a
     * quantity column (or, failing that, the most fields); else the first non-empty row.
     *
     * @param  list<list<string>>  $rows
     */
    public function headerRow(array $rows): int
    {
        $best = null;
        $bestCount = 0;
        foreach (array_slice($rows, 0, 20, true) as $i => $row) {
            $map = $this->guess($row);
            $count = count(array_filter($map, fn ($c) => $c !== null));
            if ($map['medicine'] !== null && $map['quantity'] !== null) {
                return $i;
            }
            if ($count > $bestCount) {
                $best = $i;
                $bestCount = $count;
            }
        }
        if ($best !== null && $bestCount >= 2) {
            return $best;
        }
        foreach ($rows as $i => $row) {
            if (implode('', $row) !== '') {
                return $i;
            }
        }

        return 0;
    }
}
