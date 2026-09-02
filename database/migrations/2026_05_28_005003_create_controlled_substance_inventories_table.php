<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('controlled_substance_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('drug_code', 50);
            $table->string('drug_name', 255);
            $table->enum('schedule', ['schedule_i','schedule_ii','schedule_iii','schedule_iv','schedule_v']);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->string('unit', 30);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->uuid('last_reconciled_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['facility_id', 'drug_code'], 'cs_inventory_facility_drug_unique');
            $table->index(['facility_id', 'schedule']);
        });
    }
    public function down(): void { Schema::dropIfExists('controlled_substance_inventories'); }
};
