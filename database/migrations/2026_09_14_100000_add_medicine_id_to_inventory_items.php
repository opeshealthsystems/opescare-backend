<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a facility's inventory item to the global medicine catalogue.
 *
 * The supply-chain schema (`inventory_items`, `stock_batches`,
 * `stock_movements`, `goods_receipts`, `reorder_rules`) shipped in
 * 2026_05_27_000008 keyed everything on a per-facility `inventory_items` row
 * and carried no link to `medicines` — so a batch of Paracetamol at one
 * pharmacy could never be tied to the catalogue entry the patient Medicine
 * Finder searches for. This nullable column is that link. Nullable, because
 * an inventory item can still be a consumable or a reagent with no medicine
 * behind it; unique per facility, because one medicine has exactly one SKU in
 * one pharmacy's ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->uuid('medicine_id')->nullable()->after('facility_id')->index();
            $table->unique(['facility_id', 'medicine_id'], 'inventory_items_facility_medicine_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique('inventory_items_facility_medicine_unique');
            $table->dropColumn('medicine_id');
        });
    }
};
