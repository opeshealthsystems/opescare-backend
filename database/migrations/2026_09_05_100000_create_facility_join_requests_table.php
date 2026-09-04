<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clinician-initiated requests to join a facility's team.
 *
 * This is the mirror image of facility_staff_invites. That table covers
 * "the facility knows who it wants"; this one covers "the clinician knows
 * where they work" — a doctor who self-registered at /signup/professional had
 * no way to say "I work at Hôpital Central de Yaoundé, please add me", and a
 * facility had no queue in which such a request could appear.
 *
 * Design notes that are load-bearing:
 *
 *  - `requested_role` IS A HINT AND NOTHING ELSE. It is free text, it is never
 *    resolved to a roles row, and no code path reads it when granting access.
 *    The role that is actually granted is written to `granted_role_id` by the
 *    approving facility administrator, chosen from
 *    FacilityStaffInvite::INVITABLE_ROLES. A column the requester controls that
 *    decides the requester's privileges is privilege escalation with extra
 *    steps, so the two are deliberately different columns.
 *
 *  - The partial unique index below is the race guard for "one open request per
 *    person per facility". Doing it in PHP ("does a pending row exist?") loses
 *    to two concurrent submits; doing it as a plain UNIQUE(facility_id,user_id)
 *    would forbid ever asking a second time after a decline. A UNIQUE index
 *    restricted to WHERE status = 'pending' is exactly the constraint wanted.
 *
 *  - `status` is terminal once decided. There is no transition back into
 *    'pending', which is what keeps a declined or withdrawn request from
 *    silently reopening.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_join_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('facility_id');
            $table->uuid('user_id');

            // What the requester SAYS they do. A hint for the reviewer; never a grant.
            $table->string('requested_role', 120)->nullable();
            $table->text('message')->nullable();

            $table->string('status', 20)->default('pending');

            // What the FACILITY granted. Null until an approval writes it.
            $table->uuid('granted_role_id')->nullable();
            $table->uuid('decided_by')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestampsTz();

            $table->index(['facility_id', 'status']);
            $table->index(['user_id', 'status']);

            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('granted_role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
        });

        // PostgreSQL partial unique index — one OPEN request per (facility, user).
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX facility_join_requests_one_open_per_user
            ON facility_join_requests (facility_id, user_id)
            WHERE status = 'pending'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS facility_join_requests_one_open_per_user');
        Schema::dropIfExists('facility_join_requests');
    }
};
