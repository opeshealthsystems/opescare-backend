<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * A FHIR ingest write that must not happen.
 *
 * Thrown rather than returned so that a refusal deep inside a transaction
 * Bundle unwinds the whole transaction. A Bundle is all-or-nothing: half a
 * lab report is a worse record than no lab report, because nothing downstream
 * can tell which half is missing.
 *
 * Carries the OperationOutcome response to send AND the short refusal code
 * written to `fhir_ingest_events`, so the log and the wire always agree about
 * why a write was refused.
 */
class FhirIngestRefusal extends \RuntimeException
{
    public function __construct(
        public readonly JsonResponse $response,
        public readonly string $refusalCode,
        public readonly string $refusalDetail,
    ) {
        parent::__construct($refusalDetail);
    }

    public static function make(JsonResponse $response, string $refusalCode, string $detail): self
    {
        return new self($response, $refusalCode, $detail);
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }
}
