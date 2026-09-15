<?php

namespace App\Services\Integration;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DHIS2 Integration Service — MINSANTE Cameroon
 *
 * Pushes aggregate public health data to the Ministry of Health (MINSANTE)
 * DHIS2 instance via the DHIS2 Web API (Data Value Sets).
 *
 * Docs: https://docs.dhis2.org/en/develop/using-the-api/dhis-core-version-240/data.html
 *
 * Env: DHIS2_BASE_URL, DHIS2_USERNAME + DHIS2_PASSWORD or DHIS2_TOKEN, DHIS2_DATASET_ID
 *
 * IMPORTANT: This service sends aggregate counts ONLY — no patient-identifiable data is transmitted.
 *
 * Imported from the platform; Finder adaptations:
 *  - the org unit is passed per call (one per facility, from dhis2_org_unit_mappings)
 *    instead of the single DHIS2_ORG_UNIT;
 *  - pushMonthlySummary() and its placeholder data element UIDs ('OPD_TOTAL_DE', ...)
 *    were removed: an invented UID must never reach the ministry. Values are built by
 *    App\Services\Interoperability\Dhis2PushService from an approved draft;
 *  - DHIS2_ENABLED removed: the platform admin's approval is the gate;
 *  - token auth added; testConnection() calls /api/system/info instead of /api/me;
 *  - credentials and response bodies are never logged.
 */
class Dhis2Service
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $token;
    private string $datasetId;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('dhis2.base_url', ''), '/');
        $this->username  = (string) config('dhis2.username', '');
        $this->password  = (string) config('dhis2.password', '');
        $this->token     = (string) config('dhis2.token', '');
        $this->datasetId = (string) config('dhis2.dataset_id', '');
        $this->timeout   = (int) config('dhis2.timeout', 30);
    }

    /**
     * Push a data value set to DHIS2.
     *
     * @param  string  $orgUnit  DHIS2 orgUnit UID of the facility
     * @param  string  $period   DHIS2 period format: YYYYMM (monthly)
     * @param  array   $values   [{dataElement: 'UID', value: int|string, categoryOptionCombo?: 'UID'}]
     * @return array  {success: bool, imported: int, updated: int, ignored: int, status: string, summary: ?array}
     */
    public function pushDataValues(string $orgUnit, string $period, array $values): array
    {
        if (! $this->isConfigured()) {
            Log::warning('DHIS2 push skipped — not configured', ['period' => $period]);

            return ['success' => false, 'error' => 'DHIS2 not configured', 'summary' => null];
        }

        try {
            $response = $this->client($this->timeout)
                ->post("{$this->baseUrl}/api/dataValueSets", $this->buildPayload($orgUnit, $period, $values));

            $data = $response->json() ?? [];
            // DHIS2 2.38+ wraps the import summary in `response`.
            $summary = is_array($data['response'] ?? null) ? $data['response'] : $data;
            $importCount = $summary['importCount'] ?? [];
            $status = $summary['status'] ?? ($data['status'] ?? null);

            if ($response->successful() && $status !== 'ERROR') {
                Log::info('DHIS2 data push successful', [
                    'period'   => $period,
                    'imported' => $importCount['imported'] ?? 0,
                    'updated'  => $importCount['updated'] ?? 0,
                    'ignored'  => $importCount['ignored'] ?? 0,
                ]);

                return [
                    'success'  => true,
                    'period'   => $period,
                    'imported' => (int) ($importCount['imported'] ?? 0),
                    'updated'  => (int) ($importCount['updated'] ?? 0),
                    'ignored'  => (int) ($importCount['ignored'] ?? 0),
                    'status'   => $status ?? 'SUCCESS',
                    'summary'  => $this->safeSummary($summary, $response->status()),
                ];
            }

            Log::error('DHIS2 push HTTP error', ['period' => $period, 'status' => $response->status()]);

            return [
                'success' => false,
                'period'  => $period,
                'error'   => "HTTP {$response->status()}",
                'summary' => $this->safeSummary($summary, $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error('DHIS2 push exception', ['period' => $period, 'error' => class_basename($e)]);

            return ['success' => false, 'error' => class_basename($e), 'summary' => null];
        }
    }

    public function buildPayload(string $orgUnit, string $period, array $values): array
    {
        $payload = [
            'period'     => $period,
            'orgUnit'    => $orgUnit,
            'dataValues' => array_map(fn ($v) => array_filter([
                'dataElement'         => $v['dataElement'],
                'value'               => (string) $v['value'],
                'categoryOptionCombo' => $v['categoryOptionCombo'] ?? null,
            ], fn ($x) => $x !== null && $x !== ''), array_values($values)),
        ];

        if ($this->datasetId !== '') {
            $payload = ['dataSet' => $this->datasetId] + $payload;
        }

        return $payload;
    }

    /**
     * Test DHIS2 connection by calling /api/system/info.
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['connected' => false, 'error' => 'Not configured'];
        }

        try {
            $response = $this->client(10)->get("{$this->baseUrl}/api/system/info");

            if ($response->ok()) {
                $info = $response->json() ?? [];

                return [
                    'connected' => true,
                    'version'   => $info['version'] ?? null,
                    'system'    => $info['systemName'] ?? null,
                ];
            }

            return ['connected' => false, 'error' => "HTTP {$response->status()}"];
        } catch (\Throwable $e) {
            return ['connected' => false, 'error' => class_basename($e)];
        }
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== ''
            && ($this->token !== '' || ($this->username !== '' && $this->password !== ''));
    }

    private function client(int $timeout): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->timeout($timeout);

        return $this->token !== ''
            ? $request->withHeaders(['Authorization' => 'ApiToken ' . $this->token])
            : $request->withBasicAuth($this->username, $this->password);
    }

    /** Keep only DHIS2's status, counts and conflicts — never headers or credentials. */
    private function safeSummary(array $summary, int $httpStatus): array
    {
        return [
            'http_status' => $httpStatus,
            'status'      => $summary['status'] ?? null,
            'importCount' => $summary['importCount'] ?? null,
            'conflicts'   => array_slice((array) ($summary['conflicts'] ?? []), 0, 20),
            'description' => isset($summary['description']) ? mb_substr((string) $summary['description'], 0, 500) : null,
        ];
    }
}
