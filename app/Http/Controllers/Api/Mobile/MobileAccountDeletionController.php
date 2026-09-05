<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccountClosureRequest;
use App\Models\Patient;
use App\Services\Compliance\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * In-app account deletion — the store blocker.
 * ============================================
 *
 * Apple 5.1.1(v) and Google Play both require an app that offers account
 * creation to offer account deletion from inside the app. This app creates
 * accounts (`POST /mobile/auth/register`) and, until this controller, had no
 * deletion route at all — `app/settings.tsx` said so in a comment.
 *
 * ── Where identity comes from ────────────────────────────────────────────
 * `patient_id` is read from `$request->attributes` and from nowhere else.
 * AuthenticateMobilePatient stamps it there after verifying the bearer token
 * against `patient_access_tokens`. There is no patient identifier in any path,
 * query or body accepted by these routes — a caller who POSTs
 * `{"patient_id": "<someone else>"}` is not overridden, the value is simply
 * never read, so it cannot reach the deletion of another person's account.
 * `validate()` below lists exactly the fields that ARE read; anything else in
 * the body is discarded by construction.
 *
 * ── Why a grace period, and why it is long ───────────────────────────────
 * The destination is a medical record. An erasure that fires the instant a
 * finger lands on a button is a mistake nobody can undo, and the two most
 * likely ways a deletion request is wrong — a lost or stolen phone that is
 * later recovered, and a deletion made in anger mid-treatment — both resolve
 * inside a month. So the request opens a 30-day window and the account stays
 * fully usable throughout it. Deliberately: locking the account during the
 * window would take away the very session the patient needs in order to
 * cancel.
 *
 * ── Two locks on the confirm ─────────────────────────────────────────────
 * A password or PIN (proof it is the account holder, not somebody holding an
 * unlocked phone) AND a typed confirmation word (proof the disclosure was
 * read, not scrolled past). Both, not either.
 *
 * ── What the GET returns ─────────────────────────────────────────────────
 * The state AND the full itemised disclosure in one round trip, so the screen
 * can never render a confirm button before it has the text describing what
 * confirming does.
 */
class MobileAccountDeletionController extends Controller
{
    public function __construct(
        private readonly AccountDeletionService $deletion,
    ) {
    }

    /**
     * GET /api/mobile/account/deletion
     *
     * Current closure state + the itemised disclosure, in the patient's
     * language.
     */
    public function show(Request $request): JsonResponse
    {
        $patient = $this->patientFromToken($request);

        if ($patient === null) {
            return response()->json(['message' => __('account_deletion.errors.not_found')], 404);
        }

        $locale = $this->resolveLocale($request);

        // Safety net, not the primary executor.
        //
        // The scheduled `opescare:execute-account-deletions` sweep is what
        // normally runs an elapsed request. If that schedule line is not yet
        // registered on a deployment, a patient who was promised "your account
        // closes on the 3rd" would open the app on the 10th and find it open —
        // the one failure mode this whole feature exists to avoid. So a
        // caller's OWN overdue request is executed here before answering.
        // Scoped to the caller, so this can never touch anyone else's account.
        $pending = $this->deletion->pendingFor($patient->id);
        if ($pending !== null && $pending->isDue()) {
            try {
                $this->deletion->execute($pending);
                $patient->refresh();
            } catch (\Throwable $e) {
                // The request stays pending and the sweep retries it. Better a
                // stale screen than a half-applied erasure.
                Log::error('account_deletion.inline_execute_failed', [
                    'closure_request_id' => $pending->id,
                    'error'              => $e->getMessage(),
                ]);
            }
        }

        $latest = $this->deletion->latestFor($patient->id);

        return response()->json([
            'closure'          => $this->presentClosure($latest, $locale),
            'disclosure'       => $this->deletion->disclosure($patient, $locale),
            'reauth_methods'   => $this->deletion->availableReauthMethods($patient),
            'grace_period_days'=> AccountDeletionService::GRACE_PERIOD_DAYS,
        ], 200);
    }

    /**
     * POST /api/mobile/account/deletion
     *
     * Open the grace period. Requires a password or PIN AND the typed
     * confirmation word.
     */
    public function store(Request $request): JsonResponse
    {
        $patient = $this->patientFromToken($request);

        if ($patient === null) {
            return response()->json(['message' => __('account_deletion.errors.not_found')], 404);
        }

        $locale = $this->resolveLocale($request);

        // Note what is NOT here: patient_id, user_id, health_id, email. The
        // account being closed is the one the bearer token belongs to, and
        // there is no field through which a caller could nominate another.
        $data = $request->validate([
            'confirmation' => 'required|string|max:40',
            'credential'   => 'required|string|max:200',
            'reason'       => 'nullable|string|max:290',
        ]);

        if (! $this->deletion->confirmationMatches($data['confirmation'])) {
            return response()->json([
                'message' => __('account_deletion.errors.bad_confirmation', [], $locale),
                'error'   => 'bad_confirmation',
            ], 422);
        }

        // An account with neither a password nor a PIN cannot prove it is the
        // holder asking. Said plainly, with the way forward, rather than a
        // generic 401 — this is a real population (patients registered at a
        // facility desk), not an edge case.
        if ($this->deletion->availableReauthMethods($patient) === []) {
            return response()->json([
                'message' => __('account_deletion.errors.no_credential', [], $locale),
                'error'   => 'no_credential',
            ], 409);
        }

        $method = $this->deletion->verifyReauth($patient, $data['credential']);

        if ($method === null) {
            return response()->json([
                'message' => __('account_deletion.errors.bad_credential', [], $locale),
                'error'   => 'bad_credential',
            ], 401);
        }

        $closure = $this->deletion->request($patient, [
            'reason'        => $data['reason'] ?? null,
            'reauth_method' => $method,
            'via'           => 'mobile',
            'ip'            => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);

        $date = $closure->executes_after?->toFormattedDateString() ?? '';

        return response()->json([
            'message' => __('account_deletion.request_created', ['date' => $date], $locale),
            'closure' => $this->presentClosure($closure, $locale),
        ], 201);
    }

    /**
     * POST /api/mobile/account/deletion/cancel
     *
     * Stop a pending closure. Authentication is the whole check: only the
     * holder of a live token for this patient can reach it, and cancelling is
     * the SAFE direction — it preserves data. Requiring the password again
     * here would mean a patient who wanted to stop an erasure could be blocked
     * by a forgotten password while the clock ran out.
     */
    public function cancel(Request $request): JsonResponse
    {
        $patient = $this->patientFromToken($request);

        if ($patient === null) {
            return response()->json(['message' => __('account_deletion.errors.not_found')], 404);
        }

        $locale  = $this->resolveLocale($request);
        $pending = $this->deletion->pendingFor($patient->id);

        if ($pending === null) {
            return response()->json([
                'message' => __('account_deletion.nothing_pending', [], $locale),
                'error'   => 'nothing_pending',
                'closure' => $this->presentClosure($this->deletion->latestFor($patient->id), $locale),
            ], 404);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:290',
        ]);

        $cancelled = $this->deletion->cancel($pending, $data['reason'] ?? null);

        return response()->json([
            'message' => __('account_deletion.request_cancelled', [], $locale),
            'closure' => $this->presentClosure($cancelled, $locale),
        ], 200);
    }

    /* ====================================================================== */
    /* Presentation                                                           */
    /* ====================================================================== */

    /**
     * @return array<string, mixed>|null
     */
    private function presentClosure(?AccountClosureRequest $closure, string $locale): ?array
    {
        if ($closure === null) {
            return null;
        }

        $executesAfter = $closure->executes_after;
        $daysLeft      = $closure->daysRemaining();

        return [
            'id'                 => (string) $closure->id,
            'status'             => $closure->status,
            'is_pending'         => $closure->isPending(),
            'requested_at'       => $closure->created_at?->toIso8601String(),
            'executes_after'     => $executesAfter?->toIso8601String(),
            'executes_after_date'=> $executesAfter?->toFormattedDateString(),
            'days_remaining'     => $daysLeft,
            'grace_period_days'  => $closure->grace_period_days,
            'cancelled_at'       => $closure->cancelled_at?->toIso8601String(),
            'executed_at'        => $closure->executed_at?->toIso8601String(),
            'retention_until'    => $closure->retention_until?->toDateString(),
            'disclosure_version' => $closure->disclosure_version,
            'can_cancel'         => $closure->isPending(),

            // Server-translated so the banner is right in the patient's
            // language whether or not the client bundle has been merged.
            'headline' => $closure->isPending()
                ? ($daysLeft === 0
                    ? __('account_deletion.grace.today', [], $locale)
                    : __('account_deletion.grace.title', [
                        'date' => $executesAfter?->toFormattedDateString() ?? '',
                    ], $locale))
                : ($closure->status === AccountClosureRequest::STATUS_COMPLETED
                    ? __('account_deletion.completed.title', [], $locale)
                    : __('account_deletion.request_cancelled', [], $locale)),

            'body' => $closure->isPending()
                ? __('account_deletion.grace.body', [
                    'date' => $executesAfter?->toFormattedDateString() ?? '',
                ], $locale)
                : ($closure->status === AccountClosureRequest::STATUS_COMPLETED
                    ? __('account_deletion.completed.body', [
                        'date'  => $closure->executed_at?->toFormattedDateString() ?? '',
                        'until' => $closure->retention_until?->toDateString() ?? '',
                    ], $locale)
                    : __('account_deletion.request_cancelled', [], $locale)),

            'countdown' => $closure->isPending() && $daysLeft > 0
                ? __('account_deletion.grace.countdown', ['days' => $daysLeft], $locale)
                : null,
        ];
    }

    /* ====================================================================== */
    /* Identity + locale                                                      */
    /* ====================================================================== */

    /**
     * The patient this bearer token belongs to. The ONLY source of identity in
     * this controller.
     *
     * `$request->attributes` is set by AuthenticateMobilePatient from the token
     * row; it is not user-writable — Symfony's ParameterBag is server-side
     * state, not part of the HTTP request the client sends.
     */
    private function patientFromToken(Request $request): ?Patient
    {
        $patientId = $request->attributes->get('patient_id');

        if (! is_string($patientId) || $patientId === '') {
            return null;
        }

        return Patient::find($patientId);
    }

    /** Same contract as MobileConsentLedgerController: ?lang, then Accept-Language. */
    private function resolveLocale(Request $request): string
    {
        $supported = ['en', 'fr'];

        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $supported, true)) {
            return $requested;
        }

        $header  = (string) $request->header('Accept-Language', '');
        $primary = strtolower(substr(trim(explode(',', $header)[0] ?? ''), 0, 2));
        if (in_array($primary, $supported, true)) {
            return $primary;
        }

        return in_array(App::getLocale(), $supported, true) ? App::getLocale() : 'en';
    }
}
