<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Enums\PartnerStatus;

class VerifyPartnerTrustLevel
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $minTrustLevel = null): Response
    {
        // 1. Resolve Partner ID from header (X-Partner-ID) or Token (for testing/demo)
        $partnerId = $request->header('X-Partner-ID');

        if (!$partnerId) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PARTNER_NOT_VERIFIED',
                'message' => 'Partner identity could not be verified. Missing X-Partner-ID header.'
            ], 403);
        }

        $partner = Partner::where('uuid', $partnerId)->first();

        if (!$partner) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PARTNER_NOT_VERIFIED',
                'message' => 'Partner identity not found.'
            ], 403);
        }

        // 2. Check if suspended
        if ($partner->status === PartnerStatus::SUSPENDED->value) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PARTNER_SUSPENDED',
                'message' => 'Partner access is suspended.'
            ], 403);
        }

        // 3. Verify trust level meets minimum requirement
        $requiredLevel = (int) ($minTrustLevel ?? 1);
        $partnerLevel = $this->extractTrustLevelNumber($partner->trust_level);

        if ($partnerLevel < $requiredLevel) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'INSUFFICIENT_TRUST_LEVEL',
                'message' => 'Partner trust level is insufficient for this operation.'
            ], 403);
        }

        // 4. Inject Partner into request so controllers can use it
        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    /**
     * Extract the numeric portion from a trust level string.
     * E.g. 'level_3_operational_verified' -> 3
     */
    private function extractTrustLevelNumber(string $trustLevel): int
    {
        if (preg_match('/level_(\d+)/', $trustLevel, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
}
