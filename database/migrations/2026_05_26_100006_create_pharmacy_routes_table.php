<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pharmacy_routes')) {
            return;
        }
        Schema::create('pharmacy_routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('pharmacy_name');
            $table->enum('pharmacy_type', ['in_facility', 'external', 'online'])->default('in_facility');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->enum('routing_method', ['fax', 'api', 'print', 'sms'])->default('print');
            $table->string('api_endpoint')->nullable();
            $table->string('api_key_encrypted')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_routes');
    }
};
