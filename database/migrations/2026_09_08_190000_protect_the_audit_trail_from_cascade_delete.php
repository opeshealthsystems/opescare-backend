<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The audit trail must survive the deletion of the person who acted.
 *
 * ── What was wrong ───────────────────────────────────────────────────────
 * `access_logs` records who opened whose record. Its three foreign keys were
 * declared together in 2026_05_19_000001, and two of them chose the safe
 * option while the third did not:
 *
 *     patient_id  -> patients   ON DELETE SET NULL
 *     actor_id    -> users      ON DELETE CASCADE     <-- this one
 *     facility_id -> facilities ON DELETE SET NULL
 *
 * So deleting a user deleted every record of what that user had looked at.
 * The evidence of access disappeared along with the person who accessed —
 * which is precisely backwards, because the moment you most need an access
 * log is when you are investigating the person whose account is being
 * removed.
 *
 * ── Why RESTRICT and not SET NULL ────────────────────────────────────────
 * `actor_id` is NOT NULL, so SET NULL is not available without also widening
 * the column — and a null actor would be a worse answer anyway: "somebody
 * opened this record and we no longer know who" is not an audit trail, it is
 * the shape of one.
 *
 * RESTRICT says the true thing instead: a user who has touched a patient
 * record cannot be erased. The database refuses. That is the correct
 * invariant for an audit log, and it is enforced now even against a console
 * command, a tinker session, or a controller written next year.
 *
 * ── This is defence in depth, not the fix ────────────────────────────────
 * The fix was at the controllers: admin "delete" on a user and on a facility
 * now DEACTIVATE (`status = 'suspended'`) and issue no DELETE at all. Nothing
 * routed can reach this constraint. It exists so that the guarantee does not
 * depend on every future writer remembering.
 *
 * SCOPE IS DELIBERATELY NARROW. `users` still cascades to 46 other tables and
 * `facilities` to 61. Repointing all of those is a large migration with real
 * risk, and it is not what makes the platform safe today. This changes ONE
 * constraint: the one protecting the record of who looked at a patient.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('access_logs')) {
            return;
        }

        // Nothing in the application deletes a user (verified: no User::delete,
        // no truncate, no demo-reset path), so no existing flow can start
        // failing on this. A failure here means something is trying to erase
        // an audit trail, which is exactly what should fail.
        DB::statement('ALTER TABLE access_logs DROP CONSTRAINT IF EXISTS access_logs_actor_id_foreign');
        DB::statement(
            'ALTER TABLE access_logs
               ADD CONSTRAINT access_logs_actor_id_foreign
               FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('access_logs')) {
            return;
        }

        // Restores the original CASCADE. Reversible because a migration should
        // be — but note that rolling this back re-arms the deletion of an
        // audit trail, which is not a thing anybody should want.
        DB::statement('ALTER TABLE access_logs DROP CONSTRAINT IF EXISTS access_logs_actor_id_foreign');
        DB::statement(
            'ALTER TABLE access_logs
               ADD CONSTRAINT access_logs_actor_id_foreign
               FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE'
        );
    }
};
