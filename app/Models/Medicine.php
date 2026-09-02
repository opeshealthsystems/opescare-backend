<?php

namespace App\Models;

use App\Enums\MedicineCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canonical medicine catalog entry — the thing a patient searches for in the
 * Medicine Finder. Availability and price live on MedicinePharmacyStock, one
 * row per pharmacy listing.
 */
class Medicine extends Model
{
    use HasUuids;

    protected $table = 'medicines';

    protected $fillable = [
        'name',
        'generic_name',
        'brand_name',
        'strength',
        'form',
        'category',
        'atc_code',
        'description',
        'indications',
        'prescription_required',
        'is_controlled',
        'default_pack_size',
        'pack_size_options',
        'price_min',
        'price_max',
        'currency',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'category'              => MedicineCategory::class,
        'indications'           => 'array',
        'pack_size_options'     => 'array',
        'prescription_required' => 'boolean',
        'is_controlled'         => 'boolean',
        'is_active'             => 'boolean',
        'price_min'             => 'float',
        'price_max'             => 'float',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(MedicinePharmacyStock::class, 'medicine_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(MedicineReservation::class, 'medicine_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Case-insensitive catalog search across name / generic / brand.
     *
     * Substring matching on a *product catalog* is intentional and carries none
     * of the patient-enumeration risk the LIKE prohibition guards against — no
     * row in this table is personal data.
     */
    public function scopeMatchingTerm(Builder $query, string $term): Builder
    {
        $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($term)) . '%';

        return $query->where(function (Builder $sub) use ($needle) {
            $sub->whereRaw('LOWER(name) LIKE LOWER(?)', [$needle])
                ->orWhereRaw('LOWER(generic_name) LIKE LOWER(?)', [$needle])
                ->orWhereRaw('LOWER(COALESCE(brand_name, \'\')) LIKE LOWER(?)', [$needle]);
        });
    }
}
