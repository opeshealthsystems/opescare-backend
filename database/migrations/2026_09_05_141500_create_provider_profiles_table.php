<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The clinician's own professional profile — the storage behind
 * `GET|PUT /api/mobile/doctor/profile` and `POST /api/mobile/doctor/profile/photo`.
 *
 * Why a new table rather than more columns on `staff_profiles`:
 *
 *   • `staff_profiles` is an HR record. It is created and edited by a facility
 *     administrator, it is keyed to a facility, and a clinician may have one per
 *     posting. What a clinician publishes about themselves — specialty, bio,
 *     languages, headshot — is one row per PERSON and follows them between
 *     facilities. Folding the two together would make "my profile" mean
 *     different things depending on which posting was read.
 *
 *   • Nothing in this product stored specialty / subspecialty / bio /
 *     years of experience / languages / room / photo anywhere at all. The
 *     mobile contract (`lib/api/doctorQueries.ts` → `ProviderProfile`) requires
 *     every one of them, so the alternative to this table is serving fields the
 *     server cannot remember.
 *
 * What is deliberately NOT here:
 *
 *   • `license_status` — asserted by credentialing, derived at read time from
 *     `provider_credentials` (verified_at / expiry_date / status). A clinician
 *     must never be able to write their own licence standing.
 *   • `consultation_fee` / `currency` — no fee schedule exists in the Finder
 *     product. The API reports both as null rather than inventing a price.
 *
 * `photo_path` is a path on the `local` disk (storage/app/private) — OUTSIDE the
 * webroot. It is served only through a temporary signed route; there is no
 * public URL for it and no symlink into public/.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_profiles')) {
            return;
        }

        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // One row per clinician. Unique, so "my profile" is never ambiguous.
            $table->uuid('user_id')->unique();

            $table->string('title', 120)->nullable();
            $table->string('specialty', 120)->nullable();
            $table->string('subspecialty', 120)->nullable();
            $table->text('bio')->nullable();

            // Relative path on the `local` disk + the MIME the server itself
            // determined from the file's CONTENT. The client's Content-Type is
            // never stored, so it can never be echoed back as the served type.
            $table->string('photo_path')->nullable();
            $table->string('photo_mime', 60)->nullable();

            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->jsonb('languages')->nullable();
            $table->jsonb('qualifications')->nullable();
            $table->string('room', 120)->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
