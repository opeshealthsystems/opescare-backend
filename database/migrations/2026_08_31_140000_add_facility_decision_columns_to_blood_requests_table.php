<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records WHO moved a blood request and WHEN.
 *
 * `blood_requests` shipped with `confirmed_at` / `fulfilled_at` but no facility
 * side to write them: `confirmed`, `ready`, `fulfilled` and `rejected` were
 * unreachable statuses. Now that the blood bank can act
 * (App\Http\Controllers\Api\V1\BloodRequestQueueController), a decision needs an
 * actor and a timestamp — that is the whole of it, not a workflow engine.
 *
 * `decided_by` holds the integration client id that acted (the value
 * VerifyIntegrationClient puts on the request), never a caller-supplied string.
 * `decided_at` is stamped on every facility transition, including the ones that
 * have no dedicated column of their own (`ready`, `rejected`).
 *
 * Purely additive: no column is dropped, no row is rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->string('decided_by')->nullable()->after('facility_note');
            $table->timestamp('decided_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropColumn(['decided_by', 'decided_at']);
        });
    }
};
