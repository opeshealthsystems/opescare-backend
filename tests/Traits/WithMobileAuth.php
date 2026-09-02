<?php

namespace Tests\Traits;

use App\Models\Patient;
use App\Models\PatientAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

trait WithMobileAuth
{
    /**
     * Create a valid PatientAccessToken for a patient and return the auth header array.
     */
    protected function mobileAuthHeaders(Patient $patient): array
    {
        $rawToken = 'pat_' . \Illuminate\Support\Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $patient->id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addHours(24),
        ]);
        return ['Authorization' => "Bearer {$rawToken}"];
    }

    /**
     * Make a GET request to a mobile route with patient auth headers.
     */
    protected function mobileGetJson(Patient $patient, string $uri, array $data = [])
    {
        return $this->withHeaders($this->mobileAuthHeaders($patient))->getJson($uri, $data);
    }

    /**
     * Make a POST request to a mobile route with patient auth headers.
     */
    protected function mobilePostJson(Patient $patient, string $uri, array $data = [])
    {
        return $this->withHeaders($this->mobileAuthHeaders($patient))->postJson($uri, $data);
    }
}
