<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add blood_group to patients table.
 *
 * blood_group is a critical clinical safety field required by the emergency
 * profile endpoint and is part of the minimum patient record for a national
 * health system deployment.
 *
 * Accepted values: A+, A-, B+, B-, AB+, AB-, O+, O-
 * Nullable: blood group is not always known at registration time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('blood_group', 10)->nullable()->after('sex')
                ->comment('ABO/Rh blood group: A+, A-, B+, B-, AB+, AB-, O+, O-');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('blood_group');
        });
    }
};
