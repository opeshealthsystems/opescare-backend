<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates a Postman v2.1 collection from public/openapi.json so developers can
 * import the entire API into Postman in one click. Run after generating the
 * OpenAPI spec: `php artisan opescare:generate-openapi && php artisan opescare:generate-postman`.
 */
class GeneratePostman extends Command
{
    protected $signature = 'opescare:generate-postman {--out=public/opescare.postman_collection.json}';
    protected $description = 'Generate a Postman v2.1 collection from public/openapi.json';

    private const VERBS = ['get', 'post', 'put', 'patch', 'delete'];

    public function handle(): int
    {
        $specPath = public_path('openapi.json');
        if (! file_exists($specPath)) {
            $this->error('public/openapi.json not found — run `php artisan opescare:generate-openapi` first.');
            return self::FAILURE;
        }

        $spec = json_decode(file_get_contents($specPath), true);
        $byTag = [];

        foreach (($spec['paths'] ?? []) as $path => $ops) {
            foreach ($ops as $method => $op) {
                if (! in_array($method, self::VERBS, true)) {
                    continue;
                }
                $tag = $op['tags'][0] ?? 'API';
                $byTag[$tag][] = $this->request($method, $path, $op);
            }
        }
        ksort($byTag);

        $items = [];
        foreach ($byTag as $tag => $reqs) {
            $items[] = ['name' => $tag, 'item' => $reqs];
        }

        $collection = [
            'info' => [
                'name'        => 'OpesCare API',
                '_postman_id' => 'opescare-api-collection',
                'description' => 'Auto-generated from openapi.json. Set the collection variables: {{baseUrl}}, {{bearerToken}} (Connect JWT), {{clientId}} / {{clientSecret}} (B2B header auth).',
                'schema'      => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item'     => $items,
            'variable' => [
                ['key' => 'baseUrl', 'value' => 'https://api.opescare.com'],
                ['key' => 'bearerToken', 'value' => ''],
                ['key' => 'clientId', 'value' => ''],
                ['key' => 'clientSecret', 'value' => ''],
            ],
        ];

        $out = base_path($this->option('out'));
        file_put_contents($out, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->info('Postman collection generated: ' . $out . ' (' . count($items) . ' folders).');
        return self::SUCCESS;
    }

    private function request(string $method, string $path, array $op): array
    {
        $rawPath = ltrim($path, '/');
        $headers = [];

        $security = $op['security'][0] ?? [];
        if (array_key_exists('BearerAuth', $security)) {
            $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{bearerToken}}'];
        }
        if (array_key_exists('ClientId', $security)) {
            $headers[] = ['key' => 'X-Client-ID', 'value' => '{{clientId}}'];
            $headers[] = ['key' => 'X-Client-Secret', 'value' => '{{clientSecret}}'];
        }

        $request = [
            'method' => strtoupper($method),
            'header' => $headers,
            'url'    => [
                'raw'  => '{{baseUrl}}/' . $rawPath,
                'host' => ['{{baseUrl}}'],
                'path' => explode('/', $rawPath),
            ],
        ];

        if (in_array($method, ['post', 'put', 'patch'], true) && isset($op['requestBody'])) {
            $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            $request['body'] = ['mode' => 'raw', 'raw' => "{\n}", 'options' => ['raw' => ['language' => 'json']]];
        }

        return [
            'name'    => $op['summary'] ?? (strtoupper($method) . ' ' . $path),
            'request' => $request,
        ];
    }
}
