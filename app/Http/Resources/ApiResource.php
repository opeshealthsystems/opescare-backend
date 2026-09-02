<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base class for all OpesCare API resources.
 *
 * Every endpoint that returns an entity MUST return it through a resource that
 * extends this class — never a raw Eloquent model. The resource IS the public
 * wire contract: a model column can be added, renamed, or removed without
 * changing the wire format unless the resource is deliberately updated (an
 * additive, non-breaking change). See docs/API-RESOURCES.md and
 * docs/API-VERSIONING.md §4.
 *
 * Wrapping is disabled because OpesCare controllers compose the envelope
 * explicitly, e.g. response()->json(['message' => ..., 'data' => Foo::make($m)]).
 * Leaving Laravel's default "data" wrap on would produce a nested data.data.
 */
abstract class ApiResource extends JsonResource
{
    public static $wrap = null;
}
