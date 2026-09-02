<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every public marketing page must actually render, in both locales.
 *
 * This exists because /solutions/pharmacies and /solutions/laboratories were
 * live 500s and nothing noticed. Their Blade carried curly quotes inside the
 * translation calls — `__(‘public.sol_labs.badge’)` — and PHP 8 accepts those
 * bytes as identifier characters, so the file PARSED cleanly. `view:cache`
 * compiled it happily; the failure only arrived at request time as
 * "Undefined constant". A compile-time check could never have caught it, and
 * the suite had no test that rendered those pages at all.
 *
 * So this renders every one of them for real, in EN and FR, and asserts the
 * page is not merely a 200 but has its own content. Both locales matter: a
 * missing French key raises no error, it silently prints the key path, which
 * is how a page can be "green" and still be broken for half the country.
 */
class PublicMarketingPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Named public GET routes with no parameters and no auth. Discovered from
     * the router rather than hard-coded, so a new marketing page is covered the
     * moment it is added — the point is that nothing ships unrendered.
     *
     * Not a data provider: PHPUnit runs providers before the framework boots,
     * so the router would be empty and the whole check would silently pass on
     * zero pages.
     */
    private function publicPages(): array
    {
        // A handful of public routes are excluded deliberately, not accidentally:
        //   - auth/onboarding forms: covered by their own flow tests
        //   - /status, /sla: covered by StatusAndSlaPageTest
        //   - well-known + docs: JSON / separate layout
        $skip = [
            'public.status', 'public.sla',
            'well-known.jwks', 'well-known.oauth-metadata',
        ];

        $pages = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (! str_starts_with($name, 'public.') || in_array($name, $skip, true)) {
                continue;
            }
            if (str_contains($route->uri(), '{')) {
                continue; // needs a parameter; not a plain marketing page
            }
            if (array_intersect($route->gatherMiddleware(), ['auth', 'platform.admin'])) {
                continue;
            }
            $pages[$name] = [$name, '/' . ltrim($route->uri(), '/')];
        }

        ksort($pages);

        return $pages;
    }

    public function test_every_public_page_renders_in_both_locales(): void
    {
        $pages = $this->publicPages();

        // Guard the guard: if discovery silently returns nothing, this test
        // would pass while checking absolutely no pages.
        $this->assertGreaterThan(15, count($pages), 'public page discovery found almost nothing');

        $failures = [];

        foreach ($pages as [$name, $uri]) {
            foreach (['en', 'fr'] as $locale) {
                app()->setLocale($locale);

                $res = $this->get($uri, ['Accept-Language' => $locale]);

                if ($res->status() !== 200) {
                    $failures[] = "{$name} ({$uri}) returned {$res->status()} in {$locale}";

                    continue;
                }

                $text = strip_tags((string) $res->getContent());

                if (trim($text) === '') {
                    $failures[] = "{$name} rendered an empty body in {$locale}";
                }

                // An unresolved translation prints its own key path. That is a
                // 200 with broken copy — exactly what this guards against.
                foreach (['landing.nav.', 'landing.footer.', 'public.network_'] as $leak) {
                    if (str_contains($text, $leak)) {
                        $failures[] = "{$name} leaked the raw key prefix '{$leak}' in {$locale}";
                    }
                }
            }
        }

        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    public function test_the_two_network_service_pages_are_routed_and_reachable(): void
    {
        // The medicine and blood availability detail moved off the homepage onto
        // these pages. If the routes vanish, that content becomes unreachable
        // rather than merely relocated.
        $this->get(route('public.network.medicine-finder'))
            ->assertOk()
            ->assertSee(__('public.network_medicine.hero_title'));

        $this->get(route('public.network.blood-finder'))
            ->assertOk()
            ->assertSee(__('public.network_blood.hero_title'));
    }

    public function test_the_homepage_links_to_both_network_pages(): void
    {
        // Per the project convention: a page reachable only by URL does not
        // count as shipped. The homepage is where these two are surfaced.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('public.network.medicine-finder'), $html);
        $this->assertStringContainsString(route('public.network.blood-finder'), $html);
    }

    public function test_the_homepage_no_longer_markets_frozen_modules(): void
    {
        // Billing and the claims workflow are frozen out of V1 and 404 in
        // production (config/features.php). The homepage used to advertise
        // "Billing and Insurance" as a module, which promised a surface that
        // does not answer. Coverage — read-only, and part of identity — stays.
        $text = strip_tags($this->get('/')->assertOk()->getContent());

        $this->assertStringNotContainsString('Billing and Insurance', $text);
        $this->assertStringNotContainsString('claims, and reconciliation', $text);
    }

    public function test_the_orphaned_second_homepage_is_gone(): void
    {
        $this->get('/home2')->assertNotFound();
    }
}
