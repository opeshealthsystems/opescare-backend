<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_directives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->enum('directive_type', [
                'dnr',
                'living_will',
                'healthcare_proxy',
                'polst',
                'organ_donation',
                'other',
            ]);
            $table->boolean('is_active')->default(true);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->string('witness_name', 255)->nullable();
            $table->date('witness_date')->nullable();
            $table->string('healthcare_proxy_name', 255)->nullable();
            $table->string('healthcare_proxy_phone', 30)->nullable();
            $table->string('healthcare_proxy_relationship', 100)->nullable();
            $table->text('instructions')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['patient_id', 'is_active', 'directive_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_directives');
    }
};
