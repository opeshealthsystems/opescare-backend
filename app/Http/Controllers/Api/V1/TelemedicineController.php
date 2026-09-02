<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/**
 * TelemedicineController — PLACEHOLDER. Not an implementation.
 *
 * The full telehealth platform (waiting-room queue management and video
 * session orchestration) is frozen out of V1 behind the `telemedicine_full`
 * feature flag. The real controller was deleted when the module was frozen;
 * its route group in the SEALED routes/api.php (api/v1/telemedicine/*) could
 * not be, so this class exists purely so that route table can be built. See
 * FrozenModulePlaceholderController for the full rationale.
 *
 * HISTORY — read before narrowing the freeze. The freeze map originally covered
 * only the call / waiting-room / session paths, on the reasoning that the thin
 * "book -> consult" path should stay in. But its controller had already been
 * deleted with the rest of the module, so the four routes left matched-but-
 * unfrozen (consultations create/show, consent, cancel) handed an AUTHENTICATED
 * partner a fatal class-not-found instead of a clean answer. bootstrap/app.php
 * was broadened to 'api/v1/telemedicine' + 'api/v1/telemedicine/*' to close
 * that gap, and this class is the belt to that map's braces — if a pattern is
 * ever dropped again, unavailable() still 404s rather than fataling.
 *
 * Frozen surface (7 routes, routes/api.php lines 992-1000):
 *   POST  api/v1/telemedicine/consultations
 *   GET   api/v1/telemedicine/consultations/{consultId}
 *   POST  api/v1/telemedicine/consultations/{consultId}/cancel
 *   POST  api/v1/telemedicine/consultations/{consultId}/consent
 *   POST  api/v1/telemedicine/consultations/{consultId}/waiting-room
 *   POST  api/v1/telemedicine/consultations/{consultId}/call
 *   POST  api/v1/telemedicine/sessions/{sessionId}/end
 *
 * recordConsent() is the one to be most careful about: a stub that answered
 * 200 would let a caller believe a patient's telemedicine consent had been
 * captured when nothing was written. It 404s like the rest.
 *
 * @see \App\Http\Controllers\Api\V1\FrozenModulePlaceholderController
 */
class TelemedicineController extends FrozenModulePlaceholderController
{
    protected function featureKey(): string
    {
        return 'telemedicine_full';
    }

    protected function moduleLabel(): string
    {
        return 'telemedicine';
    }

    public function book(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function show(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function cancel(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function recordConsent(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function joinWaitingRoom(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function initiateCall(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function endCall(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }
}
