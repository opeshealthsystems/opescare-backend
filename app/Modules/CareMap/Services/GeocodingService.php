<?php

namespace App\Modules\CareMap\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Translate address string into coordinates.
     * Uses Nominatim (OpenStreetMap) — free, no API key required.
     * Falls back gracefully when unavailable.
     */
    public function geocodeAddress(string $addressString): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'OpesCare-HealthPlatform/1.0 (opescare@example.com)',
                    'Accept-Language' => 'en',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $addressString,
                    'format'         => 'json',
                    'limit'          => 1,
                    'addressdetails' => 0,
                ]);

            if ($response->successful()) {
                $results = $response->json();
                if (!empty($results)) {
                    return [
                        'latitude'  => (float) $results[0]['lat'],
                        'longitude' => (float) $results[0]['lon'],
                        'accuracy'  => 'address_level',
                        'source'    => 'nominatim',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Geocoding failed', ['address' => $addressString, 'error' => $e->getMessage()]);
        }

        // Return null coordinates with explicit fallback marker so callers know it's unresolved
        return [
            'latitude'  => null,
            'longitude' => null,
            'accuracy'  => 'unresolved',
            'source'    => 'fallback',
        ];
    }
}
