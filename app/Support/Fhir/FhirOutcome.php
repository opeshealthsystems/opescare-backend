<?php

namespace App\Support\Fhir;

use Illuminate\Http\JsonResponse;

/**
 * FHIR R4 OperationOutcome responses.
 *
 * Every refusal from the ingest API is a structured OperationOutcome with the
 * correct `issue.code` from the FHIR issue-type value set, served as
 * `application/fhir+json`. A partner's FHIR client is built to read this; a
 * bare JSON string would be an unhandled parse failure at the far end, which
 * is how a refused clinical write gets logged as "server error" and retried
 * forever.
 *
 * -- Status codes ---------------------------------------------------------
 * The platform's rules, applied here:
 *
 *   401  no token / bad token                (VerifyBearerToken)
 *   403  missing SMART scope                 (VerifyBearerToken)
 *   403  no patient consent for this partner  <- named as consent, by rule
 *   404  the Health ID does not resolve      -- never 403, so a refusal
 *        cannot be read as "this person exists but you may not touch them"
 *   400  the resource is malformed / a code is invalid
 *   409  Idempotency-Key reused with a different body
 *   422  business rule refused the content
 *
 * -- Language -------------------------------------------------------------
 * OperationOutcome diagnostics are DEVELOPER-facing and stay in English, per
 * FHIR convention: they are read by an integration engineer in a log, are
 * quoted verbatim in support tickets, and must not change with the Accept-
 * Language of whichever machine made the call. Nothing here is ever rendered
 * to a patient or a clinician. User-facing strings elsewhere still go through
 * __().
 */
final class FhirOutcome
{
    public const CONTENT_TYPE = 'application/fhir+json';

    /** A machine-readable OperationOutcome array. */
    public static function make(string $severity, string $code, string $diagnostics, ?string $expression = null): array
    {
        $issue = [
            'severity'    => $severity,
            'code'        => $code,
            'diagnostics' => $diagnostics,
        ];

        if ($expression !== null) {
            $issue['expression'] = [$expression];
        }

        return [
            'resourceType' => 'OperationOutcome',
            'issue'        => [$issue],
        ];
    }

    /** Any FHIR resource, served with the right content type. */
    public static function respond(array $resource, int $status = 200, array $headers = []): JsonResponse
    {
        return response()
            ->json($resource, $status, array_merge(['Content-Type' => self::CONTENT_TYPE], $headers));
    }

    public static function error(int $status, string $code, string $diagnostics, ?string $expression = null): JsonResponse
    {
        return self::respond(self::make('error', $code, $diagnostics, $expression), $status);
    }

    // -- The named refusals ------------------------------------------------

    /**
     * The Health ID did not resolve.
     *
     * 404, and the diagnostic deliberately does not echo the identifier back:
     * a partner already knows what it sent, and an error string is the
     * easiest place for a Health ID to end up in somebody else's log file.
     *
     * THIS IS NOT A "CREATE IT THEN" PATH. A partner may never mint an
     * identity on this platform. An unknown Health ID is the end of the
     * request, every time.
     */
    public static function unknownPatient(): JsonResponse
    {
        return self::error(
            404,
            'not-found',
            'No patient matches the supplied Health ID identifier. This server does not create '
            . 'patient records from partner submissions: a Health ID must already exist and be '
            . 'supplied exactly. Resolve the patient through the OpesCare Health ID service and retry.',
            'subject.identifier'
        );
    }

    /**
     * No active consent grant — answered IDENTICALLY to an unknown Health ID.
     *
     * ── Why this is deliberately unhelpful ───────────────────────────────
     * Returning 403 here while an unknown Health ID returned 404 told a
     * partner which Health IDs are real. That is an enumeration oracle: hold
     * one partner credential, post a throwaway Observation at a guessed
     * identifier, and the status code answers "is this a registered person".
     *
     * On a NATIONAL health ID that is a membership disclosure. It confirms
     * someone exists in the health system at all — to a party that patient
     * has given no consent to, and without their knowledge.
     *
     * This platform already refuses to leak existence elsewhere: a
     * cross-facility record answers 404, never 403, for precisely this
     * reason. An ingest endpoint must not be the one door that answers the
     * question the rest of the platform declines to.
     *
     * So both refusals return the SAME status and the SAME body. A partner
     * cannot separate "no such Health ID" from "that person has not
     * consented to you" — which is the correct amount for an unconsented
     * party to learn.
     *
     * ── The legitimate partner is not abandoned ──────────────────────────
     * The true reason IS recorded server-side on every attempt, in
     * `fhir_ingest_events`, against the authenticated client. A partner
     * debugging an integration recovers the distinction through their own
     * account, where they have already proved who they are — not from an
     * unauthenticated guess. The cost is a slower debugging loop for a
     * partner with a typo. The alternative is shipping a probe endpoint for
     * the national register, which is not a trade worth making.
     */
    public static function consentRequired(): JsonResponse
    {
        return self::unknownPatient();
    }

    public static function invalidCode(string $system, string $code, string $why): JsonResponse
    {
        return self::error(
            400,
            'code-invalid',
            sprintf('The %s code "%s" was refused: %s. A wrong code says something false about a '
                . 'patient in a format other systems are built to believe, so it is not stored.',
                $system, $code, $why),
            'code.coding'
        );
    }

    public static function structure(string $diagnostics, ?string $expression = null): JsonResponse
    {
        return self::error(400, 'structure', $diagnostics, $expression);
    }

    public static function invalid(string $diagnostics, ?string $expression = null): JsonResponse
    {
        return self::error(400, 'invalid', $diagnostics, $expression);
    }

    public static function businessRule(string $diagnostics, ?string $expression = null): JsonResponse
    {
        return self::error(422, 'business-rule', $diagnostics, $expression);
    }

    public static function idempotencyRequired(): JsonResponse
    {
        return self::error(
            400,
            'required',
            'An Idempotency-Key header is required on every write to this API. A retried clinical '
            . 'write without one is indistinguishable from a second real event.',
            'http.header.Idempotency-Key'
        );
    }

    public static function idempotencyConflict(): JsonResponse
    {
        return self::error(
            409,
            'conflict',
            'This Idempotency-Key has already been used with a different request body. Use a new '
            . 'key for a new clinical event, or resend the identical body to replay the original '
            . 'response.',
            'http.header.Idempotency-Key'
        );
    }

    /**
     * The token carries no facility.
     *
     * facility_id comes from the verified token and from nowhere else - not a
     * header, not the body, not an Organization reference in the posted
     * resource, and never "the one facility we happened to find".
     */
    public static function noFacilityInToken(): JsonResponse
    {
        return self::error(
            403,
            'forbidden',
            'The access token carries no facility. Every write is attributed to the facility named '
            . 'in the token; a facility supplied in the request body or headers is ignored.'
        );
    }

    public static function insufficientScope(string $required): JsonResponse
    {
        return self::error(
            403,
            'forbidden',
            sprintf('The access token does not carry the required scope "%s".', $required)
        );
    }
}
