<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

/**
 * PHI scrubbing for Sentry events.
 *
 * Strips request bodies, cookies and query strings from every event before it
 * leaves the host. Patient identifiers and clinical payloads must never reach
 * the error tracker (ISO 27799, Cameroon Law No. 2010/012).
 *
 * WHY THIS IS A CLASS AND NOT A CLOSURE IN config/sentry.php
 * ----------------------------------------------------------
 * `php artisan config:cache` serialises the merged config with var_export(),
 * which cannot represent a Closure — it fails with
 * "Call to undefined method Closure::__set_state()".
 *
 * That is not a cosmetic failure. deploy.yml enters maintenance mode, pulls,
 * migrates, and only then runs config:cache under `set -e`; a failure there
 * aborts the script before `php artisan up`, leaving production stranded in
 * maintenance until someone intervenes by hand.
 *
 * A first-class callable array — [self::class, 'scrub'] — is two plain strings,
 * so it survives var_export() and the config cache builds. Keep it that way:
 * never inline a closure into config/sentry.php again.
 */
class SentryPhiScrubber
{
    /**
     * Remove PHI-bearing keys from an outbound Sentry event.
     *
     * The Laravel SDK invokes the configured before_send as
     * `$callback($event, $eventHint)`, so the hint is accepted and ignored
     * rather than left to arrive as an unexpected extra argument.
     */
    public static function scrub(Event $event, ?EventHint $hint = null): ?Event
    {
        $request = $event->getRequest();

        unset($request['data'], $request['cookies'], $request['query_string']);

        $event->setRequest($request);

        return $event;
    }
}
