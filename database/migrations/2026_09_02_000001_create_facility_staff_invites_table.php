<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facility staff invitations.
 *
 * Before this table the /invite/{token} pages were a static mock: the GET
 * rendered hardcoded strings and the POST wrote nothing, so the only way to
 * create a clinician was a platform admin minting a user by hand — and that
 * path never set primary_facility_id, which dropped the new account straight
 * into the /select-facility dead end.
 *
 * Design notes that are load-bearing:
 *  - `token_hash` holds sha256(raw token). The raw token exists only in the
 *    invite URL. A database dump therefore cannot be replayed as a login.
 *  - `accepted_at` makes an invite single-use; `expires_at` makes it expire.
 *    Both are enforced under a row lock so two concurrent POSTs to the same
 *    link cannot both create an account.
 *  - `facility_id` and `role_id` are decided by the ISSUER and frozen here.
 *    Acceptance never reads either of them from the request, which is what
 *    keeps an invite from being redirected at another facility or escalated
 *    to a platform role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_staff_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('facility_id');
            $table->uuid('role_id');

            $table->string('email');
            $table->string('name')->nullable();

            // sha256 hex digest of the raw token — never the token itself.
            $table->string('token_hash', 64)->unique();

            $table->uuid('invited_by')->nullable();
            $table->timestampTz('expires_at');

            $table->timestampTz('accepted_at')->nullable();
            $table->uuid('accepted_user_id')->nullable();

            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('revoked_by')->nullable();

            $table->timestampsTz();

            $table->index(['facility_id', 'accepted_at']);
            $table->index('email');

            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('accepted_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_staff_invites');
    }
};
