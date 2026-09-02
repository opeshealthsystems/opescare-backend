<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('license_number')->nullable();
            $table->uuid('parent_organization_id')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('actor_role')->nullable();
            $table->uuid('facility_id')->nullable()->index();
            $table->uuid('patient_id')->nullable()->index();
            $table->uuid('encounter_id')->nullable()->index();
            $table->string('action_type');
            $table->string('resource_type');
            $table->uuid('resource_id')->nullable()->index();
            $table->uuid('consent_grant_id')->nullable();
            $table->boolean('emergency_override')->default(false);
            $table->string('source_system')->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('before_state')->nullable();
            $table->jsonb('after_state')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('facilities');
    }
};
