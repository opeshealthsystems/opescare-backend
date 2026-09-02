<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lab_reference_ranges')) {
            return;
        }
        Schema::create('lab_reference_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loinc_code', 30)->nullable();
            $table->string('test_name');
            $table->string('unit', 30);
            $table->enum('gender', ['male', 'female', 'all'])->default('all');
            $table->unsignedTinyInteger('age_min')->default(0);
            $table->unsignedTinyInteger('age_max')->default(120);
            $table->decimal('normal_low', 10, 3)->nullable();
            $table->decimal('normal_high', 10, 3)->nullable();
            $table->decimal('critical_low', 10, 3)->nullable();
            $table->decimal('critical_high', 10, 3)->nullable();
            $table->index(['loinc_code', 'gender']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reference_ranges');
    }
};
