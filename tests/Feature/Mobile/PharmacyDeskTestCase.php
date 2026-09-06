<?php

namespace Tests\Feature\Mobile;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Shared fixture for the pharmacy desk API (/api/mobile/pharmacy-desk).
 *
 * One pharmacy (`facility`, with its directory listing), one rival pharmacy
 * (`otherFacility`, with its own listing), the hospital that writes the
 * scripts, one patient, two catalogue medicines stocked at our listing, and
 * a provider session per role. Same session recipe as ProviderRoleMatrixTest:
 * a dashboard_profiles row + Role.dashboard_profile_key so isProfessional()
 * passes, then ProviderSessionService::mint() at the facility under test.
 */
abstract class PharmacyDeskTestCase extends TestCase
{
    use RefreshDatabase;

    protected const HEALTH_ID = 'CM-HID-TEST-DESK-0001';

    protected const BASE = '/api/mobile/pharmacy-desk';

    protected Facility $facility;
    protected Facility $otherFacility;
    protected Facility $hospital;

    protected CareFacility $listing;
    protected CareFacility $otherListing;

    protected Patient $patient;

    protected Medicine $paracetamol;
    protected Medicine $amoxicillin;

    protected MedicinePharmacyStock $paracetamolStock;
    protected MedicinePharmacyStock $amoxicillinStock;

    /** @var array<string, User> keyed by session name */
    protected array $users = [];

    /** @var array<string, string> bearer tokens keyed by session name */
    protected array $bearer = [];

    protected PharmacyDeskService $desk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->desk = app(PharmacyDeskService::class);

        $this->facility      = Facility::factory()->create(['name' => 'Pharmacie du Test', 'type' => 'pharmacy']);
        $this->otherFacility = Facility::factory()->create(['name' => 'Pharmacie Rivale', 'type' => 'pharmacy']);
        $this->hospital      = Facility::factory()->create(['name' => 'Hôpital de Test', 'type' => 'hospital']);

        $this->listing      = $this->listingFor($this->facility, 'Pharmacie du Test (listing)');
        $this->otherListing = $this->listingFor($this->otherFacility, 'Pharmacie Rivale (listing)');

        $this->patient = Patient::create([
            'health_id'     => self::HEALTH_ID,
            'first_name'    => 'Nadia',
            'last_name'     => 'Finder',
            'sex'           => 'female',
            'date_of_birth' => '1992-04-11',
            'is_demo'       => (bool) config('demo.enabled'),
        ]);

        $this->paracetamol = Medicine::create([
            'name'                  => 'Paracetamol 500mg Tablet',
            'generic_name'          => 'Paracetamol',
            'strength'              => '500mg',
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'prescription_required' => false,
            'default_pack_size'     => '10 tablets',
            'currency'              => 'XAF',
        ]);

        $this->amoxicillin = Medicine::create([
            'name'                  => 'Amoxicillin 500mg Capsule',
            'generic_name'          => 'Amoxicillin',
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::Antibiotics->value,
            'prescription_required' => true,
            'default_pack_size'     => '15 capsules',
            'currency'              => 'XAF',
        ]);

        $this->paracetamolStock = $this->stock($this->listing, $this->paracetamol, 20, 1500);
        $this->amoxicillinStock = $this->stock($this->listing, $this->amoxicillin, 4, 3500, PharmacyStockStatus::LowStock);

        // Sessions: the pharmacist at our counter, the same role at the rival
        // counter, and the roles that must be refused.
        $this->providerSession('pharmacist', 'pharmacist', $this->facility);
        $this->providerSession('rival', 'pharmacist', $this->otherFacility);
        $this->providerSession('doctor', 'doctor', $this->hospital);
        $this->providerSession('receptionist', 'receptionist', $this->facility);
        $this->providerSession('stockkeeper', 'medicine_stock', $this->facility);
    }

    // ── Fixtures ──────────────────────────────────────────────────────────

    protected function listingFor(Facility $facility, string $name): CareFacility
    {
        return CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => $name,
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaoundé',
            'address'        => 'Avenue Kennedy, Yaoundé',
            'phone_primary'  => '+237 6 90 00 00 ' . random_int(10, 99),
            'latitude'       => 3.8667,
            'longitude'      => 11.5167,
        ]);
    }

    protected function stock(
        CareFacility $listing,
        Medicine $medicine,
        int $packs,
        float $price,
        PharmacyStockStatus $status = PharmacyStockStatus::InStock,
        ?int $reorderLevel = 5,
    ): MedicinePharmacyStock {
        return MedicinePharmacyStock::create([
            'medicine_id'         => $medicine->id,
            'care_facility_id'    => $listing->id,
            'stock_status'        => $status->value,
            'packs_available'     => $packs,
            'reorder_level'       => $reorderLevel,
            'pack_size'           => $medicine->default_pack_size,
            'unit_price'          => $price,
            'currency'            => 'XAF',
            'reservation_enabled' => true,
            'source_system'       => 'portal',
            'last_stocked_at'     => now(),
            'last_reported_at'    => now(),
        ]);
    }

    /**
     * A user whose role passes isProfessional(), with a provider session at
     * the given facility. Same recipe as ProviderRoleMatrixTest.
     */
    protected function providerSession(string $key, string $role, Facility $facility): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save(); // not mass-assignable

        $user = User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);

        $this->users[$key]  = $user;
        $this->bearer[$key] = app(ProviderSessionService::class)->mint($user, $facility->id, 'desk-test');

        return $user;
    }

    /** An active, unexpired script written at the hospital for our patient. */
    protected function prescription(array $overrides = [], array $items = [['drug_name' => 'Paracetamol 500 mg', 'quantity' => 20]]): Prescription
    {
        $rx = Prescription::create(array_merge([
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->hospital->id,
            'prescribed_by' => $this->users['doctor']->id,
            'status'        => 'active',
            'prescribed_at' => now()->subHour(),
            'expires_at'    => now()->addDays(30),
        ], $overrides));

        foreach ($items as $item) {
            PrescriptionItem::create(array_merge([
                'prescription_id' => $rx->id,
                'drug_name'       => 'Paracetamol 500 mg',
                'dose'            => '1 tablet',
                'frequency'       => 'TDS',
                'route'           => 'oral',
                'duration_days'   => 5,
                'quantity'        => 15,
                'status'          => 'pending',
            ], $item));
        }

        return $rx->fresh(['items']);
    }

    /** A script routed to a facility, at the given bench position. */
    protected function routed(Facility $facility, string $routingStatus = PharmacyDeskService::ROUTING_PENDING, array $overrides = []): Prescription
    {
        $rx = $this->desk->routeToFacility($this->prescription($overrides), $facility);

        if ($routingStatus !== PharmacyDeskService::ROUTING_PENDING) {
            $rx->update(['pharmacy_routing_status' => $routingStatus]);
        }

        return $rx->fresh(['items']);
    }

    protected function reservation(array $overrides = []): MedicineReservation
    {
        $stock = $overrides['stock'] ?? $this->paracetamolStock;
        unset($overrides['stock']);

        return MedicineReservation::create(array_merge([
            'reference'        => 'OC-RX-' . strtoupper(Str::random(8)),
            'patient_id'       => $this->patient->id,
            'medicine_id'      => $stock->medicine_id,
            'care_facility_id' => $stock->care_facility_id,
            'stock_id'         => $stock->id,
            'quantity'         => 2,
            'pack_size'        => $stock->pack_size,
            'unit_price'       => $stock->unit_price,
            'total_price'      => $stock->unit_price * 2,
            'currency'         => 'XAF',
            'status'           => MedicineReservation::STATUS_PENDING,
            'fulfilment_mode'  => MedicineReservation::FULFILMENT_PICKUP,
            'expires_at'       => now()->addDay(),
        ], $overrides));
    }

    // ── Requests ──────────────────────────────────────────────────────────

    protected function as(string $key): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$key]}"]);
    }

    protected function desk(string $key, string $method, string $path, array $body = []): TestResponse
    {
        return $this->as($key)->json($method, self::BASE . $path, $body);
    }

    // ── Assertions ────────────────────────────────────────────────────────

    /** The bell rows written for our patient, newest first. */
    protected function patientNotifications(?string $type = null): \Illuminate\Support\Collection
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', (new Patient())->getMorphClass())
            ->where('notifiable_id', $this->patient->id)
            ->orderByDesc('created_at');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get()->map(function ($row) {
            $row->payload = json_decode($row->data, true);

            return $row;
        });
    }
}
