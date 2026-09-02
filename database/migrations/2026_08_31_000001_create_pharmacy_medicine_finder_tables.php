<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medicine Finder — catalog, per-pharmacy stock, and patient reservations.
 *
 * Why new tables rather than reusing care_map's `pharmacy_stock_availability`:
 * that table keys a medicine by free-text `medicine_name` only, so two
 * pharmacies stocking the same drug are not joinable and a patient cannot be
 * shown "12 pharmacies near you have this". The Medicine Finder needs a
 * canonical catalog row (`medicines`) that stock rows point at by FK.
 * `pharmacy_stock_availability` stays untouched for the CareMap directory.
 *
 * Stock and reservations hang off `care_facilities` (the public directory,
 * which carries latitude/longitude/opening hours) rather than `facilities`
 * (the internal tenant record). `care_facilities.facility_id` links the two,
 * so a pharmacy tenant such as "OpesCare Pharmacy"
 * (facilities.id = 00000000-0000-0000-0000-800000000004) reaches its own
 * stock rows through its directory listing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Canonical medicine catalog ────────────────────────────────────
        Schema::create('medicines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                          // "Paracetamol 500mg Tablet"
            $table->string('generic_name');                   // "Paracetamol"
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable();           // "500mg"
            $table->string('form')->nullable();               // tablet, capsule, syrup, injection
            $table->string('category');                       // App\Enums\MedicineCategory value
            $table->string('atc_code')->nullable();           // WHO ATC, for clinical/interop use
            $table->text('description')->nullable();          // "About this medicine" copy
            $table->jsonb('indications')->nullable();         // ["Pain relief","Fever","Headache"]
            $table->boolean('prescription_required')->default(false);
            $table->boolean('is_controlled')->default(false);
            $table->string('default_pack_size')->nullable();  // "10 tablets"
            $table->jsonb('pack_size_options')->nullable();   // ["10 tablets","20 tablets"]
            $table->decimal('price_min', 10, 2)->nullable();  // indicative national range
            $table->decimal('price_max', 10, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('generic_name');
            $table->index('category');
            $table->index(['is_active', 'category']);
        });

        // ── 2. Per-pharmacy availability of a catalog medicine ───────────────
        Schema::create('medicine_pharmacy_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('medicine_id');
            $table->uuid('care_facility_id');
            $table->string('stock_status')->default('unknown'); // App\Enums\PharmacyStockStatus
            $table->integer('packs_available')->nullable();
            $table->string('pack_size')->nullable();            // pack this price applies to
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->boolean('reservation_enabled')->default(true);
            $table->string('source_system')->nullable();        // manual, bridge-agent, partner-api
            $table->timestamp('last_stocked_at')->nullable();
            $table->timestamp('last_reported_at')->nullable();
            $table->timestamps();

            $table->foreign('medicine_id')->references('id')->on('medicines')->cascadeOnDelete();
            $table->foreign('care_facility_id')->references('id')->on('care_facilities')->cascadeOnDelete();

            $table->unique(['medicine_id', 'care_facility_id'], 'medicine_pharmacy_stocks_unique');
            $table->index(['care_facility_id', 'stock_status']);
            $table->index('stock_status');
        });

        // ── 3. Patient reservations (a hold, never a payment) ────────────────
        Schema::create('medicine_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();           // OC-RX-XXXXXXXX, shown at the counter
            $table->uuid('patient_id');
            $table->uuid('medicine_id');
            $table->uuid('care_facility_id');
            $table->uuid('stock_id')->nullable();            // stock row priced at reservation time
            $table->uuid('prescription_id')->nullable();     // patient's own Rx, when the medicine needs one
            $table->integer('quantity')->default(1);
            $table->string('pack_size')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->string('status')->default('pending');    // App\Enums\MedicineReservationStatus
            $table->text('patient_note')->nullable();
            $table->text('pharmacy_note')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('medicine_id')->references('id')->on('medicines')->cascadeOnDelete();
            $table->foreign('care_facility_id')->references('id')->on('care_facilities')->cascadeOnDelete();
            $table->foreign('stock_id')->references('id')->on('medicine_pharmacy_stocks')->nullOnDelete();
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->nullOnDelete();

            $table->index(['patient_id', 'status']);
            $table->index(['care_facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_reservations');
        Schema::dropIfExists('medicine_pharmacy_stocks');
        Schema::dropIfExists('medicines');
    }
};
