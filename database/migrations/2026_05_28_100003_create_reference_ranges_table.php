<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('test_code', 50)->index();
            $table->string('test_name', 255);
            $table->string('loinc_code', 30)->nullable()->index();
            $table->string('age_group', 30)->default('all')
                ->comment('neonate|infant|child|adolescent|adult|geriatric|all');
            $table->string('sex', 10)->default('all')
                ->comment('male|female|all');
            $table->string('unit', 50);
            $table->decimal('normal_low', 12, 4)->nullable();
            $table->decimal('normal_high', 12, 4)->nullable();
            $table->decimal('critical_low', 12, 4)->nullable()
                ->comment('Value below this triggers HH/LL critical flag');
            $table->decimal('critical_high', 12, 4)->nullable()
                ->comment('Value above this triggers HH/LL critical flag');
            $table->string('source', 100)->default('internal')
                ->comment('internal|WHO|CDC|local_laboratory');
            $table->foreignUuid('facility_id')->nullable()->constrained()->nullOnDelete()
                ->comment('null = platform default; set = facility-specific override');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['test_code', 'age_group', 'sex']);
            $table->index(['facility_id', 'test_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_ranges');
    }
};
