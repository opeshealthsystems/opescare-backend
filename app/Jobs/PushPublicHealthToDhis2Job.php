<?php
namespace App\Jobs;

use App\Services\Interoperability\Dhis2PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushPublicHealthToDhis2Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public readonly array $dataPoint) {}

    public function handle(): void
    {
        $service = new Dhis2PushService(
            baseUrl:  config('services.dhis2.url', 'https://dhis2.minsante.cm'),
            username: config('services.dhis2.username', ''),
            password: config('services.dhis2.password', ''),
        );

        $result = $service->push($this->dataPoint);

        if (!$result['success']) {
            Log::warning('DHIS2 push unsuccessful', ['result' => $result, 'data' => $this->dataPoint]);
        }
    }
}
