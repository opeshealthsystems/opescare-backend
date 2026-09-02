<?php
namespace App\Services\Pharmacy;

use App\Models\DrugFormulary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FormularyService {
    public function search(string $query, ?string $facilityId, array $filters = []): Collection {
        $driver = DB::connection()->getDriverName();
        $like   = '%' . mb_strtolower($query) . '%';

        $q = DrugFormulary::where(function ($builder) use ($query, $like, $driver) {
            $builder->whereRaw('LOWER(generic_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(drug_code) LIKE ?', [$like]);

            if ($driver === 'pgsql') {
                $builder->orWhereRaw(
                    "EXISTS (SELECT 1 FROM jsonb_array_elements_text(brand_names::jsonb) AS bn WHERE LOWER(bn) LIKE ?)",
                    [$like]
                );
            } else {
                // SQLite: brand_names is stored as JSON string — search it as text
                $builder->orWhereRaw('LOWER(brand_names) LIKE ?', [$like]);
            }
        });

        $q->where(function ($builder) use ($facilityId) {
            $builder->whereNull('facility_id');
            if ($facilityId) {
                $builder->orWhere('facility_id', $facilityId);
            }
        });

        if (isset($filters['is_controlled'])) {
            $q->where('is_controlled', $filters['is_controlled']);
        }
        if (isset($filters['is_available'])) {
            $q->where('is_available', $filters['is_available']);
        }
        if (!empty($filters['drug_class'])) {
            $q->where('drug_class', $filters['drug_class']);
        }
        if (!empty($filters['form'])) {
            $q->where('form', $filters['form']);
        }

        return $q->orderByRaw('facility_id IS NULL')->orderBy('generic_name')->get();
    }

    public function isOnFormulary(string $drugCode, ?string $facilityId): bool {
        return DrugFormulary::where('drug_code', $drugCode)
            ->where('is_available', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) { $q->orWhere('facility_id', $facilityId); }
            })
            ->exists();
    }

    public function add(array $data): DrugFormulary {
        return DrugFormulary::create($data);
    }

    public function toggleAvailability(string $id, bool $available): DrugFormulary {
        $entry = DrugFormulary::findOrFail($id);
        $entry->update(['is_available' => $available]);
        return $entry->fresh();
    }

    public function getControlledSubstances(?string $facilityId): Collection {
        return DrugFormulary::where('is_controlled', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) { $q->orWhere('facility_id', $facilityId); }
            })
            ->orderBy('generic_name')
            ->get();
    }

    public function getRestrictedDrugs(string $specialty, ?string $facilityId): Collection {
        $driver = DB::connection()->getDriverName();

        $q = DrugFormulary::whereNotNull('restricted_to');

        if ($driver === 'pgsql') {
            $q->whereRaw("restricted_to::jsonb @> ?::jsonb", [json_encode([$specialty])]);
        } else {
            // SQLite: search the JSON array string for the specialty value
            $q->whereRaw('LOWER(restricted_to) LIKE ?', ['%"' . mb_strtolower($specialty) . '"%']);
        }

        $q->where(function ($builder) use ($facilityId) {
            $builder->whereNull('facility_id');
            if ($facilityId) { $builder->orWhere('facility_id', $facilityId); }
        });

        return $q->orderBy('generic_name')->get();
    }
}
