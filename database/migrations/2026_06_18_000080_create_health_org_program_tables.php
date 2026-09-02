<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Health-Org / NGO portal: real backing tables for programs and outreach
 * events (previously the portal only listed facilities), plus a baseline
 * public_health_report_types catalog so reports can actually be created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('health_org_programs')) {
            Schema::create('health_org_programs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('program_type')->nullable();   // immunization|maternal|nutrition|disease_control|education
                $table->string('status')->default('active');  // active|paused|completed
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('target_population')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('health_org_outreach_events')) {
            Schema::create('health_org_outreach_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('program_id')->nullable()->index();
                $table->uuid('facility_id')->nullable()->index();
                $table->string('title');
                $table->string('location')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('status')->default('planned');  // planned|in_progress|completed|cancelled
                $table->string('target_population')->nullable();
                $table->unsignedInteger('people_reached')->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Seed a minimal report-type catalogue if empty, so reports can be created.
        if (Schema::hasTable('public_health_report_types') && DB::table('public_health_report_types')->count() === 0) {
            $now = now();
            $types = [
                ['IDSR_WEEKLY', 'Weekly notifiable disease report', 'routine'],
                ['IMMUNIZATION_COVERAGE', 'Immunization coverage report', 'routine'],
                ['MATERNAL_MORTALITY', 'Maternal mortality report', 'sensitive'],
                ['OUTBREAK_SITREP', 'Outbreak situation report', 'sensitive'],
            ];
            foreach ($types as [$code, $name, $sensitivity]) {
                DB::table('public_health_report_types')->insert([
                    'id'                     => (string) Str::uuid(),
                    'code'                   => $code,
                    'name'                   => $name,
                    'description'            => $name,
                    'sensitivity_level'      => $sensitivity,
                    'default_review_required' => $sensitivity === 'sensitive',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('health_org_outreach_events');
        Schema::dropIfExists('health_org_programs');
    }
};
