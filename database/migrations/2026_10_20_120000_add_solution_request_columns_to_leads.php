<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * /solutions/request — a facility asking to buy an OPES health system, or to
 * receive one free (donation). Stored in the existing leads pipeline with
 * source 'solutions' rather than a table of its own, so the admin leads desk
 * shows them beside every other lead. All columns nullable: older leads have
 * none of this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('request_kind', 20)->nullable()->after('source');   // purchase | donation
            $table->string('solution_slug', 64)->nullable()->after('request_kind');
            $table->string('facility_type', 40)->nullable()->after('solution_slug');
            $table->string('city', 120)->nullable()->after('facility_type');
            $table->boolean('has_system')->nullable()->after('city');

            $table->index(['source', 'request_kind']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['source', 'request_kind']);
            $table->dropColumn(['request_kind', 'solution_slug', 'facility_type', 'city', 'has_system']);
        });
    }
};
