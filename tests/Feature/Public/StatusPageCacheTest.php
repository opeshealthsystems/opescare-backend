<?php

namespace Tests\Feature\Public;

use App\Modules\Admin\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The public status page, exercised on the CACHE HIT rather than the miss.
 *
 * config('cache.serializable_classes') is false — Laravel's default, which
 * stops a leaked APP_KEY from turning the cache into a gadget chain. Every
 * store therefore unserializes with allowed_classes:false, so any object put
 * into the cache returns as __PHP_Incomplete_Class the moment it is read back.
 *
 * SystemHealthService cached a Carbon under 'checked_at' and the view calls
 * ->copy() on it, so /status rendered once and then 500'd for the remaining
 * 30 seconds of its TTL. In production that meant the page was broken far more
 * often than it worked.
 *
 * The whole suite runs on the 'array' store with serialize => false, which
 * never serializes anything — which is precisely why nothing caught this.
 * These tests pin the production store on purpose.
 */
class StatusPageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The store the deployed app actually uses. Without this the test
        // passes against a bug that is still live.
        config(['cache.default' => 'database']);
        Cache::forget('public_status_health');
    }

    public function test_the_status_page_survives_a_second_request_within_the_cache_window(): void
    {
        $this->get('/status')->assertOk();

        // Same 30s window, so this one is served from cache — the path that broke.
        $this->get('/status')->assertOk();
        $this->get('/status')->assertOk();
    }

    public function test_checked_at_is_still_a_carbon_when_it_comes_back_from_cache(): void
    {
        $service = app(SystemHealthService::class);

        $fresh  = $service->currentHealth();
        $cached = $service->currentHealth();

        $this->assertInstanceOf(Carbon::class, $fresh['checked_at']);
        $this->assertInstanceOf(
            Carbon::class,
            $cached['checked_at'],
            'a cache hit must not hand back __PHP_Incomplete_Class'
        );
    }

    /**
     * The rule the bug broke, asserted directly: nothing this service caches
     * may be an object, whatever the shape of the payload becomes later.
     */
    public function test_nothing_object_shaped_is_written_to_the_cache(): void
    {
        app(SystemHealthService::class)->currentHealth();

        $raw = Cache::store('database')->get('public_status_health');

        $this->assertIsArray($raw);
        array_walk_recursive($raw, function ($value, $key) {
            $this->assertIsNotObject($value, "cached key '{$key}' holds an object");
        });
    }

    public function test_the_status_page_renders_in_both_languages_from_cache(): void
    {
        foreach (['en', 'fr', 'en', 'fr'] as $locale) {
            $this->get('/status?lang=' . $locale)->assertOk();
        }
    }
}
