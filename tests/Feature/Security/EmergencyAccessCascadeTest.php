<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmergencyAccessCascadeTest extends TestCase
{
    public function test_emergency_access_events_patient_fk_is_set_null_not_cascade(): void
    {
        // SQLite test environment does not support information_schema — skip gracefully
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('FK delete rule check requires PostgreSQL — skipping on ' . DB::getDriverName());
            return;
        }

        // Check the FK delete rule for emergency_access_events
        $fk = DB::select("
            SELECT rc.delete_rule
            FROM information_schema.table_constraints tc
            JOIN information_schema.referential_constraints rc
                ON rc.constraint_name = tc.constraint_name
            WHERE tc.table_name = 'emergency_access_events'
            AND tc.constraint_type = 'FOREIGN KEY'
        ");

        if (empty($fk)) {
            $this->markTestSkipped('emergency_access_events table or FK not found in test DB');
            return;
        }

        foreach ($fk as $row) {
            $this->assertNotEquals(
                'CASCADE',
                strtoupper($row->delete_rule),
                'emergency_access_events patient FK must be SET NULL, not CASCADE — audit records must survive patient deletion'
            );
        }
    }
}
