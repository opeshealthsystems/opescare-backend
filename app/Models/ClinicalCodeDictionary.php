<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A published reference vocabulary: WHO ICD-10 and LOINC.
 *
 * READ-ONLY BY INTENT. Nothing in the application writes here — the rows come
 * from `opescare:import-icd10` and `opescare:import-loinc`, verbatim from the
 * publisher, and the only correct way to change one is to import a newer
 * release. Editing a WHO title by hand would make this deployment disagree
 * with every system it exchanges data with, silently.
 *
 * Not to be confused with `CodeSystemMapping`, which is this deployment's own
 * crosswalk from local codes to these ones and IS edited by people.
 */
class ClinicalCodeDictionary extends Model
{
    protected $table = 'clinical_code_dictionary';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_common' => 'boolean',
        'is_active' => 'boolean',
        'imported_at' => 'datetime',
    ];

    /**
     * Search the dictionary by code or by words in the display and synonyms.
     *
     * A leading code match is tried first because a clinician who types
     * "E11" means the code, not every title containing that string.
     *
     * The LIKE here is on PUBLISHED REFERENCE DATA, not on patients. The
     * platform's prohibition on enumeration search exists to stop someone
     * walking the patient register; a public code table has nothing to
     * enumerate that WHO and LOINC do not already publish in full.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Escaped ONCE, then used everywhere. Escaping only the contains
        // pattern and leaving the prefix clause on the raw term is how a
        // search for "%" becomes ILIKE '%%' and quietly returns the whole
        // dictionary as though it had matched.
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
        $like = '%' . $escaped . '%';

        return $query->where(function (Builder $q) use ($escaped, $like) {
            $q->where('code', 'ILIKE', $escaped . '%')
                ->orWhere('display', 'ILIKE', $like)
                // Searched in BOTH languages regardless of the current locale.
                // A francophone clinician types "paludisme" and an anglophone
                // one types "malaria" for the same code, and neither should
                // have to know which language the record was filed in.
                ->orWhere('display_fr', 'ILIKE', $like)
                ->orWhere('synonyms', 'ILIKE', $like);
        });
    }

    /**
     * The title to show, in the reader's language where one exists.
     *
     * Falls back to the published English rather than to nothing: 84 ICD-10
     * codes — the U-chapter emergency-use placeholders — have no French title
     * because CIM-10 FR does not carry them, and an empty row would be worse
     * than an English one.
     */
    public function getLabelAttribute(): string
    {
        if (app()->getLocale() === 'fr' && filled($this->display_fr)) {
            return $this->display_fr;
        }

        return $this->display;
    }

    /** Codes a clinician can actually assign — never a chapter or block heading. */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query->whereIn('kind', ['category', 'term'])->where('is_active', true);
    }

    /**
     * Codes a FACILITY can declare it offers. A different question entirely.
     *
     * `assignable()` answers "may a clinician record this against a patient",
     * and its job is to keep an ICD chapter heading out of a diagnosis field.
     * The facility service catalogue asks something else — "does this hospital
     * do this" — and the two answers are not the same set.
     *
     * Using `assignable()` for the catalogue made every procedure, vaccine,
     * department and specialty INVISIBLE on the one screen built to select
     * them: 8,923 ICHI interventions, 596 HL7 service types, 290 CVX vaccines
     * and 115 specialties, none of them offerable, because none of them is a
     * `category` or a `term`. A hospital could declare it does a haemoglobin
     * assay but not that it performs a caesarean section.
     *
     * What is excluded here is excluded for a reason, not by omission:
     *
     *   chapter, block, group   headings. "ANTIINFECTIVES FOR SYSTEMIC USE" is
     *                           not a service; the drug beneath it is.
     *   unit, route             `mg/dL` and "Intramuscular use" describe HOW a
     *                           thing is measured or given. No facility offers
     *                           a unit.
     *   nonvaccine              CDC ships immune globulins inside CVX; they are
     *                           flagged so they cannot pass as vaccines.
     *
     * Inactive rows are excluded from both scopes: a withdrawn code stays in
     * the table so historical records still resolve, but nothing new may be
     * built on it.
     */
    public function scopeOfferable(Builder $query): Builder
    {
        return $query
            ->whereIn('kind', ['category', 'term', 'procedure', 'service', 'vaccine', 'specialty'])
            ->where('is_active', true);
    }
}
