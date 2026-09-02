<?php

namespace Tests\Feature\Analytics;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Modules\Analytics\Services\ProductAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * computeDailySnapshots() must survive a facility that has appointments.
 *
 * It did not. computeAppointmentNoShowRate() filtered on `appointment_date`,
 * a column that has never existed on `appointments` — the real column is
 * `scheduled_at`, and there was no accessor bridging the two. Every call threw
 * SQLSTATE[42703], which surfaced as a 500 on /portals/admin/kpi for every
 * platform admin, on every request.
 *
 * Nothing caught it because no test exercised the snapshot path with an
 * appointment row present: with zero appointments the query still throws, but
 * no test called it at all. The KPI page is not feature-frozen, so this was
 * live.
 */
class ProductAnalyticsSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_snapshots_compute_for_a_facility_with_appointments(): void
    {
        $facility = Facility::factory()->create();
        $patient  = Patient::factory()->create();

        // A kept appointment and a no-show, both inside the window.
        foreach ([['completed', 3], ['no_show', 2]] as [$status, $daysAgo]) {
            Appointment::factory()->create([
                'patient_id'   => $patient->id,
                'facility_id'  => $facility->id,
                'scheduled_at' => now()->subDays($daysAgo),
                'status'       => $status,
            ]);
        }

        $service = app(ProductAnalyticsService::class);
        $service->seedCoreMetrics();

        // The bug made this throw rather than return.
        $service->computeDailySnapshots($facility->id, Carbon::today());

        $snapshots = $service->latestDailySnapshots($facility->id);

        $this->assertNotNull($snapshots, 'snapshot computation returned nothing');
    }

    public function test_the_appointments_table_has_no_appointment_date_column(): void
    {
        // Pins the fact the bug depended on. If someone adds an
        // `appointment_date` column later, this fails loudly and the two
        // spellings get reconciled deliberately instead of by accident.
        $this->assertFalse(
            Schema::hasColumn('appointments', 'appointment_date'),
            'appointments.appointment_date exists again — reconcile it with scheduled_at'
        );
        $this->assertTrue(Schema::hasColumn('appointments', 'scheduled_at'));
    }
}
