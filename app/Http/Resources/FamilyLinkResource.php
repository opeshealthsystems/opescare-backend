<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One family link as the app receives it — a member row, a sent invitation
 * or an incoming invitation. MobileFamilyController shapes the array (see
 * formatLink() and the invitation formatters); this resource is the wire
 * boundary those arrays cross, so the API-resources ratchet can see that no
 * raw model reaches the client.
 */
class FamilyLinkResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
