<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turn `account_closure_requests` into a self-service, grace-period deletion
 * record — the thing Apple 5.1.1(v) and Google's Play data-safety rules require
 * an app that creates accounts to offer from inside the app.
 *
 * WHY THIS TABLE AND NOT A NEW ONE
 * --------------------------------
 * `account_closure_requests` shipped with 2026_05_27_000013_create_legal_
 * compliance_tables.php and has never had a model, a controller or a route
 * pointing at it — a schema stub, zero rows. Its shape assumed a human
 * reviewer (`reviewed_by`, `review_note`, status `approved|rejected`), which
 * is the one shape the stores will not accept: a deletion a staff member has
 * to approve is not "delete your account in the app". So the review columns
 * stay (a data steward may still annotate a closure), and the columns that
 * make the flow self-service and REVERSIBLE are added here.
 *
 * WHAT THE NEW COLUMNS ARE FOR
 * ----------------------------
 *  executes_after       When the grace period runs out and erasure runs. The
 *                       one column the executor sweeps on.
 *  grace_period_days    Recorded per request, not read from config at
 *                       execution time — the patient was shown a date and a
 *                       number of days, and a later config change must not
 *                       silently move a deadline somebody was promised.
 *  cancelled_at/_reason Reversibility. A closure request is undone, never
 *                       deleted, so "I cancelled it" stays provable.
 *  executed_at          When erasure actually ran.
 *  execution_summary    What erasure actually did, per step, with counts. The
 *                       evidence that the screen's promises were kept.
 *  retention_until      The date the RETAINED clinical record becomes eligible
 *                       for destruction. Stamped from the country policy's
 *                       retention_period_years at execution, so the date the
 *                       patient was told is the date that is enforced.
 *  purged_at            Set when that second, later purge has actually run.
 *  disclosure_version   Which itemised "what is erased / kept / why" text the
 *                       patient confirmed against. If the disclosure is ever
 *                       reworded, old requests still say what was agreed.
 *  reauth_method        password | pin — how the patient proved it was them.
 *  requested_ip/_agent  Where the request came from. Audit, not analytics.
 *
 * NOTE ON `status`: the column already exists as a plain string with a
 * `pending` default and an index. The new lifecycle reuses it —
 * pending -> cancelled | completed | purged — so no type change is needed and
 * the existing index keeps serving the executor's sweep.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_closure_requests', function (Blueprint $table) {
            // The grace-period deadline. Indexed: the daily executor's only
            // predicate is "pending AND executes_after <= now".
            $table->timestampTz('executes_after')->nullable()->index()->after('status');
            $table->unsignedSmallInteger('grace_period_days')->nullable()->after('executes_after');

            // Reversibility.
            $table->timestampTz('cancelled_at')->nullable()->after('grace_period_days');
            $table->string('cancelled_reason', 300)->nullable()->after('cancelled_at');

            // Execution.
            $table->timestampTz('executed_at')->nullable()->after('cancelled_reason');
            $table->jsonb('execution_summary')->nullable()->after('executed_at');

            // The retained half, and its end.
            $table->date('retention_until')->nullable()->index()->after('execution_summary');
            $table->timestampTz('purged_at')->nullable()->after('retention_until');

            // What the patient was actually shown and how they proved identity.
            $table->string('disclosure_version', 20)->nullable()->after('purged_at');
            $table->string('reauth_method', 20)->nullable()->after('disclosure_version');
            $table->string('requested_via', 20)->nullable()->after('reauth_method');
            $table->string('requested_ip', 45)->nullable()->after('requested_via');
            $table->string('requested_user_agent', 512)->nullable()->after('requested_ip');
        });

        // `patient_id` becomes nullable so the closure request can OUTLIVE the
        // patient row it refers to.
        //
        // It cascades from `patients`, so at the far end — when the retention
        // period expires and the retained record is finally destroyed — deleting
        // the patient would also delete the record of the authority to delete
        // them. AccountDeletionService::purgeExpiredRetention() detaches the
        // request first and marks it `purged`, leaving a dated, patient-free
        // tombstone that says an erasure was asked for, executed, and completed.
        Schema::table('account_closure_requests', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable()->change();
        });

        // One live closure request per patient. Without this, two taps on a
        // slow connection open two grace periods and cancelling one leaves the
        // other running — the patient believes they stopped it and the
        // erasure still fires. Partial index, so completed/cancelled history
        // accumulates freely.
        //
        // Raw SQL: Laravel's schema builder has no partial-index primitive.
        // Postgres only (this platform's system of record; see CLAUDE.md).
        \Illuminate\Support\Facades\DB::statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS account_closure_requests_one_pending_per_patient
             ON account_closure_requests (patient_id)
             WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'DROP INDEX IF EXISTS account_closure_requests_one_pending_per_patient'
        );

        Schema::table('account_closure_requests', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable(false)->change();
        });

        Schema::table('account_closure_requests', function (Blueprint $table) {
            $table->dropColumn([
                'executes_after',
                'grace_period_days',
                'cancelled_at',
                'cancelled_reason',
                'executed_at',
                'execution_summary',
                'retention_until',
                'purged_at',
                'disclosure_version',
                'reauth_method',
                'requested_via',
                'requested_ip',
                'requested_user_agent',
            ]);
        });
    }
};
