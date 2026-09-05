<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `consent_requests` becomes a table a facility can actually write to.
 *
 * The table has existed since 2026_05_14 and, until this migration, had never
 * held a single row: the only code that could create one is
 * App\Modules\Governance\Services\ConsentService::requestConsent(), and no HTTP
 * route anywhere reached it. The patient's side of the loop was already built
 * and wired (GET /api/mobile/consent-requests, POST .../approve, .../deny) —
 * an inbox that was structurally always empty. This adds what the ASK needs.
 *
 * ── WHAT EACH NEW COLUMN IS FOR ───────────────────────────────────────────
 *
 *  justification  The sentence the PATIENT reads. `purpose` is a coded token
 *                 ('treatment', 'referral', …) that ConsentService::verifyAccess
 *                 JOINs on, so it cannot also be free text — a clinician typing
 *                 their reason into `purpose` would silently make every later
 *                 verifyAccess($scope, 'treatment') miss. Two columns, two jobs.
 *
 *  expires_at     When the ASK lapses. This is NOT the grant's lifetime —
 *                 `duration_minutes` is that, and it starts when the patient
 *                 approves. A request nobody answered must not sit "pending"
 *                 forever: it holds the partial unique index below and so
 *                 blocks the facility from ever asking again.
 *
 *  decided_at     Who ended it and when. Null while pending; written by the
 *  decided_by     approve / deny / withdraw paths in ConsentService.
 *
 * ── THE PARTIAL UNIQUE INDEX IS THE POINT ─────────────────────────────────
 *
 * A patient must not be spammed: one OPEN request per (facility, patient).
 *
 *   - Checking in PHP ("does a pending row exist?") loses to two concurrent
 *     submits — two clinicians at the same facility tapping at once.
 *   - A plain UNIQUE (requesting_facility_id, patient_id) would forbid ever
 *     asking again after a denial, which is worse than the problem: a patient
 *     who said no in March cannot say yes in September.
 *
 * A UNIQUE index restricted to the open state is exactly the constraint
 * wanted, and it is the same shape as `facility_join_requests_one_open_per_user`
 * (2026_09_05_100000). The predicate lists BOTH spellings of "open":
 * `pending_patient_approval` is what ConsentService has always written and what
 * the column defaults to, and `pending` is the shorter value the rest of this
 * codebase uses for review queues. Covering both means the constraint holds
 * however the row was written, rather than being silently bypassed by the
 * other spelling.
 *
 * Every other status — approved, denied, expired, withdrawn — is TERMINAL and
 * falls outside the predicate, which is what lets a denied request be re-asked
 * later while a pending one cannot be duplicated now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_requests', function (Blueprint $table) {
            // The reason in the clinician's own words — what the patient reads.
            $table->text('justification')->nullable();

            // When the ASK lapses, distinct from the grant's duration_minutes.
            $table->timestampTz('expires_at')->nullable();

            // How it ended.
            $table->timestampTz('decided_at')->nullable();
            $table->uuid('decided_by')->nullable();

            $table->index(['patient_id', 'status']);
            $table->index(['requesting_facility_id', 'status']);
            $table->index('expires_at');

            $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
        });

        // PostgreSQL partial unique index — one OPEN request per (facility, patient).
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX consent_requests_one_open_per_facility
            ON consent_requests (requesting_facility_id, patient_id)
            WHERE status IN ('pending', 'pending_patient_approval')
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS consent_requests_one_open_per_facility');

        Schema::table('consent_requests', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->dropIndex(['patient_id', 'status']);
            $table->dropIndex(['requesting_facility_id', 'status']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['justification', 'expires_at', 'decided_at', 'decided_by']);
        });
    }
};
