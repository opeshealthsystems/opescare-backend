<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePlatformAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/demo/reset — wipes and reseeds demo data and revokes demo sessions.
 *
 * It used to be anonymous and unthrottled. Now (route throttle 5/min):
 *   - demo mode off                      → 404
 *   - signed-in platform-tier admin      → runs
 *   - DEMO_RESET_TOKEN empty (default)   → 404 (the token path does not exist)
 *   - X-Demo-Reset-Token wrong / missing → 403
 *   - X-Demo-Reset-Token matches         → runs (hash_equals, constant time)
 */
class DemoResetController extends Controller
{
    public const HEADER = 'X-Demo-Reset-Token';

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $user = $request->user();
        $viaAdmin = $user !== null && RequirePlatformAdmin::isPlatformTier($user);

        if (! $viaAdmin) {
            $expected = (string) config('demo.reset_token', '');
            abort_if($expected === '', 404);

            $given = (string) $request->header(self::HEADER, '');
            if ($given === '' || ! hash_equals($expected, $given)) {
                Log::warning('demo_reset_refused', ['ip' => $request->ip()]);
                abort(403);
            }
        }

        Log::info('demo_reset_authorised', [
            'via'     => $viaAdmin ? 'platform_admin' : 'token',
            'user_id' => $user?->id,
            'ip'      => $request->ip(),
        ]);

        Artisan::call('opescare:demo:reset');

        return response()->json(['message' => 'Demo reset successfully']);
    }
}
