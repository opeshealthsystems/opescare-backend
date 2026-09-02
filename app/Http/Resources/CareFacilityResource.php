<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CareFacilityResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facility_code' => $this->facility_code,
            'partner_id' => $this->partner_id,
            'organization_id' => $this->organization_id,
            'facility_id' => $this->facility_id,
            'facility_name' => $this->facility_name,
            'facility_type' => $this->facility_type,
            'ownership_type' => $this->ownership_type,
            'license_number' => $this->license_number,
            'license_status' => $this->license_status,
            'verification_status' => $this->verification_status,
            'listing_status' => $this->listing_status,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'city' => $this->city,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geocoding_accuracy' => $this->geocoding_accuracy,
            'phone_primary' => $this->phone_primary,
            'phone_secondary' => $this->phone_secondary,
            'email' => $this->email,
            'website' => $this->website,
            'emergency_contact' => $this->emergency_contact,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'cover_image_path' => $this->cover_image_path,
            'integration_status' => $this->integration_status,
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'last_profile_update_at' => $this->last_profile_update_at?->toISOString(),
            'last_availability_update_at' => $this->last_availability_update_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            /*
             * Where this row came from, and the attribution that has to travel
             * with it.
             *
             * Facilities imported from OpenStreetMap are ODbL-licensed, and
             * ODbL requires attribution wherever the data is redistributed —
             * which this API does, to partners and to the public. Serving an
             * OSM-derived facility without its notice puts OpesCare in breach
             * of the very licence that makes the import lawful, so the notice
             * ships with the record rather than living only in a footer
             * somebody may forget to render.
             *
             * Null for the MINSANTE registry rows, which are not OSM-derived.
             */
            'source_system' => $this->source_system,
            'source_attribution' => $this->source_attribution,
            'source_synced_at' => $this->source_synced_at?->toISOString(),
        ];
    }
}
