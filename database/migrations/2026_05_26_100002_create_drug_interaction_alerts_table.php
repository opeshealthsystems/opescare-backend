<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('drug_interaction_alerts')) {
            return;
        }
        Schema::create('drug_interaction_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_id')
                ->constrained('medication_reconciliations')->cascadeOnDelete();
            $table->string('drug_a');
            $table->string('drug_b');
            $table->enum('severity', ['minor', 'moderate', 'major', 'contraindicated']);
            $table->text('description');
            $table->boolean('is_hard_stop')->default(false);
            $table->boolean('acknowledged')->default(false);
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_interaction_alerts');
    }
};
