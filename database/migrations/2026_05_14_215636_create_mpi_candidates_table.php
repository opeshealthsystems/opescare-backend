<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpi_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_patient_id')->index();
            $table->uuid('target_patient_id')->index();
            $table->decimal('match_score', 5, 2)->default(0.00);
            $table->jsonb('match_reasons')->nullable();
            $table->string('status')->default('pending_review')->comment('pending_review, merged, rejected');
            $table->uuid('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('source_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('target_patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpi_candidates');
    }
};
