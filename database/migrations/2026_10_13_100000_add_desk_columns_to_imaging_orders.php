<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The imaging desk (2026-10-13) moves an order through
 * pending → scheduled → in_progress → completed, or cancels it with a
 * reason. `scheduled_at` and `completed_at` existed; the start and the
 * cancellation had nowhere to be written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imaging_orders', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('imaging_orders', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'cancelled_at', 'cancel_reason']);
        });
    }
};
