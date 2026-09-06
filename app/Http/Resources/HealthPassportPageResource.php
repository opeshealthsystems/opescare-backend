<?php

namespace App\Http\Resources;

/**
 * One passport page: its number, title, icon and the sections built from the
 * patient's own records. An empty `sections` array is a true statement -
 * nothing has been recorded on this page - and the app renders it as such.
 */
class HealthPassportPageResource extends ApiResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return (array) $this->resource;
    }
}
