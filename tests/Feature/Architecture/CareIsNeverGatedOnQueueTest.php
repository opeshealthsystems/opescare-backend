<?php

namespace Tests\Feature\Architecture;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\Role;
use App\Models\TriageRecord;
use App\Models\User;
use App\Services\Triage\TriageDeskService;
use App\Support\TriageAcuity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Nobody is ever turned away, delayed or refused a consultation for not
 * holding a queue ticket.
 *
 * Sibling of CareIsNeverGatedOnPaymentTest and CareIsNeverGatedOnPreconsultTest,
 * for the same reason: the failure mode is not a decision anyone would defend
 * out loud, it is a routine-looking line. A `->whereHas('queueTicket')` on the
 * triage desk, a "no ticket" guard on the prescriber desk, a consultation
 * form that refuses to open until the patient was called — each turns a
 * waiting-room convenience into a condition of care.
 *
 * The queue is a record of who is waiting. It is never a permission to be
 * seen. The people this protects are the ones who walked past the desk: the
 * unconscious, the illiterate, the one carried in by a stranger, the one who
 * arrived while the receptionist was at lunch.
 *
 * If this test ever fails, the right response is almost certainly to remove
 * the gate, not to amend the test.
 */
class CareIsNeverGatedOnQueueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The ONLY files under app/ allowed to name the queue tables or models in
     * code. Everything else — every controller that consults, triages,
     * prescribes, dispenses, orders or results — must be unable to ask
     * whether a patient has a ticket, because it cannot spell the table.
     *
     * The dashboard tile is allowed: it counts, it does not gate.
     */
    private const QUEUE_AWARE_FILES = [
        'app/Services/Queue/PatientQueueService.php',
        'app/Services/Queue/QueueRefusal.php',
        'app/Http/Controllers/MedicalId/QueueDeskController.php',
        'app/Http/Controllers/MedicalId/StaffPortalController.php',
        'app/Models/Queue.php',
        'app/Models/QueueTicket.php',
        'app/Models/QueueStatusHistory.php',
        'app/Models/QueueTransfer.php',
        'app/Models/QueueStation.php',
        'app/Models/QueuePriorityRule.php',
        'app/Models/QueueDisplaySetting.php',
        'app/Models/FacilityQueue.php',
        'app/Models/AppointmentCheckIn.php',
    ];

    /** Structural: no clinical code can spell the queue. */
    public function test_only_the_queue_feature_names_the_queue_tables_in_code(): void
    {
        $offences = [];
        $needles  = ['queue_tickets', 'QueueTicket', 'queue_status_histories', 'QueueStatusHistory', 'PatientQueueService'];

        foreach ($this->phpFilesUnder(base_path('app')) as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen(base_path()) + 1));

            if (in_array($relative, self::QUEUE_AWARE_FILES, true)) {
                continue;
            }

            $code = $this->stripComments((string) file_get_contents($path));

            foreach ($needles as $needle) {
                if (str_contains($code, $needle)) {
                    $offences[] = sprintf('%s names %s', $relative, $needle);
                }
            }
        }

        $this->assertSame(
            [],
            $offences,
            "A file outside the queue feature can read a queue ticket, which is the first step to gating care on one.\n"
            . "If it only counts or links, add it to QUEUE_AWARE_FILES with a reason; if it decides anything, remove the read.\n"
            . 'Offenders: ' . implode(', ', $offences)
        );
    }

    /**
     * Structural, the other direction: no clinical table points AT a ticket.
     * A `queue_ticket_id` on visits, triage_records or consultations would be
     * the column a NOT NULL constraint turns into a gate.
     */
    public function test_no_clinical_table_carries_a_queue_ticket_column(): void
    {
        $offences = [];

        foreach (['visits', 'triage_records', 'consultations', 'prescriptions', 'lab_orders', 'diagnoses', 'vital_signs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getColumnListing($table) as $column) {
                if (str_contains($column, 'queue') || str_contains($column, 'ticket')) {
                    $offences[] = "{$table}.{$column}";
                }
            }
        }

        $this->assertSame([], $offences, 'A clinical table gained a queue column: ' . implode(', ', $offences));
    }

    /**
     * Behavioural: a patient with no ticket, no appointment and no phone is
     * triaged exactly as anyone else — the visit opens, the record is written,
     * and the queue never learns of it unless someone chooses to check them in.
     */
    public function test_a_patient_with_no_ticket_is_triaged_and_seen_exactly_the_same(): void
    {
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $role     = Role::where('name', 'triage_nurse')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $nurse    = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create(['user_id' => $nurse->id, 'facility_id' => $facility->id, 'role_id' => $role->id, 'is_active' => true, 'assigned_at' => now()]);

        $patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled'), 'phone_number' => null, 'phone_number_hash' => null]);

        $this->assertSame(0, QueueTicket::where('patient_id', $patient->id)->count());

        $this->actingAs($nurse)->withSession(['active_facility_id' => $facility->id])
            ->post('/facility/triage', [
                'patient_id'           => $patient->id,
                'presenting_complaint' => 'Carried in, unresponsive',
                'acuity'               => TriageAcuity::P1,
            ])
            ->assertRedirect();

        $record = TriageRecord::latest('created_at')->firstOrFail();
        $this->assertSame(TriageAcuity::P1, app(TriageDeskService::class)->currentPriority($record));
        $this->assertDatabaseHas('visits', ['id' => $record->visit_id, 'patient_id' => $patient->id, 'facility_id' => $facility->id, 'status' => 'open']);

        // Still no ticket. Care happened; the queue was not consulted.
        $this->assertSame(0, QueueTicket::where('patient_id', $patient->id)->count());
    }

    /**
     * And the queue itself never drops anyone for having no priority: a
     * ticket whose priority is unrecognised ranks last, but it is listed.
     */
    public function test_a_ticket_with_no_recognisable_priority_is_still_in_the_queue(): void
    {
        $facility = Facility::factory()->create();
        $patient  = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        $ticket = QueueTicket::create([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'queue_number'   => '000000-001',
            'current_queue'  => 'general',
            'status'         => 'waiting',
            'priority_level' => 0,
            'checked_in_at'  => now(),
        ]);

        $rows = app(\App\Services\Queue\PatientQueueService::class)->ranked($facility->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame($ticket->id, $rows[0]->id);
        $this->assertSame(6, (int) $rows[0]->priority_rank);
    }

    /** @return list<string> */
    private function phpFilesUnder(string $dir): array
    {
        $files = [];
        $it    = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** Strip PHP comments so a docblock explaining a rule is not read as breaking it. */
    private function stripComments(string $source): string
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }
}
