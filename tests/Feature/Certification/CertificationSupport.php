<?php

namespace Tests\Feature\Certification;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Shared machinery for the functional certification suites (Phase 2 of
 * docs/superpowers/plans/2026-10-18-launch-certification-plan.md).
 *
 * Who may open a route is derived from its middleware and URI prefix, the
 * same inputs the app itself uses: auth, the portal.access buckets in
 * EnsurePortalAccess, capability: from config/staff_capabilities.php,
 * platform.admin, auth.mobile, auth.provider. Fixtures are built per case
 * inside the RefreshDatabase transaction.
 *
 * Every case appends one JSON line to storage/framework/testing/certification/
 * so scripts/certification-matrix.php can write docs/certification/FUNCTIONAL_MATRIX.md.
 */
trait CertificationSupport
{
    /** A UUID that names no row anywhere. */
    public const GHOST_UUID = '00000000-0000-4000-8000-0000000000ff';

    protected ?Facility $certFacility = null;

    protected ?Patient $certPatient = null;

    /** Boot a throwaway app so a static data provider can read the route table. */
    protected static function bootForProvider(): \Illuminate\Foundation\Application
    {
        static $app = null;
        if ($app === null) {
            $app = require __DIR__ . '/../../../bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        }

        return $app;
    }

    /** @return array<string, array{0:string,1:string}> "METHOD /uri" => [method, uri] */
    protected static function routesFor(array $methods): array
    {
        $app = self::bootForProvider();
        $out = [];
        foreach ($app['router']->getRoutes() as $route) {
            $m = array_values(array_intersect($methods, $route->methods()));
            if ($m === []) {
                continue;
            }
            $method = $m[0];
            // Route::any() catch-alls are listed once, as their GET.
            if (count($route->methods()) > 5 && $method !== 'GET') {
                continue;
            }
            $out[$method . ' /' . ltrim($route->uri(), '/')] = [$method, $route->uri()];
        }
        ksort($out);

        return $out;
    }

    protected function findRoute(string $method, string $uri): Route
    {
        foreach (app('router')->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }
        $this->fail("route {$method} {$uri} vanished");
    }

    /** @return string[] */
    protected function middlewareNames(Route $route): array
    {
        return array_map(fn ($m) => is_string($m) ? $m : '', $route->gatherMiddleware());
    }

    /** @return string[] capability names required by `capability:a,b` (any-of) */
    protected function capabilities(Route $route): array
    {
        foreach ($this->middlewareNames($route) as $m) {
            if (str_starts_with($m, 'capability:')) {
                return explode(',', substr($m, 11));
            }
        }

        return [];
    }

    /**
     * Classify the route's audience:
     * guest | patient | staff | pharmacy | admin | developer | healthorg | mobile | provider | integration | horizon
     */
    protected function audience(Route $route): string
    {
        $mw = ' ' . implode(' ', $this->middlewareNames($route)) . ' ';

        if (str_contains($mw, 'Horizon')) {
            return 'horizon';
        }
        if (str_contains($mw, 'VerifyIntegrationClient') || str_contains($mw, 'verify.integration.client')
            || str_contains($mw, ' auth.bearer') || str_contains($mw, ' api.admin') || str_contains($mw, ' sdk.token')) {
            return 'integration';
        }
        if (str_contains($mw, ' auth.provider')) {
            return 'provider';
        }
        if (str_contains($mw, ' auth.mobile') || str_contains($mw, 'AuthenticateMobilePatient')) {
            return 'mobile';
        }
        if (! preg_match('/ auth(:[^ ]*)? /', $mw)) {
            return 'guest';
        }
        $path = ltrim($route->uri(), '/');

        return match (true) {
            str_starts_with($path, 'admin'), str_starts_with($path, 'portals/admin') => 'admin',
            str_starts_with($path, 'patient'), str_starts_with($path, 'portals/patient'),
            str_starts_with($path, 'notifications'), str_starts_with($path, 'family') => 'patient',
            str_starts_with($path, 'developer'), str_starts_with($path, 'portals/developer') => 'developer',
            str_starts_with($path, 'healthorg') => 'healthorg',
            str_starts_with($path, 'pharmacy') => 'pharmacy',
            default => 'staff',
        };
    }

    protected function seedRoles(): void
    {
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    protected function facilityOfType(string $type): Facility
    {
        return $this->certFacility ??= Facility::factory()->create([
            'type' => $type, 'is_demo' => (bool) config('demo.enabled'),
        ]);
    }

    protected function fixturePatient(): Patient
    {
        return $this->certPatient ??= Patient::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
        ]);
    }

    /** The role that should succeed: holds the capability and sits in the portal bucket. */
    protected function roleFor(Route $route, string $audience): string
    {
        $caps   = $this->capabilities($route);
        $bucket = $this->portalBucket(ltrim($route->uri(), '/'));
        $map    = config('staff_capabilities.capabilities', []);

        $preferred = $audience === 'pharmacy'
            ? ['pharmacy_manager', 'pharmacist']
            : ['hospital_admin', 'doctor', 'nurse', 'receptionist', 'pharmacy_manager', 'lab_manager', 'radiologist', 'cashier', 'data_steward'];

        if ($caps !== []) {
            $candidates = [];
            foreach ($caps as $cap) {
                array_push($candidates, ...($map[$cap] ?? []));
            }
        } else {
            $candidates = $bucket ?? $preferred;
        }
        if ($bucket !== null) {
            $candidates = array_values(array_intersect($candidates, $bucket));
        }
        // On the operations desk, a clinician before an administrator.
        if (str_starts_with(ltrim($route->uri(), '/'), 'facility') && $caps === []) {
            array_unshift($preferred, 'doctor');
        }
        foreach ($preferred as $p) {
            if (in_array($p, $candidates, true)) {
                return $p;
            }
        }

        return $candidates[0] ?? 'doctor';
    }

    /** @return string[]|null the EnsurePortalAccess bucket for a path */
    protected function portalBucket(string $path): ?array
    {
        $ref = new \ReflectionClassConstant(\App\Http\Middleware\EnsurePortalAccess::class, 'PORTAL_ROLES');
        foreach ($ref->getValue() as $prefix => $roles) {
            if (str_starts_with($path, $prefix)) {
                return $roles;
            }
        }

        return null;
    }

    protected function facilityTypeForRole(string $role): string
    {
        return match (true) {
            str_contains($role, 'pharm') || in_array($role, ['medicine_stock', 'dispensing_officer'], true) => 'pharmacy',
            str_starts_with($role, 'lab') || $role === 'sample_collection' => 'laboratory',
            in_array($role, ['radiologist', 'imaging_tech'], true) => 'diagnostic_center',
            default => 'hospital',
        };
    }

    protected function staffUser(string $roleName, ?string $facilityType = null): User
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = $this->facilityOfType($facilityType ?? $this->facilityTypeForRole($roleName));
        $user     = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
            'is_demo' => (bool) config('demo.enabled'),
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return $user;
    }

    protected function platformAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null, 'is_demo' => (bool) config('demo.enabled')]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    protected function patientUser(string $roleName = 'patient'): User
    {
        $patient = $this->fixturePatient();
        $user = User::factory()->create([
            'status' => 'active', 'is_demo' => (bool) config('demo.enabled'),
            'role_id' => Role::where('name', $roleName)->value('id'),
        ]);
        $user->forceFill(['patient_id' => $patient->id])->save();

        return $user;
    }

    protected function simpleRoleUser(string $roleName): User
    {
        return User::factory()->create([
            'status' => 'active', 'is_demo' => (bool) config('demo.enabled'), 'primary_facility_id' => null,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ]);
    }

    protected function patientBearer(): string
    {
        $raw = 'pat_' . Str::random(40);
        \App\Models\PatientAccessToken::create([
            'patient_id'   => $this->fixturePatient()->id,
            'token_hash'   => \Illuminate\Support\Facades\Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12),
            'expires_at'   => now()->addHours(24),
        ]);

        return $raw;
    }

    protected function providerBearer(string $role): string
    {
        $user = $this->staffUser($role);

        return app(\App\Services\Portal\ProviderSessionService::class)->mint($user, $this->certFacility->id, 'certification');
    }

    /** Sign in the right actor for the audience. Returns the actor label for the matrix. */
    protected function actAs(string $audience, Route $route): string
    {
        switch ($audience) {
            case 'guest':
                return 'guest';
            case 'admin':
                $this->actingAs($this->platformAdmin())->withSession(['mfa.verified' => true]);

                return 'super_admin';
            case 'patient':
                $this->actingAs($this->patientUser())->withSession(['mfa.verified' => true]);

                return 'patient';
            case 'developer':
                $this->actingAs($this->simpleRoleUser('developer'))->withSession(['mfa.verified' => true]);

                return 'developer';
            case 'healthorg':
                $this->actingAs($this->simpleRoleUser('ngo_admin'))->withSession(['mfa.verified' => true]);

                return 'ngo_admin';
            case 'mobile':
                $this->withHeaders(['Authorization' => 'Bearer ' . $this->patientBearer(), 'Accept' => 'application/json']);

                return 'patient(app)';
            case 'provider':
                $role = $this->roleFor($route, 'staff');
                $this->withHeaders(['Authorization' => 'Bearer ' . $this->providerBearer($role), 'Accept' => 'application/json']);

                return $role . '(app)';
            default: // staff / pharmacy
                $role = $this->roleFor($route, $audience);
                $user = $this->staffUser($role);
                $this->actingAs($user)->withSession(['active_facility_id' => $this->certFacility->id, 'mfa.verified' => true]);

                return $role;
        }
    }

    /** A signed-in actor who must NOT get this audience's pages. */
    protected function wrongActorFor(string $audience): ?string
    {
        return match ($audience) {
            'admin'                          => 'doctor',
            'staff', 'pharmacy', 'developer' => 'patient',
            'patient'                        => 'doctor',
            'provider'                       => 'patient-bearer',
            default                          => null,
        };
    }

    protected function actAsWrong(string $who): void
    {
        match ($who) {
            'patient'        => $this->actingAs($this->patientUser())->withSession(['mfa.verified' => true]),
            'patient-bearer' => $this->withHeaders(['Authorization' => 'Bearer ' . $this->patientBearer(), 'Accept' => 'application/json']),
            default          => $this->actingAs($u = $this->staffUser($who))
                ->withSession(['active_facility_id' => $this->certFacility->id, 'mfa.verified' => true]),
        };
    }

    /** Fill route parameters with real fixture rows where the binding names a model. */
    protected function resolveUri(Route $route): string
    {
        $uri = $route->uri();
        $typed = [];
        foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $p) {
            $t = $p->getType();
            if ($t instanceof \ReflectionNamedType) {
                $typed[$p->getName()] = $t->getName();
            }
        }
        foreach ($route->parameterNames() as $name) {
            $value = $this->paramValue($name, $typed[$name] ?? null);
            $uri = preg_replace('/\{' . preg_quote($name, '/') . '\??\}/', rawurlencode((string) $value), $uri);
        }

        return '/' . ltrim($uri, '/');
    }

    protected function paramValue(string $name, ?string $class): string
    {
        $l = strtolower($name);
        if ($class && is_subclass_of($class, Model::class)) {
            $model = $this->fixtureRow($class);

            return $model ? (string) $model->getRouteKey() : self::GHOST_UUID;
        }

        return match (true) {
            $l === 'page'                                    => '1',
            in_array($l, ['locale', 'lang'], true)           => 'fr',
            str_contains($l, 'health') || $l === 'hid'       => (string) $this->fixturePatient()->health_id,
            in_array($l, ['patient', 'patientid', 'patient_id'], true)    => (string) $this->fixturePatient()->id,
            in_array($l, ['facility', 'facilityid', 'facility_id'], true) => (string) $this->facilityOfType('hospital')->id,
            $l === 'path'                                    => 'missing.txt',
            default                                          => self::GHOST_UUID,
        };
    }

    protected function fixtureRow(string $class): ?Model
    {
        if ($class === Patient::class) {
            return $this->fixturePatient();
        }
        if ($class === Facility::class) {
            return $this->facilityOfType('hospital');
        }
        try {
            $existing = $class::query()->first();
            if ($existing) {
                return $existing;
            }
            if (! method_exists($class, 'factory')) {
                return null;
            }
            $table = (new $class())->getTable();
            $attrs = [];
            if ($this->certFacility && Schema::hasColumn($table, 'facility_id')) {
                $attrs['facility_id'] = $this->certFacility->id;
            }
            if (Schema::hasColumn($table, 'patient_id')) {
                $attrs['patient_id'] = $this->fixturePatient()->id;
            }

            return DB::transaction(fn () => $class::factory()->create($attrs));
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return string[] */
    protected function langNamespaces(): array
    {
        static $ns = null;

        return $ns ??= array_map(fn ($f) => basename($f, '.php'), glob(lang_path('en') . '/*.php'));
    }

    /** @return string[] raw translation keys visible in the page text */
    protected function leakedKeys(string $html): array
    {
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags((string) $text));
        $ns = implode('|', array_map(fn ($n) => preg_quote($n, '/'), $this->langNamespaces()));
        preg_match_all('/(?<![\w.\/@-])(?:' . $ns . ')\.[a-z0-9_]+(?:\.[a-z0-9_]+)*(?![\w\/@-])/', $text, $m);

        return array_values(array_unique($m[0]));
    }

    /** @return string[] internal hrefs on the page that match no route */
    protected function deadLinks(string $html): array
    {
        preg_match_all('/\shref="([^"]+)"/i', $html, $m);
        $dead = [];
        $routes = app('router')->getRoutes();
        foreach (array_unique($m[1]) as $href) {
            $href = html_entity_decode($href);
            if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?(/.*)?$#', $href, $mm)) {
                $href = ($mm[3] ?? '') ?: '/';
            }
            if ($href === '' || $href[0] !== '/' || str_starts_with($href, '//')) {
                continue;
            }
            $path = parse_url($href, PHP_URL_PATH) ?: '/';
            if (preg_match('#^/(build|css|js|img|images|fonts|vendor|storage|favicon|icons|brand|assets)(/|$)#', $path)
                || preg_match('/\.(css|js|png|jpe?g|svg|ico|webp|woff2?|json|xml|txt|webmanifest|pdf)$/i', $path)) {
                continue;
            }
            if (str_contains($path, '{') || str_contains($path, '%7B')) {
                $dead[] = $href . ' (unfilled placeholder)';
                continue;
            }
            try {
                $routes->match(Request::create($path, 'GET'));
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                $dead[] = $href;
            }
        }

        return $dead;
    }

    protected function record(string $suite, array $row): void
    {
        $dir = storage_path('framework/testing/certification');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        file_put_contents($dir . "/{$suite}.jsonl", json_encode($row, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    }
}
