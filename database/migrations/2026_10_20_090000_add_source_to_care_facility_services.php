<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a public service line came from.
 *
 * 2026-10-20 (owner's decision): the facility service catalogue
 * (facility_catalogue_items) is the single editor of what a facility offers,
 * and the public services list (care_facility_services) is DERIVED from it by
 * App\Services\CareMap\PublicServicesSync. Rows written that way carry
 * source = 'catalogue' and the catalogue item they mirror; every row that
 * existed before carries source = 'manual' (typed in on the old settings /
 * listing forms, or imported) so the two can be told apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_facility_services', function (Blueprint $table) {
            $table->string('source', 20)->default('manual');
            $table->uuid('catalogue_item_id')->nullable();
            $table->index(['facility_id', 'source']);
            $table->index('catalogue_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('care_facility_services', function (Blueprint $table) {
            $table->dropIndex(['catalogue_item_id']);
            $table->dropIndex(['facility_id', 'source']);
            $table->dropColumn(['source', 'catalogue_item_id']);
        });
    }
};
