<?php

namespace App\Services\Queue;

use RuntimeException;

/**
 * A refusal the queue desk can explain: `key` names a `queue.refusal.*`
 * translation, `status` the HTTP status a JSON caller would receive.
 */
final class QueueRefusal extends RuntimeException
{
    public function __construct(public readonly string $key, public readonly int $status = 422)
    {
        parent::__construct(strtoupper($key));
    }
}
