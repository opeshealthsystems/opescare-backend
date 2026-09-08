<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One service, test or procedure a facility says it offers.
 *
 * Half of this row is quoted from `clinical_code_dictionary` — the system and
 * the code — and half of it is the facility's own: what they call it, what
 * they charge in whole francs, how long it takes. The quoted half is never
 * copied here: the published display is read through the `dictionaryEntry`
 * relation so that a corrected WHO title corrects every catalogue in the
 * country at once, instead of leaving 900 stale copies behind.
 *
 * The relation is a two-column match, not a foreign key, because the catalogue
 * has to survive a dictionary re-import — see the migration for the full
 * reasoning. The practical consequence is handled here rather than hidden: if
 * a code is not in the dictionary (never imported, or withdrawn by its
 * publisher) `dictionaryEntry` is null and the screen must say so. It must not
 * quietly drop the row, because the facility did offer it.
 */
class FacilityCatalogueItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'facility_id', 'system', 'code', 'local_name', 'price_xaf',
        'turnaround', 'notes', 'is_active', 'added_by',
        'deactivated_at', 'deactivated_by',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        // XAF is a zero-decimal currency. Cast to integer, never to a float or
        // a decimal string: 1500 FCFA is 1500 francs and has no fractional part
        // to lose or to invent.
        'price_xaf'      => 'integer',
        'deactivated_at' => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Resolve the published dictionary rows for a set of catalogue items.
     *
     * Deliberately NOT an Eloquent relation. A belongsTo cannot express a
     * two-column match, and the obvious cheat — a belongsTo on `code` with an
     * added `where(system)` — is broken precisely where it matters: Laravel
     * builds relations on a fresh model instance when eager loading, so
     * `$this->system` is null there and the constraint silently matches the
     * wrong system. A LOINC catalogue would start displaying ICD-10 titles.
     *
     * One query for a whole page, keyed "SYSTEM|CODE".
     *
     * @param  iterable<int, self>  $items
     * @return array<string, ClinicalCodeDictionary>
     */
    public static function dictionaryFor(iterable $items): array
    {
        $wanted = [];
        foreach ($items as $item) {
            $wanted[$item->system][$item->code] = true;
        }

        if ($wanted === []) {
            return [];
        }

        $rows = ClinicalCodeDictionary::query()
            ->where(function ($q) use ($wanted) {
                foreach ($wanted as $system => $codes) {
                    $q->orWhere(fn ($w) => $w->where('system', $system)->whereIn('code', array_keys($codes)));
                }
            })
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->system . '|' . $row->code] = $row;
        }

        return $map;
    }

    /** The key this item takes in the map returned by dictionaryFor(). */
    public function dictionaryKey(): string
    {
        return $this->system . '|' . $this->code;
    }

    /** Live offerings only. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The name to show: the facility's own, falling back to the published
     * display in the reader's language, falling back to the bare code.
     *
     * The bare code is the honest last resort. It means "this facility offers
     * the thing this code names and we cannot currently resolve the name",
     * which is true and checkable, unlike a blank cell.
     */
    public function displayName(?ClinicalCodeDictionary $entry = null): string
    {
        if (filled($this->local_name)) {
            return $this->local_name;
        }

        return $entry?->label ?? $this->code;
    }
}
