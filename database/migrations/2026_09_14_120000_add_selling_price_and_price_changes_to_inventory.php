<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pharmacy price list.
 *
 * The price a patient sees on the Medicine Finder is
 * `medicine_pharmacy_stocks.unit_price`, a decimal edited row-by-row on the
 * public-availability page, while the ledger behind it (`inventory_items`)
 * carried no selling price at all — so a medicine first created by a goods
 * receipt was published with no price, and nothing remembered when a price
 * moved. `selling_price_xaf` is the one selling price per SKU, in WHOLE
 * FRANCS (XAF has no minor unit; see App\Services\Billing\Xaf), and
 * `inventory_price_changes` is its append-only history — a row is written
 * on every change and never edited or removed (InventoryPriceChange refuses
 * updates and deletes at the model, as StockMovement does).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedInteger('selling_price_xaf')->nullable()->after('unit_cost');
        });

        Schema::create('inventory_price_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('inventory_item_id')->index();
            $table->unsignedInteger('old_price_xaf')->nullable();
            $table->unsignedInteger('new_price_xaf');
            $table->uuid('changed_by')->nullable();
            $table->string('reason', 120)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_price_changes');

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('selling_price_xaf');
        });
    }
};
