<?php
namespace App\Jobs;

use App\Models\WaitlistEntry;
use App\Modules\Appointments\Services\PatientSelfBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BackfillWaitlistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $providerId,
        public readonly string $facilityId,
        public readonly string $date,
    ) {}

    public function handle(PatientSelfBookingService $service): void
    {
        $entries = WaitlistEntry::where('provider_id', $this->providerId)
            ->where('facility_id', $this->facilityId)
            ->where('status', 'waiting')
            ->whereJsonContains('preferred_dates', $this->date)
            ->orderBy('created_at')
            ->take(3)
            ->get();

        foreach ($entries as $entry) {
            $entry->update(['status' => 'notified', 'notified_at' => now()]);
            Log::info('Waitlist backfill: notified patient', [
                'patient_id' => $entry->patient_id,
                'date'       => $this->date,
            ]);
        }
    }
}
