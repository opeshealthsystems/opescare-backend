<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('critical_value_alerts')) {
            return;
        }
        Schema::create('critical_value_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // FK to lab_results is added in 2026_05_28_000002 — the lab_results
            // table itself is only created in 2026_05_28_000001.
            $table->uuid('lab_result_id')->index();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('alert_type', ['critical_high', 'critical_low', 'panic_high', 'panic_low']);
            $table->string('test_name');
            $table->string('result_value');
            $table->string('critical_threshold');
            $table->boolean('acknowledged')->default(false);
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgement_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_value_alerts');
    }
};
