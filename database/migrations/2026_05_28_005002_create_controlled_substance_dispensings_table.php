<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('controlled_substance_dispensings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->uuid('prescription_id')->index();
            $table->uuid('prescription_item_id')->index();
            $table->string('drug_code', 50);
            $table->string('drug_name', 255);
            $table->enum('schedule', ['schedule_i','schedule_ii','schedule_iii','schedule_iv','schedule_v']);
            $table->decimal('quantity_dispensed', 10, 2);
            $table->string('unit', 30);
            $table->foreignUuid('dispensed_by')->constrained('users');
            $table->timestamp('dispensed_at');
            $table->uuid('witness_id')->nullable()->index();
            $table->timestamp('witness_confirmed_at')->nullable();
            $table->decimal('stock_balance_before', 10, 2);
            $table->decimal('stock_balance_after', 10, 2);
            $table->string('lot_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['facility_id', 'dispensed_at']);
            $table->index(['drug_code', 'facility_id']);
            $table->index('schedule');
        });
    }
    public function down(): void { Schema::dropIfExists('controlled_substance_dispensings'); }
};
