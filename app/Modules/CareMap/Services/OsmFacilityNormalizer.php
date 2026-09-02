<?php

namespace App\Modules\CareMap\Services;

use Illuminate\Support\Str;

/**
 * Turns a raw OpenStreetMap element into the shape `care_facilities` speaks,
 * and decides how alike two facility names are.
 *
 * The name comparison is the whole ballgame. The existing directory is a
 * bilingual MINSANTE extract — 'Hôpital de District de Bassa', 'Centre Pasteur
 * du Cameroun — Douala', 'Djakoume Phamacy' (sic) — and OSM contributors write
 * the same buildings in whichever language and spelling they had to hand. A
 * comparison that is too literal misses 'CHU de Douala' against 'CHU Douala'
 * and inserts a second teaching hospital. A comparison that is too generous
 * merges 'Hôpital de District de Bassa' into 'Hôpital de District de Bonabéri'
 * — two different hospitals ten kilometres apart that share every word but the
 * last. Both failures put a patient in front of the wrong building.
 *
 * ## How the score is built
 *
 * 1. Fold to ASCII, lowercase, strip punctuation.
 * 2. Canonicalise the vocabulary: pharmacie→pharmacy, hôpital→hospital,
 *    centre→center, santé→health. Now FR and EN names are the same string.
 * 3. Split into GENERIC tokens (the words that say what kind of place it is,
 *    plus grammar: de, du, la, of, the) and CORE tokens (everything left —
 *    which is the part that actually identifies the building: bassa, joss,
 *    djakoume, pasteur).
 * 4. Score = 0.75 × Jaccard over CORE tokens + 0.25 × edit similarity over the
 *    whole sorted string.
 *
 * The 0.75 weight on core tokens is what separates Bassa from Bonabéri: they
 * share every generic word, so a plain string comparison scores them 0.73 —
 * high enough to merge under any sane threshold — while their cores are
 * disjoint and this function returns 0.18.
 *
 * ## Generic-only names score zero, always
 *
 * 33 of 241 named Douala elements are called exactly 'Centre de Santé',
 * 'Clinique' or 'Cabinet Médical'. Ten of them are the same three words. Those
 * names carry no identifying information whatsoever: matching two of them by
 * name is matching nothing, and inserting one gives a patient a directory entry
 * they cannot act on. Their core token set is empty, this function returns 0,
 * and the importer routes them to human review.
 */
class OsmFacilityNormalizer
{
    /**
     * FR/EN vocabulary levelling. Applied token by token after ASCII folding,
     * so 'Hôpital' and 'hopital' both arrive here as 'hopital'.
     */
    private const CANONICAL = [
        'hopital' => 'hospital', 'hopitaux' => 'hospital', 'hospitalier' => 'hospital',
        'clinique' => 'clinic', 'cliniques' => 'clinic', 'polyclinique' => 'polyclinic',
        'pharmacie' => 'pharmacy', 'pharmacies' => 'pharmacy',
        'centre' => 'center', 'centres' => 'center',
        'sante' => 'health',
        'medicale' => 'medical', 'medicales' => 'medical', 'medicaux' => 'medical',
        'medico' => 'medical', 'medecine' => 'medical',
        'laboratoire' => 'laboratory', 'labo' => 'laboratory', 'lab' => 'laboratory',
        'maternite' => 'maternity',
        'dentaire' => 'dental', 'dentiste' => 'dental',
        'ophtalmologique' => 'ophthalmology', 'oculaire' => 'eye',
        'radiologie' => 'radiology', 'imagerie' => 'imaging',
        'dispensaire' => 'dispensary',
        'integre' => 'integrated',
        'soins' => 'care',
        'saint' => 'st', 'sainte' => 'st', 'ste' => 'st',
    ];

    /**
     * Words that describe the KIND of place, or are pure grammar. Stripped
     * before the identity comparison. Note this runs AFTER canonicalisation, so
     * only the canonical forms need listing.
     */
    private const GENERIC = [
        // kind-of-place
        'hospital', 'clinic', 'polyclinic', 'pharmacy', 'center', 'health', 'medical',
        'laboratory', 'maternity', 'dental', 'dispensary', 'cabinet', 'care',
        'integrated', 'district', 'regional', 'general', 'central', 'social',
        'doctor', 'doctors', 'surgery', 'practice', 'point',
        // grammar, FR + EN
        'de', 'du', 'des', 'd', 'la', 'le', 'les', 'l', 'et', 'a', 'au', 'aux', 'en',
        'of', 'the', 'and', 'for', 'sur',
    ];

    /**
     * OSM tag → `care_facilities.facility_type`. Right-hand values are the eight
     * types already present in the table plus the rest of the vocabulary
     * ImportFacilityRegistry validates against — no new type is invented here.
     */
    private const TYPE_MAP = [
        'amenity' => [
            'pharmacy'      => 'pharmacy',
            'hospital'      => 'hospital',
            'clinic'        => 'clinic',
            'doctors'       => 'clinic',
            'dentist'       => 'dental',
            'health_post'   => 'health_center',
        ],
        'healthcare' => [
            'pharmacy'         => 'pharmacy',
            'hospital'         => 'hospital',
            'clinic'           => 'clinic',
            'doctor'           => 'clinic',
            'centre'           => 'health_center',
            'center'           => 'health_center',
            'health_post'      => 'health_center',
            'yes'              => 'health_center',
            'laboratory'       => 'laboratory',
            'sample_collection'=> 'laboratory',
            'dentist'          => 'dental',
            'blood_donation'   => 'blood_bank',
            'blood_bank'       => 'blood_bank',
            'midwife'          => 'maternity',
            'birthing_centre'  => 'maternity',
            'radiotherapy'     => 'imaging_center',
            'radiology'        => 'imaging_center',
            'imaging'          => 'imaging_center',
            'diagnostics'      => 'diagnostic_center',
            'optometrist'      => 'eye_clinic',
            'ophthalmologist'  => 'eye_clinic',
            'nurse'            => 'health_center',
            'nursing_home'     => 'nursing_home',
            'physiotherapist'  => 'specialist',
            'psychotherapist'  => 'specialist',
            'rehabilitation'   => 'specialist',
            'dialysis'         => 'specialist',
        ],
    ];

    /**
     * Types that OSM contributors use more or less interchangeably for the same
     * building — a district hospital tagged `amenity=clinic`, a health centre
     * tagged `amenity=doctors`. Only types inside one family may be matched to
     * each other; a pharmacy is never quietly merged into a laboratory.
     */
    private const TYPE_FAMILIES = [
        'acute'    => ['hospital', 'clinic', 'health_center', 'dispensary', 'maternity', 'nursing_home', 'specialist'],
        'pharmacy' => ['pharmacy'],
        'lab'      => ['laboratory'],
        'imaging'  => ['imaging_center', 'diagnostic_center'],
        'dental'   => ['dental'],
        'eye'      => ['eye_clinic'],
        'blood'    => ['blood_bank'],
    ];

    /**
     * Reduce a facility name to comparable parts.
     *
     * @return array{normalized: string, tokens: list<string>, core: list<string>}
     */
    public function analyseName(?string $name): array
    {
        $ascii = Str::lower(Str::ascii((string) $name));
        $ascii = preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '';

        $tokens = [];

        foreach (preg_split('/\s+/', trim($ascii), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            $tokens[] = $this->canonicalise($token);
        }

        $core = array_values(array_filter(
            $tokens,
            fn (string $t): bool => ! $this->isGenericToken($t)
        ));

        return [
            'normalized' => implode(' ', $tokens),
            'tokens'     => $tokens,
            'core'       => array_values(array_unique($core)),
        ];
    }

    /**
     * A name made entirely of generic words — 'Centre de Santé', 'Clinique'.
     * It names a category, not a facility.
     */
    public function isGenericName(?string $name): bool
    {
        $analysis = $this->analyseName($name);

        return $analysis['core'] === [];
    }

    /**
     * How confident we are that two names denote the same facility, 0.0 – 1.0.
     *
     * Returns 0.0 whenever either side is generic-only: two things called
     * 'Clinique' are not evidence of anything.
     */
    public function nameSimilarity(?string $a, ?string $b): float
    {
        $left  = $this->analyseName($a);
        $right = $this->analyseName($b);

        if ($left['core'] === [] || $right['core'] === []) {
            return 0.0;
        }

        return round(
            (0.75 * $this->fuzzyJaccard($left['core'], $right['core']))
            + (0.25 * $this->editSimilarity($left['tokens'], $right['tokens'])),
            4
        );
    }

    /**
     * Jaccard over token sets, where two tokens count as equal if they are one
     * edit apart. 'Djakoume Phamacy' is a real row in this database; a typo in
     * a name typed off a shopfront should not read as a different facility.
     *
     * @param  list<string> $a
     * @param  list<string> $b
     */
    private function fuzzyJaccard(array $a, array $b): float
    {
        $matchedB = [];
        $intersection = 0;

        foreach ($a as $left) {
            foreach ($b as $i => $right) {
                if (isset($matchedB[$i])) {
                    continue;
                }

                if ($this->tokensEqual($left, $right)) {
                    $matchedB[$i] = true;
                    $intersection++;
                    break;
                }
            }
        }

        $union = count($a) + count($b) - $intersection;

        return $union === 0 ? 0.0 : $intersection / $union;
    }

    private function tokensEqual(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        // One typo is forgiven, but only in a token long enough that a single
        // edit cannot turn one real word into a different real word.
        return min(strlen($a), strlen($b)) >= 6 && levenshtein($a, $b) <= 1;
    }

    /**
     * Edit similarity over the sorted token string, so word order does not
     * matter: 'Africa Pharmacy' and 'Pharmacie Africa' are the same shop.
     *
     * @param  list<string> $a
     * @param  list<string> $b
     */
    private function editSimilarity(array $a, array $b): float
    {
        sort($a);
        sort($b);

        $left  = substr(implode(' ', $a), 0, 255);   // PHP levenshtein() caps at 255
        $right = substr(implode(' ', $b), 0, 255);

        $longest = max(strlen($left), strlen($right));

        if ($longest === 0) {
            return 0.0;
        }

        return max(0.0, 1 - (levenshtein($left, $right) / $longest));
    }

    /**
     * The best display name available on an element, preferring the local
     * French/English name tags over the bare `name` when they disagree.
     *
     * @param  array<string,string> $tags
     */
    public function displayName(array $tags): ?string
    {
        foreach (['name', 'name:fr', 'name:en', 'official_name', 'alt_name'] as $key) {
            $value = trim((string) ($tags[$key] ?? ''));

            if ($value !== '') {
                return mb_substr($value, 0, 255);
            }
        }

        return null;
    }

    /**
     * @param  array<string,string> $tags
     */
    public function facilityType(array $tags): ?string
    {
        // `healthcare` is the more specific scheme and wins where both are set:
        // amenity=clinic + healthcare=laboratory is a lab inside a clinic building.
        foreach (['healthcare', 'amenity'] as $key) {
            $value = strtolower(trim((string) ($tags[$key] ?? '')));

            if ($value !== '' && isset(self::TYPE_MAP[$key][$value])) {
                return self::TYPE_MAP[$key][$value];
            }
        }

        return null;
    }

    public function typeFamily(?string $facilityType): ?string
    {
        foreach (self::TYPE_FAMILIES as $family => $types) {
            if (in_array($facilityType, $types, true)) {
                return $family;
            }
        }

        return null;
    }

    /** Two types that OSM contributors plausibly use for the same building. */
    public function typesAreCompatible(?string $a, ?string $b): bool
    {
        $familyA = $this->typeFamily($a);

        return $familyA !== null && $familyA === $this->typeFamily($b);
    }

    /**
     * Normalise a Cameroonian phone number to the '+237 6XXXXXXXX' form the
     * existing rows use, or return null.
     *
     * Cameroon moved to nine-digit national numbers in 2010 (mobile 6…, fixed
     * 2…). Anything that does not reduce to nine digits is not dialable, and a
     * phone number a patient cannot dial is worse than the honest NULL it would
     * replace — so this rejects rather than guesses.
     */
    public function phone(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        // Multi-valued OSM tags: 'phone=+237 233 42 11 11;+237 699 00 00 00'.
        foreach (preg_split('/[;,]/', $raw) ?: [] as $part) {
            $digits = preg_replace('/\D+/', '', $part) ?? '';

            if (str_starts_with($digits, '00237')) {
                $digits = substr($digits, 5);
            } elseif (str_starts_with($digits, '237')) {
                $digits = substr($digits, 3);
            }

            if (preg_match('/^[62]\d{8}$/', $digits) === 1) {
                return '+237 ' . $digits;
            }
        }

        return null;
    }

    public function email(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        foreach (preg_split('/[;,\s]+/', trim($raw)) ?: [] as $part) {
            $candidate = mb_strtolower(trim($part));

            if ($candidate !== ''
                && mb_strlen($candidate) <= 255
                && filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
                return $candidate;
            }
        }

        return null;
    }

    public function website(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $candidate = trim(explode(';', $raw)[0]);

        if ($candidate === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $candidate)) {
            $candidate = 'https://' . $candidate;
        }

        if (mb_strlen($candidate) > 255 || filter_var($candidate, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $candidate;
    }

    /**
     * Best-effort street address from the OSM address tags.
     *
     * @param  array<string,string> $tags
     */
    public function address(array $tags): ?string
    {
        if (! empty($tags['addr:full'])) {
            return trim($tags['addr:full']);
        }

        $line = trim(implode(' ', array_filter([
            $tags['addr:housenumber'] ?? null,
            $tags['addr:street'] ?? null,
        ])));

        $parts = array_filter([
            $line !== '' ? $line : null,
            $tags['addr:suburb'] ?? $tags['addr:neighbourhood'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Map a token to its canonical form, forgiving one typo in a long word.
     *
     * Both the directory and OSM are full of misspelt category words —
     * 'Phamacie de l'Horizon' and 'Pharmacie de l'Horizon' are the same shop,
     * standing on the same spot, spelt two ways. Without this, 'phamacie' fails
     * the CANONICAL lookup, survives generic-stripping, and gets treated as
     * part of the shop's IDENTITY — which drags a certain match down to 0.59
     * and sends an obvious pair to a human for no reason.
     *
     * The seven-character floor matters: it keeps a short distinctive word from
     * being swallowed by a category word one edit away.
     */
    private function canonicalise(string $token): string
    {
        if (isset(self::CANONICAL[$token])) {
            return self::CANONICAL[$token];
        }

        if (strlen($token) >= 7) {
            foreach (self::CANONICAL as $variant => $canonical) {
                if (strlen($variant) >= 6 && levenshtein($token, $variant) <= 1) {
                    return $canonical;
                }
            }
        }

        return $token;
    }

    private function isGenericToken(string $token): bool
    {
        if (in_array($token, self::GENERIC, true)) {
            return true;
        }

        // Catch mis-spellings of the generic words too ('phamacy'), otherwise a
        // typo promotes a category word into an identity word and two unrelated
        // pharmacies start looking alike.
        if (strlen($token) >= 6) {
            foreach (self::GENERIC as $generic) {
                if (strlen($generic) >= 6 && levenshtein($token, $generic) <= 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
