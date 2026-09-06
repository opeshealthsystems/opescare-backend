<?php

namespace App\Services\FrontDesk;

use RuntimeException;

/**
 * A front-desk action the service will not perform, with the lang key that
 * explains why and the HTTP status the refusal should carry.
 *
 * `key` is looked up in lang/{en,fr}/desk_api.php by the desk controllers and
 * in lang/{en,fr}/doctor_schedule.php by the clinician's follow-up endpoint,
 * so every key thrown here exists in both files.
 */
final class FrontDeskRefusal extends RuntimeException
{
    public function __construct(public readonly string $key, public readonly int $status)
    {
        parent::__construct(strtoupper($key));
    }
}
