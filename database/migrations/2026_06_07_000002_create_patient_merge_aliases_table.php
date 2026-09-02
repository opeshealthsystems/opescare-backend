<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient Merge Aliases
 *
 * When two patient records are merged (duplicate resolution), the retired
 * Health ID must continue to resolve to the canonical patient so that:
 *
 *   1. External HIS systems that stored the old Health ID still get valid data.
 *   2. The QR code on a printed card issued before the merge still works.
 *   3. Emergency responders with an old card are not turned away.
 *
 * This table stores a many-to-one mapping: one canonical patient can have
 * many retired (alias) Health IDs pointing to it.
 *
 * Bidirectional resolution:
 *   - Forward: alias_health_id → canonical_patient_id  (old card → new record)
 *   - Reverse: canonical_patient_id → [alias_health_ids]  (admin view of merges)
 *
 * The `PatientMergeAlias` model adds a `mergeAliases()` / `canonicalPatient()`
 * relationship pair and a static `resolveHealthId()` helper used in the
 * HealthIdResolutionController to transparently redirect alias lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_merge_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The retired Health ID (e.g. "CM-HID-XXXX-XXXX-XXXX" of the duplicate)
            $table->string('alias_health_id', 30)->unique()
                ->comment('Retired Health ID — the duplicate that was merged away.');

            // The canonical patient that absorbed the duplicate
            $table->uuid('canonical_patient_id')
                ->comment('The surviving/canonical patient record.');

            $table->foreign('canonical_patient_id')
                ->references('id')
                ->on('patients')
                ->cascadeOnDelete(); // if the canonical record is deleted, aliases are cleaned up too

            // The original patient_id that was retired (kept for audit trail even
            // after the old patient row is soft-deleted or physically removed)
            $table->uuid('retired_patient_id')->nullable()
                ->comment('UUID of the patient record that was merged away (may no longer exist).');

            // Who performed the merge and why
            $table->uuid('merged_by_user_id')->nullable()
                ->comment('Admin user who approved the merge.');
            $table->text('merge_reason')->nullable()
                ->comment('Clinical or administrative reason for the merge.');

            // Merge direction notes — useful for audits
            $table->string('merge_direction', 20)->default('forward')
                ->comment('forward = duplicate→canonical. Used for display in audit UI.');

            $table->timestamps();

            // Indexes for both lookup directions
            $table->index('alias_health_id');           // forward lookup
            $table->index('canonical_patient_id');      // reverse lookup (admin view)
            $table->index('retired_patient_id');        // audit trace
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_merge_aliases');
    }
};
