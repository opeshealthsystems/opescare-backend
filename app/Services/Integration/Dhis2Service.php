<?php

namespace App\Services\Integration;

use Illuminate\Support\Collection;
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
 * Env: DHIS2_BASE_URL, DHIS2_USERNAME, DHIS2_PASSWORD, DHIS2_ORG_UNIT, DHIS2_DATASET_ID, DHIS2_ENABLED
 *
 * IMPORTANT: This service sends aggregate counts ONLY — no patient-identifiable data is transmitted.
 */
class Dhis2Service
{
    private string  $baseUrl;
    private string  $username;
    private string  $password;
    private string  $orgUnit;
    private string  $datasetId;
    private bool    $enabled;
    private int     $timeout;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('dhis2.base_url', ''), '/');
        $this->username  = config('dhis2.username', '');
        $this->password  = config('dhis2.password', '');
        $this->orgUnit   = config('dhis2.org_unit', '');
        $this->datasetId = config('dhis2.dataset_id', '');
        $this->enabled   = (bool) config('dhis2.enabled', false);
        $this->timeout   = (int) config('dhis2.timeout', 30);
    }

    /**
     * Push a data value set to DHIS2.
     *
     * @param  string  $period   DHIS2 period format: YYYYMM (monthly), YYYY (yearly), YYYYWnn (weekly)
     * @param  array   $values   [{dataElement: 'UID', value: int|string, categoryOptionCombo?: 'UID'}]
     * @return array  {success: bool, imported: int, updated: int, ignored: int, status: string}
     */
    public function pushDataValues(string $period, array $values): array
    {
        if (!$this->enabled) {
            Log::info('DHIS2 push skipped — DHIS2_ENABLED=false', ['period' => $period]);
            return ['success' => false, 'reason' => 'DHIS2 disabled', 'period' => $period];
        }

        if (!$this->isConfigured()) {
            Log::warning('DHIS2 push skipped — not configured', ['period' => $period]);
            return ['success' => false, 'reason' => 'DHIS2 not configured'];
        }

        $payload = [
            'dataSet'   => $this->datasetId,
            'period'    => $period,
            'orgUnit'   => $this->orgUnit,
            'dataValues'=> array_map(fn($v) => array_filter([
                'dataElement'        => $v['dataElement'],
                'value'              => (string) $v['value'],
                'categoryOptionCombo'=> $v['categoryOptionCombo'] ?? null,
                'comment'            => $v['comment'] ?? null,
            ]), $values),
        ];

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withHeader('Content-Type', 'application/json')
                ->post("{$this->baseUrl}/api/dataValueSets", $payload);

            if ($response->ok() || $response->status() === 201) {
                $data = $response->json();
                $importCount = $data['importCount'] ?? [];

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
                    'status'   => $data['status'] ?? 'SUCCESS',
                ];
            }

            Log::error('DHIS2 push HTTP error', [
                'period' => $period,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'period'  => $period,
                'error'   => "HTTP {$response->status()}",
                'body'    => $response->json(),
            ];

        } catch (\Throwable $e) {
            Log::error('DHIS2 push exception', ['period' => $period, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Push monthly OPD (outpatient department) summary data.
     * Maps OpesCare visit counts to DHIS2 data elements.
     *
     * @param  string  $yearMonth  e.g. '2026-05'
     * @param  array   $counts     {opd_total, opd_male, opd_female, opd_under5,
     *                              malaria_confirmed, hypertension_new, diabetes_new,
     *                              maternal_visits, immunizations_given}
     * @return array
     */
    public function pushMonthlySummary(string $yearMonth, array $counts): array
    {
        // Convert YYYY-MM to DHIS2 period format YYYYMM
        $period = str_replace('-', '', $yearMonth);

        // Map OpesCare metrics to DHIS2 data element UIDs.
        // IMPORTANT: These UIDs must match the actual DHIS2 instance configuration.
        // They are placeholders — update in config/dhis2.php or .env once DHIS2 UIDs are known.
        $dataElementMap = config('dhis2.data_element_map', [
            'opd_total'         => 'OPD_TOTAL_DE',
            'opd_male'          => 'OPD_MALE_DE',
            'opd_female'        => 'OPD_FEMALE_DE',
            'opd_under5'        => 'OPD_UNDER5_DE',
            'malaria_confirmed' => 'MAL_CONFIRMED_DE',
            'hypertension_new'  => 'HYP_NEW_DE',
            'diabetes_new'      => 'DM_NEW_DE',
            'maternal_visits'   => 'ANC_VISITS_DE',
            'immunizations'     => 'IMM_GIVEN_DE',
        ]);

        $values = [];
        foreach ($counts as $key => $count) {
            if (isset($dataElementMap[$key]) && $dataElementMap[$key] !== '') {
                $values[] = [
                    'dataElement' => $dataElementMap[$key],
                    'value'       => (int) $count,
                ];
            }
        }

        return $this->pushDataValues($period, $values);
    }

    /**
     * Test DHIS2 connection by calling the /api/me endpoint.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['connected' => false, 'reason' => 'Not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get("{$this->baseUrl}/api/me");

            if ($response->ok()) {
                $user = $response->json();
                return [
                    'connected' => true,
                    'user'      => $user['userCredentials']['username'] ?? 'unknown',
                    'name'      => $user['name'] ?? null,
                ];
            }

            return ['connected' => false, 'error' => "HTTP {$response->status()}"];

        } catch (\Throwable $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl)
            && !empty($this->username)
            && !empty($this->password)
            && !empty($this->orgUnit)
            && !empty($this->datasetId);
    }
}
