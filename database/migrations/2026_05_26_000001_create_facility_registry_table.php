<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_fr')->nullable();
            $table->string('type', 60);
            $table->string('ownership', 30)->nullable();
            $table->string('region', 60);
            $table->string('division', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('ministry_code', 80)->nullable();
            $table->string('accreditation_level', 100)->nullable();
            $table->unsignedInteger('bed_capacity')->nullable();
            // jsonb falls back to text on SQLite — tests work fine
            $table->jsonb('services')->nullable();
            $table->string('source', 100)->default('initial_seed_2026');
            $table->string('source_url')->nullable();
            $table->string('status', 20)->default('unverified');
            $table->foreignUuid('claimed_facility_id')
                ->nullable()
                ->constrained('facilities')
                ->nullOnDelete();
            $table->timestampTz('claimed_at')->nullable();
            $table->timestampsTz();

            $table->index('type',                'idx_fr_type');
            $table->index('region',              'idx_fr_region');
            $table->index('status',              'idx_fr_status');
            $table->index('claimed_facility_id', 'idx_fr_claimed');
            $table->index('ministry_code',       'idx_fr_ministry');
            $table->index('city',                'idx_fr_city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_registry');
    }
};
