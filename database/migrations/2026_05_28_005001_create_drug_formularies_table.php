<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('drug_formularies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->string('generic_name', 255);
            $table->json('brand_names')->nullable();
            $table->string('drug_code', 50)->index();
            $table->string('drug_class', 100);
            $table->enum('form', ['tablet','capsule','liquid','injection','topical','inhaler','other']);
            $table->string('strength', 50);
            $table->string('unit', 30);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_controlled')->default(false);
            $table->boolean('requires_prior_auth')->default(false);
            $table->json('restricted_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['drug_code', 'facility_id']);
            $table->index(['is_controlled', 'facility_id']);
            $table->index('generic_name');
        });
    }
    public function down(): void { Schema::dropIfExists('drug_formularies'); }
};
