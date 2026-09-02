<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class HivCounsellingSessionResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'patient_id'        => $this->patient_id,
            'facility_id'       => $this->facility_id,
            'counsellor_id'     => $this->counsellor_id,
            'session_type'      => $this->session_type,
            'session_date'      => $this->session_date?->toISOString(),
            'test_result'       => $this->test_result,
            'cd4_count'         => $this->cd4_count,
            'viral_load'        => $this->viral_load,
            'on_art'            => (bool) $this->on_art,
            'art_regimen'       => $this->art_regimen,
            'risk_factors'      => $this->risk_factors,
            'counselling_notes' => $this->counselling_notes,
            'follow_up_date'    => $this->follow_up_date?->toISOString(),
            'consent_obtained'  => (bool) $this->consent_obtained,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
