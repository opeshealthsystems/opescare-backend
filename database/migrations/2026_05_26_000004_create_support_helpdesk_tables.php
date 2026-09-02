<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('requester_type')->index();
            $table->uuid('requester_id')->nullable()->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->string('category')->index();
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('open')->index();
            $table->string('subject');
            $table->text('description_redacted');
            $table->json('pii_redaction_summary')->nullable();
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('escalation_level')->nullable();
            $table->timestampTz('escalated_at')->nullable();
            $table->timestampTz('sla_due_at')->nullable()->index();
            $table->timestampTz('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->uuid('incident_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('incident_id')->references('id')->on('security_incidents')->nullOnDelete();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('support_ticket_id')->index();
            $table->string('sender_type')->index();
            $table->uuid('sender_id')->nullable()->index();
            $table->text('body_redacted');
            $table->json('pii_redaction_summary')->nullable();
            $table->boolean('internal')->default(false);
            $table->timestamps();

            $table->foreign('support_ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
        });

        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('support_ticket_id')->index();
            $table->uuid('assigned_to')->index();
            $table->uuid('assigned_by')->nullable()->index();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestamps();

            $table->foreign('support_ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('incident_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('support_ticket_id')->index();
            $table->uuid('security_incident_id')->index();
            $table->string('severity')->index();
            $table->text('summary');
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('support_ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            $table->foreign('security_incident_id')->references('id')->on('security_incidents')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('audience')->default('all')->index();
            $table->string('status')->default('published')->index();
            $table->longText('body');
            $table->unsignedInteger('view_count')->default(0);
            $table->uuid('created_by')->nullable()->index();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('incident_reports');
        Schema::dropIfExists('ticket_assignments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
