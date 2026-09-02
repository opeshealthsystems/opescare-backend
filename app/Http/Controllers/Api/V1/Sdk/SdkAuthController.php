<?php

namespace App\Http\Controllers\Api\V1\Sdk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SdkAuthController extends Controller
{
    /**
     * Introspect the current SDK token — returns scopes, expiry, and client info.
     */
    public function introspect(Request $request): JsonResponse
    {
        $token = $request->attributes->get('sdk_token');

        if (!$token) {
            // Sandbox bypass path
            return response()->json([
                'active'      => true,
                'client_id'   => 'sandbox',
                'scopes'      => ['*'],
                'environment' => 'sandbox',
                'expires_at'  => null,
            ]);
        }

        return response()->json([
            'active'      => true,
            'token_id'    => $token->id,
            'label'       => $token->label,
            'client_id'   => $token->client_id,
            'scopes'      => $token->scopes ?? [],
            'environment' => $token->environment,
            'expires_at'  => $token->expires_at?->toIso8601String(),
            'last_used_at'=> $token->last_used_at?->toIso8601String(),
        ]);
    }
}
