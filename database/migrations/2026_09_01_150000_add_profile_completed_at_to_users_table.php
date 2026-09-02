<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sign-up stops at an email and a password, so an account now exists for a
 * while before its owner has told us who they are. Patients are marked by
 * users.patient_id — the Patient row IS the completion. Guardians have no such
 * row until a reviewer verifies the relationship, so they need a marker of
 * their own.
 *
 * Deliberately generic rather than guardian-specific: it records that the
 * post-sign-up step was submitted, whoever the account belongs to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable()->after('patient_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_completed_at');
        });
    }
};
