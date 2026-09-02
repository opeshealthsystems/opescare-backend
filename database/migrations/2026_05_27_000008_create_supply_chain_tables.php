<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Suppliers ─────────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        // ── Inventory Items (catalog) ─────────────────────────
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('name', 150);
            $table->string('code', 50)->nullable()->index();
            $table->string('category', 80)->nullable(); // medicine, consumable, equipment, reagent, etc.
            $table->string('unit', 30)->default('unit'); // tablet, vial, box, kg, etc.
            $table->integer('reorder_level')->default(0);
            $table->integer('reorder_quantity')->nullable();
            $table->boolean('track_expiry')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->text('description')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });

        // ── Stock Locations ───────────────────────────────────
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('name', 100);
            $table->string('code', 30)->nullable();
            $table->string('type', 50)->default('store'); // store, ward, pharmacy, lab, theatre
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Stock Batches (lot tracking) ──────────────────────
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id')->index();
            $table->uuid('location_id')->index();
            $table->uuid('facility_id')->index();
            $table->string('batch_number', 80)->nullable();
            $table->string('lot_number', 80)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->integer('quantity_in')->default(0);
            $table->integer('quantity_out')->default(0);
            $table->integer('quantity_adjusted')->default(0);
            $table->integer('quantity_available')->storedAs('quantity_in - quantity_out + quantity_adjusted');
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->enum('status', ['active', 'expired', 'quarantine', 'recalled'])->default('active');
            $table->uuid('supplier_id')->nullable()->index();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        // ── Stock Movements ───────────────────────────────────
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('inventory_item_id')->index();
            $table->uuid('batch_id')->nullable()->index();
            $table->uuid('from_location_id')->nullable()->index();
            $table->uuid('to_location_id')->nullable()->index();
            $table->enum('movement_type', [
                'receipt',       // goods received
                'dispense',      // dispensed to patient/ward
                'transfer',      // inter-location transfer
                'adjustment',    // stock count correction
                'return',        // returned from ward/patient
                'write_off',     // expired/damaged write-off
                'opening_stock', // initial balance
            ]);
            $table->integer('quantity');
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->string('reference_type', 50)->nullable(); // purchase_order, dispense_record, etc.
            $table->uuid('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('performed_by', 100)->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });

        // ── Stock Adjustments ─────────────────────────────────
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('inventory_item_id')->index();
            $table->uuid('batch_id')->nullable();
            $table->uuid('location_id')->index();
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('adjustment_quantity')->storedAs('quantity_after - quantity_before');
            $table->enum('adjustment_type', ['count_correction', 'damage', 'expired', 'found', 'other']);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('approved');
            $table->string('approved_by', 100)->nullable();
            $table->string('requested_by', 100)->nullable();
            $table->timestamps();
        });

        // ── Purchase Orders ───────────────────────────────────
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('supplier_id')->nullable()->index();
            $table->string('po_number', 50)->nullable()->index();
            $table->enum('status', ['draft', 'submitted', 'approved', 'sent', 'partial', 'received', 'cancelled'])->default('draft');
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('total_amount', 16, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('approved_by', 100)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // ── Purchase Order Items ──────────────────────────────
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id')->index();
            $table->uuid('inventory_item_id')->index();
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_price', 14, 4)->nullable();
            $table->decimal('total_price', 14, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Goods Receipts ────────────────────────────────────
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('purchase_order_id')->nullable()->index();
            $table->uuid('supplier_id')->nullable()->index();
            $table->uuid('location_id')->nullable()->index();
            $table->string('receipt_number', 50)->nullable();
            $table->date('received_date');
            $table->string('received_by', 100)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Goods Receipt Items ───────────────────────────────
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receipt_id')->index();
            $table->uuid('inventory_item_id')->index();
            $table->uuid('purchase_order_item_id')->nullable();
            $table->integer('quantity_received');
            $table->string('batch_number', 80)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Reorder Rules ─────────────────────────────────────
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id')->index();
            $table->uuid('location_id')->nullable()->index();
            $table->integer('reorder_level');
            $table->integer('reorder_quantity');
            $table->uuid('preferred_supplier_id')->nullable();
            $table->boolean('auto_alert')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('stock_locations');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};
