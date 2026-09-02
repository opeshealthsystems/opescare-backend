<?php

namespace App\Modules\CareMap\Services;

use App\Models\PharmacyStockAvailability;
use App\Models\BloodAvailability;
use App\Models\LabTestAvailability;
use App\Models\CareFacility;

class FacilityFreshnessService
{
    /**
     * Evaluate freshness for a facility's pharmacy stock records
     */
    public function checkPharmacyStockFreshness($facilityId)
    {
        $records = PharmacyStockAvailability::where('facility_id', $facilityId)->get();
        
        foreach ($records as $record) {
            if (!$record->last_updated_at) {
                $record->update(['freshness_status' => 'stale']);
                continue;
            }

            // Arguments in this order, and absolute. Carbon 3 returns a SIGNED
            // diff, so now()->diffInHours($past) is NEGATIVE — which made
            // "<= 24" true for every past timestamp and stamped every record
            // 'fresh' no matter how old. On the blood branch below, where the
            // window is 2 hours, that is the difference between a usable answer
            // and a dangerous one.
            $hoursDiff = $record->last_updated_at->diffInHours(now(), true);
            
            if ($hoursDiff <= 24) {
                $status = 'fresh';
            } elseif ($hoursDiff <= 72) {
                $status = 'recent';
            } else {
                $status = 'stale';
            }

            $record->update(['freshness_status' => $status]);
        }
    }

    /**
     * Evaluate freshness for blood availability records
     */
    public function checkBloodAvailabilityFreshness($facilityId)
    {
        $records = BloodAvailability::where('facility_id', $facilityId)->get();
        
        foreach ($records as $record) {
            if (!$record->last_updated_at) {
                $record->update(['freshness_status' => 'stale']);
                continue;
            }

            // Arguments in this order, and absolute. Carbon 3 returns a SIGNED
            // diff, so now()->diffInHours($past) is NEGATIVE — which made
            // "<= 24" true for every past timestamp and stamped every record
            // 'fresh' no matter how old. On the blood branch below, where the
            // window is 2 hours, that is the difference between a usable answer
            // and a dangerous one.
            $hoursDiff = $record->last_updated_at->diffInHours(now(), true);
            
            if ($hoursDiff <= 2) {
                $status = 'fresh';
            } elseif ($hoursDiff <= 6) {
                $status = 'recent';
            } else {
                $status = 'stale';
            }

            $record->update(['freshness_status' => $status]);
        }
    }

    /**
     * Scan and mark all stale listings across the entire system
     */
    public function markAllStaleRecords()
    {
        // Pharmacy stock (older than 72 hours is stale)
        PharmacyStockAvailability::where('last_updated_at', '<', now()->subHours(72))
            ->update(['freshness_status' => 'stale']);

        // Blood availability (older than 6 hours is stale)
        BloodAvailability::where('last_updated_at', '<', now()->subHours(6))
            ->update(['freshness_status' => 'stale']);

        // Lab tests (older than 30 days is stale)
        LabTestAvailability::where('last_updated_at', '<', now()->subDays(30))
            ->update(['freshness_status' => 'stale']);
    }
}
