<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\HealthIdLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /login/opescard/resolve — turn a scanned OPESCARD QR into the Health ID
 * that fills the sign-in box.
 *
 * The card QR carries an opaque token (/verify/qr/qrx_…), not the Health ID,
 * so the browser cannot decode it on its own. This answers with the Health ID
 * and nothing else — no name, date of birth or photo — and the same 404 for
 * every refusal (unknown, revoked, expired, wrong secret, not a card QR), so it
 * says nothing about why. It never signs anyone in: the password is still
 * required by POST /login.
 */
class OpescardController extends Controller
{
    public function resolve(Request $request, HealthIdLogin $login): JsonResponse
    {
        $data = $request->validate([
            'payload' => 'required|string|max:512',
        ]);

        $healthId = $login->healthIdFromCardPayload($data['payload']);

        Log::info('auth.opescard_resolve', [
            'result' => $healthId ? 'success' : 'failure',
            'ip'     => $request->ip(),
        ]);

        if ($healthId === null) {
            return response()->json(['message' => __('opescard.errors.not_recognised')], 404);
        }

        return response()->json(['health_id' => $healthId]);
    }
}
