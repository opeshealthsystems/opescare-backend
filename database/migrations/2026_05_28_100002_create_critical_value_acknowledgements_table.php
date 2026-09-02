<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_value_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lab_result_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained()->cascadeOnDelete();
            $table->string('flag', 10)->comment('HH|LL — the critical flag on the lab result');
            $table->string('test_name', 255);
            $table->string('value', 50);
            $table->string('unit', 30)->nullable();
            $table->foreignUuid('notified_by')->constrained('users')->cascadeOnDelete()
                ->comment('Lab staff who sent the critical value notification');
            $table->timestamp('notified_at');
            $table->string('notification_method', 30)->default('phone')
                ->comment('phone|in_person|pager|electronic|other');
            $table->string('notified_recipient', 255)->nullable()
                ->comment('Name of clinician/nurse who was called');
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Clinician who acknowledged receipt');
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('is_read_back')->default(false)
                ->comment('Whether recipient read back the value to confirm accuracy');
            $table->text('acknowledgement_notes')->nullable();
            $table->timestamps();

            $table->index(['lab_result_id']);
            $table->index(['patient_id', 'notified_at']);
            $table->index(['facility_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_value_acknowledgements');
    }
};
