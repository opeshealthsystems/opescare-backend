<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Refer & Earn" double-sided growth referral program.
 *
 * Distinct from the CLINICAL referrals feature (ReferralCase). Each patient owns
 * a stable shareable code (patients.referral_code); when a new patient signs up
 * with that code a ReferralInvite row is created and both parties earn Premium days.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('patients', 'referral_code')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('referral_code', 16)->nullable()->unique()->after('health_id');
            });
        }

        Schema::create('referral_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('referrer_patient_id');
            $table->string('code', 16)->index();
            $table->string('referee_email')->nullable();
            $table->uuid('referee_patient_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending | joined | rewarded
            $table->integer('referrer_reward_days')->default(0);
            $table->integer('referee_reward_days')->default(0);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index('referrer_patient_id');
            $table->index('status');

            $table->foreign('referrer_patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('referee_patient_id')->references('id')->on('patients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_invites');

        if (Schema::hasColumn('patients', 'referral_code')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};
