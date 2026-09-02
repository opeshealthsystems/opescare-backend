<?php

namespace Tests\Feature\Observability;

use Tests\TestCase;

class SentryConfigTest extends TestCase
{
    public function test_sentry_never_sends_pii_by_default(): void
    {
        $this->assertFalse((bool) config('sentry.send_default_pii'));
    }

    public function test_sql_bindings_are_not_captured(): void
    {
        // SQL query parameters can carry PHI; they must stay off.
        $this->assertFalse((bool) config('sentry.breadcrumbs.sql_bindings'));
        $this->assertFalse((bool) config('sentry.tracing.sql_bindings'));
    }

    public function test_before_send_scrubs_request_data(): void
    {
        $scrub = config('sentry.before_send');
        $this->assertIsCallable($scrub);

        $event = \Sentry\Event::createEvent();
        $event->setRequest([
            'data'         => ['health_id' => 'CM-HID-XXXX'],
            'cookies'      => ['session' => 'abc'],
            'query_string' => 'token=secret',
            'url'          => 'https://opescare.test/x',
        ]);

        $out = $scrub($event);

        $request = $out->getRequest();
        $this->assertArrayNotHasKey('data', $request);
        $this->assertArrayNotHasKey('cookies', $request);
        $this->assertArrayNotHasKey('query_string', $request);
        $this->assertArrayHasKey('url', $request);
    }
}
