<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A patient's own photo on their Health ID.
 *
 * The file itself lives on the private `local` disk under patient-photos/ and
 * is served only through a short-lived signed URL (see
 * MobilePatientPhotoController) — never from public/. The row holds the path,
 * the server-determined MIME type and when it last changed, nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('photo_path', 255)->nullable()->after('emergency_contact');
            $table->string('photo_mime', 64)->nullable()->after('photo_path');
            $table->timestamp('photo_updated_at')->nullable()->after('photo_mime');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_mime', 'photo_updated_at']);
        });
    }
};
