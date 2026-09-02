<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('handoff_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('handoff_notes', 'content')) {
                $table->text('content')->nullable();
            }
            if (!Schema::hasColumn('handoff_notes', 'priority')) {
                $table->enum('priority', ['routine', 'urgent', 'critical'])->default('routine');
            }
            if (!Schema::hasColumn('handoff_notes', 'acknowledged')) {
                $table->boolean('acknowledged')->default(false);
            }
            if (!Schema::hasColumn('handoff_notes', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable();
            }
            if (!Schema::hasColumn('handoff_notes', 'patient_id')) {
                $table->uuid('patient_id')->nullable()->index();
            }
            // Soft aliases (non-FK) for from_provider_id / to_provider_id
            if (!Schema::hasColumn('handoff_notes', 'from_provider')) {
                $table->uuid('from_provider')->nullable();
            }
            if (!Schema::hasColumn('handoff_notes', 'to_provider')) {
                $table->uuid('to_provider')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('handoff_notes', function (Blueprint $table) {
            foreach (['content', 'priority', 'acknowledged', 'acknowledged_at', 'from_provider', 'to_provider'] as $col) {
                if (Schema::hasColumn('handoff_notes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
