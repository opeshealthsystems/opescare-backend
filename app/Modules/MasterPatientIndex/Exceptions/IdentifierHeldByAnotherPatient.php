<?php

namespace App\Modules\MasterPatientIndex\Exceptions;

use App\Models\MpiCandidate;
use RuntimeException;

/**
 * Thrown by linkIdentifier() when the identifier is already on a different
 * patient. That is the strongest duplicate signal the MPI receives — a
 * national ID, an insurance number, a facility MRN can belong to one person —
 * so instead of surfacing as a raw unique-constraint violation it becomes a
 * deterministic candidate for review, carried here so the caller can point
 * the operator at it.
 */
final class IdentifierHeldByAnotherPatient extends RuntimeException
{
    public function __construct(
        public readonly MpiCandidate $candidate,
        string $identifierType,
    ) {
        parent::__construct("MPI_IDENTIFIER_HELD_BY_ANOTHER_PATIENT: {$identifierType} — review candidate {$candidate->id}");
    }
}
