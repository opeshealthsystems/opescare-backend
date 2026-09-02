# Handoff: Blockers #2 & #4 — patches for fenced files

**Author:** Claude (i18n + readiness session) · **Date:** 2026-06-17
**Why a handoff:** these two fixes touch files reserved for the production-hardening
team (`routes/api.php`, `app/Http/Middleware/IdempotencyProtection.php`). They were
intentionally **NOT applied** to avoid colliding with concurrent work. Apply the
diffs below when convenient. Both are additive and low-risk.

Companion fixes already landed on `codex/production-hardening` (non-fenced files):
- `401c81cf` — payment gateway timeouts + connection-only retries (MoMo/Orange)
- `ac929860` — HL7 ADT fails closed in production when TLS is off

---

## Blocker #2 — Rate-limit the Connect token + billing endpoints

**Problem:** `POST /v1/connect/auth/token` (credential verification) sits outside any
throttle group → unbounded client_secret brute-force. The `v1/billing` group
(invoices, refunds, `wallets/deposit`, cashier reconcile) has no per-client throttle.

Both rely on limiters that **already exist** — no new limiter needed:
- `verify` → `Limit::perMinute(30)->by($request->ip())` (`AppServiceProvider.php:31`)
- `throttle.client` alias → `ThrottleByClient` middleware (`bootstrap/app.php:49`)

### Patch A — `routes/api.php` (~line 131): throttle the token endpoint
```diff
-    // Auth token request endpoint (unprotected by client middleware, uses POST body credentials)
-    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\Connect\AuthController::class, 'issueToken']);
+    // Auth token request endpoint (uses POST-body credentials). Throttled by IP
+    // via the 'verify' limiter (30/min) to bound credential brute-force.
+    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\Connect\AuthController::class, 'issueToken'])
+        ->middleware('throttle:verify');
```

### Patch B — `routes/api.php` (line 61): throttle the billing group
```diff
-Route::prefix('v1/billing')->middleware([VerifyIntegrationClient::class, 'module:billing'])->group(function () {
+Route::prefix('v1/billing')->middleware([VerifyIntegrationClient::class, 'module:billing', 'throttle.client:120,1'])->group(function () {
```
(120 req/min/client is generous for a cashier UI; tune as needed. `throttle.client`
keys by the authenticated integration client, so one abusive partner can't starve others.)

**Optional (tighter, recommended for the token endpoint):** instead of reusing
`verify`, add a dedicated limiter in `AppServiceProvider::boot()` keyed by IP **and**
the posted `client_id`, so one IP can't rotate client_ids to amplify:
```php
RateLimiter::for('connect-token', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip().'|'.$request->input('client_id'));
});
```
…then use `->middleware('throttle:connect-token')` in Patch A.

---

## Blocker #4 — Make the idempotency write path atomic (payment double-tap)

**Problem (`IdempotencyProtection.php:44-93`):** the existence check (steps 1) and the
`IdempotencyRecord::create()` (step 3) are not atomic and not lock-wrapped. Two
concurrent identical requests (double-tapped MoMo payment) can BOTH pass the
"record not found" check, BOTH run `$next($request)` (executing the charge twice),
and only then race on insert — the DB unique constraint rejects the second row
*after the side effect already happened*. Idempotency therefore does not actually
prevent the duplicate action under concurrency.

**Fix:** serialize concurrent requests that share the same `(client_id, key)` with an
atomic cache lock, so the twin waits for the first to finish and then hits the cached
response. Works on the `database` and `redis` cache stores (both lock-capable);
single-process test runs (`array`) are unaffected.

### Patch C — `app/Http/Middleware/IdempotencyProtection.php`
Add the import:
```diff
 use Illuminate\Support\Facades\Log;
+use Illuminate\Support\Facades\Cache;
+use Illuminate\Contracts\Cache\LockTimeoutException;
```

Wrap the check → process → cache block (current lines ~42-95) in a lock:
```diff
         $hash = $this->hashPayload(json_encode($request->all()));

-        // 1. Check if record already exists in database
-        try {
-            $record = IdempotencyRecord::where('idempotency_key', $key)
-                ->where('client_id', $clientId)
-                ->first();
-            ... existing steps 1-3 ...
-        }
-        return $response;
+        // Serialize concurrent requests sharing the same (client, key) so a
+        // double-tap can't execute the underlying action twice before the
+        // idempotency row is written. 10s lock TTL, wait up to 5s for the twin.
+        $lock = Cache::lock("idempotency:{$clientId}:{$key}", 10);
+        try {
+            $lock->block(5);
+
+            // 1. Check if record already exists
+            try {
+                $record = IdempotencyRecord::where('idempotency_key', $key)
+                    ->where('client_id', $clientId)
+                    ->first();
+
+                if ($record) {
+                    if ($record->request_hash !== $hash) {
+                        return response()->json([
+                            'status' => 'rejected',
+                            'error_code' => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
+                            'message' => 'Idempotency conflict. A request with this key was already submitted with a different body payload.',
+                            'correlation_id' => $correlationId,
+                        ], 409);
+                    }
+                    $response = response()->json($record->response_body, $record->response_status);
+                    $response->headers->set('X-Cache-Idempotency', 'HIT');
+                    return $response;
+                }
+            } catch (\Exception $e) {
+                Log::error('idempotency_key_store_failed', ['key' => $key, 'exception' => $e->getMessage()]);
+            }
+
+            // 2. Process request (now guaranteed single-flight for this key)
+            $response = $next($request);
+
+            // 3. Cache response if successful/accepted
+            if (in_array($response->status(), [200, 201, 202, 300])) {
+                try {
+                    IdempotencyRecord::create([
+                        'idempotency_key' => $key,
+                        'client_id' => $clientId,
+                        'request_hash' => $hash,
+                        'response_status' => $response->status(),
+                        'response_body' => json_decode($response->getContent(), true) ?? [],
+                        'expires_at' => now()->addHours(24),
+                    ]);
+                } catch (\Exception $e) {
+                    Log::error('idempotency_key_store_failed', ['key' => $key, 'exception' => $e->getMessage()]);
+                }
+            }
+
+            return $response;
+        } catch (LockTimeoutException $e) {
+            // A twin request is mid-flight and didn't finish within 5s. Reject as
+            // a conflict rather than risk a duplicate action.
+            return response()->json([
+                'status' => 'rejected',
+                'error_code' => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
+                'message' => 'A request with this idempotency key is currently being processed.',
+                'correlation_id' => $correlationId,
+            ], 409);
+        } finally {
+            optional($lock)->release();
+        }
```

**Prereqs / notes:**
- Requires a lock-capable cache store in prod. `database` (current default) and
  `redis` both qualify. Do **not** run prod on the `array`/`null` cache store.
- Keep the existing DB unique index on `(idempotency_key, client_id)` as a
  belt-and-suspenders backstop — the lock is the primary guard, the constraint
  catches anything that slips a cross-node edge.
- Existing `ConnectPlatformTest` idempotency tests should still pass (single-process,
  array-lock is a no-op that succeeds immediately). Re-run:
  `php artisan test --filter=ConnectPlatformTest`.

---

## Suggested commit message (when applied)
```
fix(security): rate-limit Connect token + billing; make idempotency single-flight

- throttle POST /v1/connect/auth/token (verify limiter) + v1/billing group
- wrap IdempotencyProtection check→process→write in an atomic cache lock so
  concurrent double-tap payments can't execute the action twice
```
