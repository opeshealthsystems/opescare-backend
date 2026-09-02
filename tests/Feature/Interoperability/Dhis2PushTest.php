<?php
namespace Tests\Feature\Interoperability;

use App\Jobs\PushPublicHealthToDhis2Job;
use App\Services\Interoperability\Dhis2PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Dhis2PushTest extends TestCase
{
    use RefreshDatabase;

    public function test_dhis2_push_service_formats_payload(): void
    {
        $service = new Dhis2PushService(
            baseUrl:  'https://dhis2.minsante.cm',
            username: 'test',
            password: 'test'
        );

        $payload = $service->buildPayload([
            'org_unit'     => 'Hôpital Central Yaoundé',
            'period'       => '202601',
            'data_element' => 'malaria_confirmed',
            'value'        => 42,
        ]);

        $this->assertArrayHasKey('dataValues', $payload);
        $this->assertEquals('malaria_confirmed', $payload['dataValues'][0]['dataElement']);
        $this->assertEquals(42, $payload['dataValues'][0]['value']);
    }

    public function test_dhis2_job_is_queued_with_correct_data(): void
    {
        Queue::fake();

        PushPublicHealthToDhis2Job::dispatch([
            'org_unit'     => 'OU-001',
            'period'       => '202601',
            'data_element' => 'cholera_suspected',
            'value'        => 5,
        ]);

        Queue::assertPushed(PushPublicHealthToDhis2Job::class, function ($job) {
            return $job->dataPoint['data_element'] === 'cholera_suspected';
        });
    }

    public function test_dhis2_push_fails_gracefully_on_error(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ERROR'], 400)]);

        $service = new Dhis2PushService('https://dhis2.minsante.cm', 'user', 'pass');

        $result = $service->push([
            'org_unit'     => 'OU-001',
            'period'       => '202601',
            'data_element' => 'malaria_confirmed',
            'value'        => 10,
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
