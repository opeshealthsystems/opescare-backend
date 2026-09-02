<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->uuid('assigned_by')->nullable();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();

            $table->unique(['user_id', 'facility_id', 'role_id'], 'fra_user_facility_role_unique');
            $table->index(['user_id', 'facility_id'], 'fra_user_facility_index');
            $table->index(['facility_id', 'role_id'], 'fra_facility_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_role_assignments');
    }
};
