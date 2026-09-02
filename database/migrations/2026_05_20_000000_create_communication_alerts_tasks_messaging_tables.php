<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. notification_templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_type');
            $table->string('channel');
            $table->string('language');
            $table->string('subject');
            $table->string('title');
            $table->text('body');
            $table->string('cta_label')->nullable();
            $table->text('template_html')->nullable();
            $table->text('template_text');
            $table->string('priority')->default('normal');
            $table->string('communication_class')->default('optional');
            $table->string('approval_status')->default('draft');
            $table->integer('version')->default(1);
            $table->string('provider_template_id')->nullable();
            $table->string('provider_approval_status')->nullable();
            $table->timestamps();
        });

        // 2. notification_events
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_type');
            $table->string('communication_type');
            $table->string('actor_id')->nullable();
            $table->string('recipient_user_id')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->string('recipient_type');
            $table->string('related_resource_type')->nullable();
            $table->string('related_resource_id')->nullable();
            $table->text('payload_json');
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending');
            $table->boolean('requires_acknowledgement')->default(false);
            $table->string('acknowledgement_status')->default('not_required');
            $table->string('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('acknowledgement_deadline')->nullable();
            $table->unsignedBigInteger('escalation_chain_id')->nullable();
            $table->timestamps();
        });

        // 3. notification_deliveries
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('notification_event_id');
            $table->string('channel');
            $table->string('recipient');
            $table->string('provider');
            $table->string('status')->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 4. notification_preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('category');
            $table->boolean('email_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(true);
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('voice_enabled')->default(false);
            $table->boolean('dashboard_enabled')->default(true);
            $table->text('quiet_hours_json')->nullable();
            $table->string('language')->default('en');
            $table->timestamps();
        });

        // 5. escalation_chains
        Schema::create('escalation_chains', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('event_type');
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->text('steps_json');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 6. action_tasks
        Schema::create('action_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('task_type');
            $table->string('title');
            $table->text('description');
            $table->string('assigned_to')->nullable();
            $table->string('assigned_role')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('related_resource_type')->nullable();
            $table->string('related_resource_id')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('escalation_chain_id')->nullable();
            $table->timestamps();
        });

        // 7. voice_notification_jobs
        Schema::create('voice_notification_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('notification_event_id');
            $table->string('recipient_phone');
            $table->string('voice_template_id');
            $table->string('status')->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        // 8. message_threads
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('thread_type');
            $table->string('context_type')->nullable();
            $table->string('context_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('title');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->string('created_by');
            $table->string('assigned_to')->nullable();
            $table->boolean('legal_hold')->default(false);
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
        });

        // 9. message_thread_participants
        Schema::create('message_thread_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->string('user_id');
            $table->string('role_in_thread');
            $table->string('status')->default('active');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();
        });

        // 10. messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('thread_id');
            $table->string('sender_id');
            $table->string('message_type');
            $table->text('body');
            $table->string('status')->default('sent');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_for_sender_at')->nullable();
            $table->timestamps();
        });

        // 11. message_attachments
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('classification');
            $table->string('scan_status')->default('pending');
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
        });

        // 12. broadcasts
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('broadcast_type');
            $table->string('title');
            $table->text('body');
            $table->string('target_type');
            $table->text('target_ids_json');
            $table->string('priority')->default('normal');
            $table->string('language')->default('en');
            $table->boolean('requires_acknowledgement')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_thread_participants');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('voice_notification_jobs');
        Schema::dropIfExists('action_tasks');
        Schema::dropIfExists('escalation_chains');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('notification_templates');
    }
};
