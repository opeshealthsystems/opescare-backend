<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Generates a complete OpenAPI 3.1 spec from the live route table.
 *
 * Every documented path is, by construction, a real registered route — so the
 * spec can never drift or advertise a phantom endpoint (enforced redundantly by
 * OpenApiContractTest). Security schemes are derived from each route's gathered
 * middleware; tags from the path prefix. Run after adding/changing routes:
 *
 *   php artisan opescare:generate-openapi
 *
 * Hand-curated rich operations (full request/response schemas) live in
 * config/openapi.php under 'overrides', keyed "METHOD /full/path"; an override
 * replaces the generated stub for that operation.
 */
class GenerateOpenApi extends Command
{
    protected $signature = 'opescare:generate-openapi {--out=public/openapi.json}';
    protected $description = 'Generate a complete OpenAPI 3.1 spec from the live route table';

    public function handle(): int
    {
        $overrides = (array) config('openapi.overrides', []);
        $paths = [];
        $ops = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/') && ! str_starts_with($uri, '.well-known/')) {
                continue;
            }

            $path = '/' . preg_replace('/\{(\w+?)\??\}/', '{$1}', $uri);

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $key = $method . ' ' . $path;
                $paths[$path][strtolower($method)] = $overrides[$key]
                    ?? $this->buildOperation($route, $method, $uri, $path);
                $ops++;
            }
        }

        ksort($paths);

        $spec = [
            'openapi' => '3.1.0',
            'info'    => $this->specInfo($ops, count($paths)),
            'servers' => [
                ['url' => 'https://api.opescare.com', 'description' => 'Production (approved accounts)'],
                ['url' => 'https://sandbox-api.opescare.com', 'description' => 'Sandbox (cloud)'],
                ['url' => 'http://opescare.test', 'description' => 'Local dev'],
            ],
            'tags'       => $this->tags($paths),
            'components' => $this->components(),
            'paths'      => $paths,
        ];

        $out = base_path($this->option('out'));
        file_put_contents($out, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->info("Generated {$ops} operations across " . count($paths) . " paths -> {$out}");
        return self::SUCCESS;
    }

    private function buildOperation(RouteObject $route, string $method, string $uri, string $path): array
    {
        $action = $route->getActionName();
        $fn = Str::contains($action, '@') ? Str::afterLast($action, '@') : 'invoke';
        $ctrl = class_basename(Str::beforeLast($action, '@'));

        $op = [
            'tags'        => [$this->tag($uri)],
            'summary'     => Str::headline($fn),
            'operationId' => trim(str_replace('Controller', '', $ctrl) . '_' . $fn, '_'),
            'security'    => $this->security($route->gatherMiddleware()),
            'responses'   => $this->responses($method),
        ];

        $params = [];
        if (preg_match_all('/\{(\w+?)\??\}/', $uri, $m)) {
            foreach ($m[1] as $name) {
                $params[] = ['name' => $name, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']];
            }
        }
        if ($params) {
            $op['parameters'] = $params;
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $op['requestBody'] = [
                'required' => false,
                'content'  => ['application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]]],
            ];
        }

        return $op;
    }

    /** Map gathered middleware -> OpenAPI security requirement(s). */
    private function security(array $middleware): array
    {
        $mw = implode(' ', $middleware);

        $has = fn (string $needle) => Str::contains($mw, $needle);

        if ($has('VerifyIntegrationClient') || $has('verify.integration.client')) {
            return [['ClientId' => [], 'ClientSecret' => []]];
        }
        if ($has('VerifyBearerToken') || $has('auth.bearer')) {
            return [['BearerAuth' => []]];
        }
        if ($has('VerifySdkToken') || $has('sdk.token')) {
            return [['SdkToken' => []]];
        }
        if ($has('VerifyBridgeAgent') || $has('bridge.agent')) {
            return [['BridgeAgentKey' => []]];
        }
        if ($has('AuthenticateMobilePatient') || $has('auth.mobile')) {
            return [['MobileToken' => []]];
        }
        if ($has('RequireApiAdminRole') || $has('api.admin') || $has('Authenticate')) {
            return [['SessionAuth' => []]];
        }

        return []; // public
    }

    private function tag(string $uri): string
    {
        $u = Str::after($uri, 'api/');
        return match (true) {
            str_starts_with($uri, '.well-known/')      => 'Discovery',
            str_starts_with($u, 'fhir')                 => 'FHIR',
            str_starts_with($u, 'v1/connect')           => 'Connect',
            str_starts_with($u, 'v1/sdk')               => 'SDK',
            str_starts_with($u, 'v1/bridge')            => 'Bridge',
            str_starts_with($u, 'v1/lite')              => 'Lite',
            str_starts_with($u, 'v1/encounters')        => 'Encounters',
            str_starts_with($u, 'v1/admin')             => 'Admin',
            str_contains($uri, 'Mobile') || str_starts_with($u, 'mobile') => 'Mobile',
            str_starts_with($u, 'v1/')                  => Str::headline(Str::before(Str::after($u, 'v1/'), '/')) ?: 'API v1',
            default                                     => 'API',
        };
    }

    private function responses(string $method): array
    {
        $ok = in_array($method, ['POST'], true) ? '201' : '200';
        $errorRef = ['$ref' => '#/components/schemas/Error'];
        $json = fn ($schema) => ['content' => ['application/json' => ['schema' => $schema]]];

        return [
            $ok    => array_merge(['description' => 'Success'], $json(['type' => 'object'])),
            '400'  => array_merge(['description' => 'Bad request'], $json($errorRef)),
            '401'  => array_merge(['description' => 'Unauthenticated'], $json($errorRef)),
            '403'  => array_merge(['description' => 'Forbidden'], $json($errorRef)),
            '422'  => array_merge(['description' => 'Validation failed'], $json($errorRef)),
            '500'  => array_merge(['description' => 'Server error'], $json($errorRef)),
        ];
    }

    private function specInfo(int $ops, int $paths): array
    {
        return [
            'title'   => 'OpesCare API',
            'version' => '1.2.0',
            'description' => "OpesCare's full REST API surface — Connect interoperability, SDK, "
                . "Bridge agent, FHIR R4, clinical, and platform endpoints.\n\n"
                . "**Auto-generated** from the live route table by `php artisan opescare:generate-openapi` "
                . "({$ops} operations across {$paths} paths). Every path is therefore a real registered "
                . "route — the spec cannot drift (enforced by OpenApiContractTest). Security is derived "
                . "from each route's middleware; request/response bodies are generic stubs except for "
                . "the hand-curated operations in config/openapi.php (full schemas).",
            'contact' => ['name' => 'OpesCare Developer Support', 'email' => 'developer-support@opescare.com'],
            'license' => ['name' => 'Proprietary'],
        ];
    }

    private function tags(array $paths): array
    {
        $names = [];
        foreach ($paths as $ops) {
            foreach ($ops as $op) {
                foreach (($op['tags'] ?? []) as $t) {
                    $names[$t] = true;
                }
            }
        }
        ksort($names);
        return array_map(fn ($n) => ['name' => $n], array_keys($names));
    }

    private function components(): array
    {
        return [
            'securitySchemes' => [
                'BearerAuth'     => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT', 'description' => 'RS256 JWT from POST /api/v1/connect/auth/token (client_credentials).'],
                'ClientId'       => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Client-ID', 'description' => 'B2B integration client id (send with X-Client-Secret).'],
                'ClientSecret'   => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Client-Secret', 'description' => 'B2B integration client secret (Argon2id verified).'],
                'SdkToken'       => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'SDK bearer token (sdk.token scopes).'],
                'BridgeAgentKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Bridge-Agent-Key', 'description' => 'On-prem Bridge Agent key.'],
                'MobileToken'    => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Opaque mobile patient token (pat_...).'],
                'SessionAuth'    => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'opescare_session', 'description' => 'Authenticated portal session.'],
            ],
            'schemas' => array_merge([
                'Error' => [
                    'type' => 'object',
                    'properties' => [
                        'status'     => ['type' => 'string', 'example' => 'error'],
                        'error_code' => ['type' => 'string', 'example' => 'VALIDATION_FAILED'],
                        'message'    => ['type' => 'string'],
                    ],
                ],
            ], (array) config('openapi.schemas', [])),
        ];
    }
}
