<?php

namespace App\Services\Staff;

use App\Models\ProviderCredential;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CredentialingService
{
    public function addCredential(array $data): ProviderCredential
    {
        return ProviderCredential::create(array_merge(['status' => 'active'], $data));
    }

    public function verify(string $credentialId, string $verifiedBy): ProviderCredential
    {
        $credential = ProviderCredential::findOrFail($credentialId);

        $credential->update([
            'verified_by' => $verifiedBy,
            'verified_at' => Carbon::now(),
            'status'      => 'active',
        ]);

        return $credential->fresh();
    }

    public function getExpiringCredentials(int $daysAhead = 30): Collection
    {
        $cutoff = Carbon::now()->addDays($daysAhead);

        return ProviderCredential::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoff)
            ->where('expiry_date', '>=', Carbon::now())
            ->with('provider')
            ->orderBy('expiry_date')
            ->get();
    }

    public function getProviderCredentials(string $providerId): Collection
    {
        return ProviderCredential::where('provider_id', $providerId)
            ->orderBy('credential_type')
            ->orderByDesc('issued_date')
            ->get();
    }

    public function getCredentialSummary(string $facilityId): array
    {
        // Get all providers belonging to this facility
        $providerIds = User::where('facility_id', $facilityId)->pluck('id');

        $credentials = ProviderCredential::whereIn('provider_id', $providerIds)->get();

        $totalProviders = $providerIds->count();
        $hasExpired     = $credentials->where('status', 'expired')->pluck('provider_id')->unique()->count();
        $expiringSoon   = $credentials
            ->filter(fn (ProviderCredential $c) => $c->isExpiringSoon(30))
            ->pluck('provider_id')
            ->unique()
            ->count();

        // "Fully credentialed" = has at least one active medical_license and no expired credentials
        $fullyCredentialed = $providerIds->filter(function ($providerId) use ($credentials) {
            $providerCreds = $credentials->where('provider_id', $providerId);
            $hasLicense    = $providerCreds->where('credential_type', 'medical_license')
                ->where('status', 'active')
                ->isNotEmpty();
            $hasNoExpired  = $providerCreds->where('status', 'expired')->isEmpty();
            return $hasLicense && $hasNoExpired;
        })->count();

        return [
            'total_providers'    => $totalProviders,
            'fully_credentialed' => $fullyCredentialed,
            'has_expired'        => $hasExpired,
            'expiring_soon'      => $expiringSoon,
        ];
    }

    public function getExpiredCredentials(): Collection
    {
        return ProviderCredential::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now()->toDateString())
            ->with('provider')
            ->get();
    }

    public function getExpiringWithin(int $days): Collection
    {
        return $this->getExpiringCredentials($days);
    }
}
