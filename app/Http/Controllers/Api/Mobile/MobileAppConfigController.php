<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MobileAppConfigController extends Controller
{
    /**
     * GET /api/mobile/app-config
     *
     * Public, unauthenticated version-gate consumed by the patient app at
     * startup (before login) to drive the forced-update flow.
     */
    public function show(): JsonResponse
    {
        // Never cacheable. This drives the forced-update gate and doubles as the
        // client's reachability probe, so a cached copy defeats both: a client
        // pinned to a stale min_supported_build can't be blocked, and a cached
        // response (or, on web, a cached CORS failure from before an origin was
        // allowed) makes the app report itself offline while the API is healthy.
        return response()
            ->json([
                'min_supported_build' => (int) config('mobile.min_supported_build'),
                'latest_version'      => (string) config('mobile.latest_version'),
                'store_url'           => (string) config('mobile.store_url'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
