<?php
namespace App\Services\Interoperability;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Dhis2PushService
{
    public function __construct(
        private string $baseUrl,
        private string $username,
        private string $password,
    ) {}

    public function buildPayload(array $dataPoint): array
    {
        return [
            'dataValues' => [[
                'dataElement' => $dataPoint['data_element'],
                'period'      => $dataPoint['period'],
                'orgUnit'     => $dataPoint['org_unit'],
                'value'       => $dataPoint['value'],
            ]],
        ];
    }

    public function push(array $dataPoint): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->post("{$this->baseUrl}/api/dataValueSets", $this->buildPayload($dataPoint));

            if ($response->successful() && $response->json('status') !== 'ERROR') {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json('description', 'Unknown DHIS2 error')];
        } catch (\Exception $e) {
            Log::error('DHIS2 push failed', ['error' => $e->getMessage(), 'data' => $dataPoint]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
