<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clinical rules: configurable rules engine
        Schema::create('clinical_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rule_type');           // allergy, drug_interaction, duplicate_rx, critical_lab, abnormal_lab, age_warning, pregnancy_warning, chronic_reminder, vaccination_reminder
            $table->string('rule_code')->unique(); // machine-readable key e.g. ALLERGY_PENICILLIN
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('severity');            // info, warning, critical
            $table->json('trigger_conditions');    // {drug_codes:[], allergy_codes:[], lab_codes:[], ...}
            $table->text('alert_message');
            $table->text('recommendation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_overridable')->default(true); // critical labs may be non-overridable
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['rule_type', 'is_active']);
            $table->index('severity');
        });

        // Drug interaction rules: bidirectional pairs
        Schema::create('drug_interaction_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('drug_a_code');
            $table->string('drug_a_name');
            $table->string('drug_b_code');
            $table->string('drug_b_name');
            $table->string('severity');            // minor, moderate, major, contraindicated
            $table->text('interaction_description');
            $table->text('clinical_effect')->nullable();
            $table->text('management')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['drug_a_code', 'drug_b_code']);
            $table->index(['drug_b_code', 'drug_a_code']); // bidirectional lookup
        });

        // Allergy alert rules: map drug to allergen
        Schema::create('allergy_alert_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('drug_code');
            $table->string('drug_name');
            $table->string('allergen_code');        // e.g. PENICILLIN, SULFA, NSAID
            $table->string('allergen_name');
            $table->string('cross_reactivity_group')->nullable(); // e.g. BETA_LACTAM
            $table->string('severity');             // warning, critical
            $table->text('alert_message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['drug_code', 'allergen_code']);
        });

        // Clinical alerts fired per visit
        Schema::create('clinical_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('patient_id');
            $table->uuid('visit_id')->nullable();
            $table->uuid('rule_id')->nullable();    // nullable: alerts may be ad-hoc
            $table->string('alert_type');           // allergy, drug_interaction, duplicate_rx, critical_lab, abnormal_lab, age_warning, pregnancy_warning, chronic_reminder, vaccination_reminder
            $table->string('severity');             // info, warning, critical
            $table->text('alert_message');
            $table->text('recommendation')->nullable();
            $table->json('context_data')->nullable(); // {drug_name, allergen, lab_name, value, ...}
            $table->string('status')->default('active'); // active, acknowledged, overridden, resolved, dismissed
            $table->string('triggered_by')->nullable();  // staff user id / system
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['visit_id', 'status']);
            $table->index(['facility_id', 'severity', 'status']);
            $table->index('triggered_at');
        });

        // Alert overrides: reason-required audit log
        Schema::create('alert_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_id');
            $table->uuid('patient_id');
            $table->uuid('visit_id')->nullable();
            $table->string('overridden_by');
            $table->text('override_reason');        // required, must be non-empty
            $table->string('override_category')->nullable(); // patient_preference, clinical_necessity, allergy_not_confirmed, risk_benefit, other
            $table->timestamp('overridden_at');
            $table->timestamps();

            $table->foreign('alert_id')->references('id')->on('clinical_alerts')->cascadeOnDelete();
            $table->index(['alert_id']);
            $table->index(['patient_id', 'overridden_at']);
            $table->index('overridden_by');
        });

        // Lab alert thresholds: per-test normal ranges
        Schema::create('lab_alert_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('lab_test_code');
            $table->string('lab_test_name');
            $table->string('unit')->nullable();
            $table->decimal('critical_low', 10, 4)->nullable();
            $table->decimal('normal_low', 10, 4)->nullable();
            $table->decimal('normal_high', 10, 4)->nullable();
            $table->decimal('critical_high', 10, 4)->nullable();
            $table->string('gender_filter')->nullable(); // M, F, null = all
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['lab_test_code', 'is_active']);
        });

        // Clinical reminders: preventive care tracking
        Schema::create('clinical_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('patient_id');
            $table->string('reminder_type');        // chronic_review, vaccination, screening, follow_up
            $table->string('title');
            $table->text('body');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending'); // pending, sent, dismissed, completed
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['facility_id', 'due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_reminders');
        Schema::dropIfExists('alert_overrides');
        Schema::dropIfExists('clinical_alerts');
        Schema::dropIfExists('lab_alert_rules');
        Schema::dropIfExists('allergy_alert_rules');
        Schema::dropIfExists('drug_interaction_rules');
        Schema::dropIfExists('clinical_rules');
    }
};
