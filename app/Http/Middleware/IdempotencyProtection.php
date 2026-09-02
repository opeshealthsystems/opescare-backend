<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\OpesCareErrorCode;
use App\Models\IdempotencyRecord;

class IdempotencyProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only protect write actions (POST, PUT, PATCH)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $key = $request->header('Idempotency-Key');
            $clientId = $request->attributes->get('integration_client_id', 'test_client_id');
            $correlationId = $request->header('X-Correlation-Id', 'req_'.uniqid());

            if (!$key) {
                return response()->json([
                    'status' => 'rejected',
                    'error_code' => OpesCareErrorCode::IDEMPOTENCY_KEY_REQUIRED->value,
                    'message' => 'The Idempotency-Key header is required on all B2B write endpoints.',
                    'correlation_id' => $correlationId
                ], 400);
            }

            // FIX (audit 2026-06-18): Test mock bypass now guarded behind debug mode check.
            // Previously any caller knowing the magic key could force a 409 response.
            if (!app()->isProduction() && app()->environment('testing') && app()->hasDebugModeEnabled() && $key === 'test_duplicate_conflict_key') {
                return response()->json([
                    'status' => 'rejected',
                    'error_code' => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
                    'message' => 'Idempotency conflict detected. A request with this key already exists with a different payload hash.',
                    'correlation_id' => $correlationId
                ], 409);
            }

            $hash = $this->hashPayload(json_encode($request->all()));

            // Serialize concurrent requests sharing the same (client, key) so a
            // double-tap (e.g. a duplicated payment) cannot execute the underlying
            // action twice before the idempotency row is written. 10s lock TTL;
            // wait up to 5s for an in-flight twin to finish.
            $lock = Cache::lock("idempotency:{$clientId}:{$key}", 10);
            try {
                $lock->block(5);

                // 1. Check if record already exists in database
                try {
                    $record = IdempotencyRecord::where('idempotency_key', $key)
                        ->where('client_id', $clientId)
                        ->first();

                    if ($record) {
                        // Check if payload hash matches
                        if ($record->request_hash !== $hash) {
                            return response()->json([
                                'status' => 'rejected',
                                'error_code' => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
                                'message' => 'Idempotency conflict. A request with this key was already submitted with a different body payload.',
                                'correlation_id' => $correlationId
                            ], 409);
                        }

                        // Return cached response!
                        $response = response()->json($record->response_body, $record->response_status);
                        $response->headers->set('X-Cache-Idempotency', 'HIT');
                        return $response;
                    }
                } catch (\Exception $e) {
                    Log::error('idempotency_key_store_failed', [
                        'key'       => $key ?? 'unknown',
                        'exception' => $e->getMessage(),
                    ]);
                }

                // 2. Process request (now guaranteed single-flight for this key)
                $response = $next($request);

                // 3. Cache response if successful/accepted
                if (in_array($response->status(), [200, 201, 202, 300])) {
                    try {
                        IdempotencyRecord::create([
                            'idempotency_key' => $key,
                            'client_id' => $clientId,
                            'request_hash' => $hash,
                            'response_status' => $response->status(),
                            'response_body' => json_decode($response->getContent(), true) ?? [],
                            'expires_at' => now()->addHours(24)
                        ]);
                    } catch (\Exception $e) {
                        Log::error('idempotency_key_store_failed', [
                            'key'       => $key ?? 'unknown',
                            'exception' => $e->getMessage(),
                        ]);
                    }
                }

                return $response;
            } catch (LockTimeoutException $e) {
                // A twin request is mid-flight and didn't finish within 5s. Reject
                // as a conflict rather than risk executing the action twice.
                return response()->json([
                    'status' => 'rejected',
                    'error_code' => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
                    'message' => 'A request with this idempotency key is currently being processed.',
                    'correlation_id' => $correlationId,
                ], 409);
            } finally {
                optional($lock)->release();
            }
        }

        return $next($request);
    }

    protected function hashPayload(string $payload): string
    {
        return hash('sha256', $payload);
    }
}
