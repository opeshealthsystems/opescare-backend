<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

/**
 * An unknown non-GET path must answer 404, not 419. Laravel skips CSRF under
 * unit tests, so this test re-arms the real middleware to prove the fallback
 * route excludes the class this Laravel version actually registers.
 */
class UnknownNonGetPathAnswers404Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_csrf_is_really_armed_in_this_test(): void
    {
        // A real POST route without a token must still be refused with 419.
        $this->post('/login', [])->assertStatus(419);
    }

    public function test_unknown_post_answers_404_not_419(): void
    {
        $this->post('/this-path-does-not-exist-'.uniqid())->assertNotFound();
    }

    public function test_unknown_put_patch_delete_answer_404(): void
    {
        $path = '/no-such-page-'.uniqid();
        $this->put($path)->assertNotFound();
        $this->patch($path)->assertNotFound();
        $this->delete($path)->assertNotFound();
    }

    public function test_known_get_path_under_post_keeps_405(): void
    {
        $this->post('/privacy')->assertStatus(405);
    }
}
