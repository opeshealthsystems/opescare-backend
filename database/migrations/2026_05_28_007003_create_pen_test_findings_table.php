<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pen_test_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pen_test_engagement_id')->index();
            $table->string('title', 255);
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'informational']);
            $table->decimal('cvss_score', 3, 1)->nullable();
            $table->text('description');
            $table->string('affected_component', 255);
            $table->text('attack_vector')->nullable();
            $table->text('remediation_steps');
            $table->enum('status', [
                'open',
                'in_progress',
                'remediated',
                'accepted_risk',
                'false_positive',
            ])->default('open');
            $table->uuid('assigned_to')->nullable()->index();
            $table->date('due_date')->nullable();
            $table->timestamp('remediated_at')->nullable();
            $table->uuid('remediated_by')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pen_test_engagement_id')
                  ->references('id')->on('pen_test_engagements')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('remediated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['pen_test_engagement_id', 'severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pen_test_findings');
    }
};
