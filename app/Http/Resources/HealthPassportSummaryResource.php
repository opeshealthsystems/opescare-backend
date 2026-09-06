<?php

namespace App\Http\Resources;

/**
 * The passport's head: credential status, whose it is, and the thirty-page
 * index. The array is built by HealthPassportBuilder; this only gives it a
 * resource so the response shape has one owner (and so the API ratchet keeps
 * counting hand-built arrays, not this).
 */
class HealthPassportSummaryResource extends ApiResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return (array) $this->resource;
    }
}
