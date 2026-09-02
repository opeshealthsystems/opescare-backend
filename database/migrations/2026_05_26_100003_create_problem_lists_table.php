<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('problem_lists')) {
            return;
        }
        Schema::create('problem_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('icd_code', 20);
            $table->enum('icd_version', ['10', '11'])->default('10');
            $table->text('description');
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->enum('status', ['active', 'resolved', 'inactive', 'entered_in_error'])->default('active');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('notes')->nullable();
            $table->index(['patient_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_lists');
    }
};
