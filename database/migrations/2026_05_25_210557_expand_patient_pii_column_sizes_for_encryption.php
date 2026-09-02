<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip on SQLite (test environment) — SQLite is flexible with column types
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            // Encrypted values are significantly longer than plain text
            // A 10-char DOB becomes ~200+ chars when encrypted
            $table->text('date_of_birth')->nullable()->change();
            $table->text('phone_number')->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->string('date_of_birth', 20)->nullable()->change();
            $table->string('phone_number', 30)->nullable()->change();
            $table->string('address', 500)->nullable()->change();
        });
    }
};
