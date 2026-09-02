<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `notifications` was created with $table->morphs('notifiable'), which makes
 * `notifiable_id` a BIGINT. Every notifiable in this application (Patient,
 * User) has a UUID primary key, so the column could never hold a valid id:
 * writes threw, and reads blew up with
 *
 *   SQLSTATE[22P02]: invalid input syntax for type bigint: "00000000-…"
 *
 * which is why GET /api/mobile/notifications and .../unread-count returned 500
 * and the table sat permanently empty. Widen the column to a string so the
 * UUID morph keys fit (the same shape uuidMorphs() would have produced).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            // Postgres will not implicitly cast bigint -> varchar.
            DB::statement('ALTER TABLE notifications ALTER COLUMN notifiable_id TYPE VARCHAR(255) USING notifiable_id::VARCHAR');

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notifiable_id')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        // Only reversible while every stored key is numeric — UUID morph keys
        // cannot be represented as a bigint, so refuse rather than lose rows.
        if (DB::table('notifications')->whereRaw("notifiable_id !~ '^[0-9]+$'")->exists()) {
            throw new RuntimeException(
                'Cannot revert notifications.notifiable_id to bigint: non-numeric (UUID) morph keys are present.'
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN notifiable_id TYPE BIGINT USING notifiable_id::BIGINT');

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('notifiable_id')->change();
        });
    }
};
