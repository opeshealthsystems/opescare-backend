<?php

namespace Tests\Feature\Certification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 2 functional certification: every parameter-less GET web route.
 *
 * For each route the actor the app itself admits (derived from middleware and
 * URI prefix by CertificationSupport::audience/roleFor — guest, patient,
 * pharmacist, lab, facility admin, platform admin, developer, health org)
 * must never get a 5xx. A route behind `auth` must not hand a guest a 200,
 * and the signed-in actor of the wrong audience must not get a 5xx either.
 */
class WebGetRouteCertificationTest extends TestCase
{
    use CertificationSupport;
    use RefreshDatabase;

    /** Audiences that are not session-web pages (API bearer, Horizon). */
    private const NOT_WEB = ['mobile', 'provider', 'integration', 'horizon'];

    public static function routes(): array
    {
        $out = [];
        foreach (self::routesFor(['GET']) as $key => [$method, $uri]) {
            if (str_contains($uri, '{') || str_starts_with($uri, 'api/') || str_starts_with($uri, '_')
                || str_starts_with($uri, 'sanctum') || str_starts_with($uri, 'horizon') || str_starts_with($uri, 'telescope')) {
                continue;
            }
            $out[$key] = [$method, $uri];
        }

        return $out;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    #[DataProvider('routes')]
    public function test_route_serves_its_audience_without_error(string $method, string $uri): void
    {
        $route    = $this->findRoute($method, $uri);
        $audience = $this->audience($route);
        if (in_array($audience, self::NOT_WEB, true)) {
            $this->markTestSkipped("{$audience} route, certified by the API suite");
        }

        $actor    = $this->actAs($audience, $route);
        $response = $this->get('/' . ltrim($uri, '/'));
        $status   = $response->getStatusCode();

        $this->record('web_get', ['route' => "{$method} /{$uri}", 'audience' => $audience, 'actor' => $actor, 'status' => $status]);

        $this->assertLessThan(500, $status, "{$method} /{$uri} as {$actor} returned {$status}: "
            . substr(strip_tags((string) $response->getContent()), 0, 400));
    }

    #[DataProvider('routes')]
    public function test_route_enforces_authentication_and_audience(string $method, string $uri): void
    {
        $route    = $this->findRoute($method, $uri);
        $audience = $this->audience($route);
        if ($audience === 'guest' || in_array($audience, self::NOT_WEB, true)) {
            $this->markTestSkipped('public or non-web route');
        }

        $guest = $this->get('/' . ltrim($uri, '/'))->getStatusCode();
        $this->assertContains($guest, [301, 302, 303, 401, 403, 404, 419],
            "guest reached authenticated {$method} /{$uri} with {$guest}");

        $wrong = $this->wrongActorFor($audience);
        if ($wrong === null) {
            return;
        }
        $this->actAsWrong($wrong);
        $status = $this->get('/' . ltrim($uri, '/'))->getStatusCode();

        $this->record('web_get_wrong_actor', ['route' => "{$method} /{$uri}", 'audience' => $audience, 'actor' => $wrong, 'status' => $status]);

        $this->assertLessThan(500, $status, "{$method} /{$uri} as wrong actor {$wrong} returned {$status}");
    }
}
