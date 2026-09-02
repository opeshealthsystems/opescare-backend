<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adr_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('reporter_id');
            $table->string('suspect_drug', 255);
            $table->string('suspect_drug_batch', 100)->nullable();
            $table->string('suspect_drug_dose', 100)->nullable();
            $table->string('suspect_drug_route', 50)->nullable();
            $table->string('indication_for_use', 500)->nullable();
            $table->date('reaction_onset_date')->nullable();
            $table->string('reaction_description', 3000);
            $table->string('severity', 20);
            $table->string('causality_assessment', 30)->nullable();
            $table->boolean('drug_stopped')->default(false);
            $table->boolean('rechallenged')->default(false);
            $table->boolean('reaction_resolved')->default(false);
            $table->string('outcome', 50)->nullable();
            $table->string('concomitant_drugs', 2000)->nullable();
            $table->string('reporter_profession', 100)->nullable();
            $table->string('notes', 3000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE adr_reports ADD CONSTRAINT chk_adr_severity CHECK (severity IN ('mild','moderate','severe','life_threatening','fatal'))");
            DB::statement("ALTER TABLE adr_reports ADD CONSTRAINT chk_adr_outcome CHECK (outcome IS NULL OR outcome IN ('recovered','recovering','not_recovered','recovered_with_sequelae','fatal','unknown'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adr_reports');
    }
};
