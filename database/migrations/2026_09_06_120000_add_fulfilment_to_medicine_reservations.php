<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pharmacy desk — the pharmacy's side of a medicine reservation, and a stock
 * ledger the desk can write to.
 *
 * `medicine_reservations`
 *   A reservation used to be a pickup hold and nothing else. The desk now
 *   accepts it, prepares it, and either hands it over at the counter or sends
 *   it out for delivery, so the row gains:
 *     - fulfilment_mode   'pickup' (the default, so every existing row keeps
 *                         its meaning) or 'delivery'
 *     - delivery_address / delivery_phone   where a delivery goes
 *     - delivery_status   the delivery leg ('dispatched' | 'delivered'), kept
 *                         SEPARATE from `status` so the existing
 *                         MedicineReservationStatus enum — which every patient
 *                         read casts through — never meets a value it does not
 *                         know. See MedicineReservation::deskStatus().
 *     - accepted_at / ready_at / dispatched_at / delivered_at   one timestamp
 *                         per desk step. collected_at already existed.
 *
 * `medicine_pharmacy_stocks.reorder_level`
 *   The threshold under which a line is "low" for THIS pharmacy. Nullable: a
 *   line that has never been given one uses the platform default
 *   (MedicinePharmacyStock::DEFAULT_REORDER_LEVEL).
 *
 * `stock_movements.inventory_item_id` → nullable
 *   The desk records every stock adjustment as a StockMovement, which is the
 *   platform's stock ledger. That table was built for the supply-chain
 *   module's `inventory_items`; a pharmacy's public listing line is a
 *   `medicine_pharmacy_stocks` row, not an inventory item, and lying about
 *   that by stuffing a stock id into `inventory_item_id` would let a
 *   supply-chain report join it to nothing — or to the wrong thing. The
 *   pharmacy rows carry `reference_type = 'medicine_pharmacy_stock'` and
 *   `reference_id` instead, and leave `inventory_item_id` NULL. Loosening the
 *   NOT NULL is the only change; every existing writer still supplies it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('medicine_reservations', 'fulfilment_mode')) {
                $table->string('fulfilment_mode', 16)->default('pickup')->after('status');
            }
            if (! Schema::hasColumn('medicine_reservations', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('fulfilment_mode');
            }
            if (! Schema::hasColumn('medicine_reservations', 'delivery_phone')) {
                $table->string('delivery_phone', 30)->nullable()->after('delivery_address');
            }
            if (! Schema::hasColumn('medicine_reservations', 'delivery_status')) {
                $table->string('delivery_status', 16)->nullable()->after('delivery_phone');
            }
            foreach (['accepted_at', 'ready_at', 'dispatched_at', 'delivered_at', 'collected_at'] as $column) {
                if (! Schema::hasColumn('medicine_reservations', $column)) {
                    $table->timestampTz($column)->nullable();
                }
            }
        });

        Schema::table('medicine_reservations', function (Blueprint $table) {
            $table->index(['care_facility_id', 'fulfilment_mode', 'delivery_status'], 'medicine_reservations_desk_delivery_idx');
        });

        Schema::table('medicine_pharmacy_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('medicine_pharmacy_stocks', 'reorder_level')) {
                $table->integer('reorder_level')->nullable()->after('packs_available');
            }
        });

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'inventory_item_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->uuid('inventory_item_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('medicine_reservations', function (Blueprint $table) {
            $table->dropIndex('medicine_reservations_desk_delivery_idx');
            $table->dropColumn([
                'fulfilment_mode', 'delivery_address', 'delivery_phone', 'delivery_status',
                'accepted_at', 'ready_at', 'dispatched_at', 'delivered_at',
            ]);
        });

        Schema::table('medicine_pharmacy_stocks', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });

        // `inventory_item_id` is deliberately left nullable: re-imposing NOT NULL
        // would fail on any pharmacy movement written in the meantime.
    }
};
