<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * webhook_replays.replayed_by was a uuid column, but the B2B replay endpoint
 * records the calling integration client's client_id (a string such as
 * "client_abc123") as the actor. Any real client therefore crashed Postgres
 * with "invalid input syntax for type uuid" — a guaranteed 500 on replay.
 *
 * The client_id IS the meaningful actor identity in the Connect context, so
 * the column becomes a string. Existing uuid values cast losslessly to text.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_replays')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE webhook_replays ALTER COLUMN replayed_by TYPE varchar(255) USING replayed_by::text');
        } else {
            // SQLite/MySQL store uuids as text-compatible already; widen via change().
            Schema::table('webhook_replays', function ($table) {
                $table->string('replayed_by', 255)->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('webhook_replays')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            // Non-uuid actor ids cannot survive a cast back; the column is NOT NULL,
            // so map them to the nil uuid rather than deleting audit rows.
            DB::statement("UPDATE webhook_replays SET replayed_by = '00000000-0000-0000-0000-000000000000' WHERE replayed_by !~ '^[0-9a-fA-F-]{36}\$'");
            DB::statement('ALTER TABLE webhook_replays ALTER COLUMN replayed_by TYPE uuid USING replayed_by::uuid');
        }
    }
};
