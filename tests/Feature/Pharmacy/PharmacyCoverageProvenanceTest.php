<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Role;
use App\Models\User;
use App\Modules\Connect\Services\PartnerStockIngestService;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The pharmacy portal's coverage widget must agree with the public finder.
 *
 * Two surfaces answer the same question — "is this row real enough to show a
 * patient?" — and a pharmacist reads both. `PharmacyStockReportService::coverage()`
 * tells the pharmacy how much of its shelf is published; the public Medicine
 * Finder (`GET /api/v1/care-map/pharmacies/medicine-search`) does the publishing.
 * If the two use different rules, the portal lies about the platform.
 *
 * They diverged: coverage() counted only `source_system = 'portal'`, so a
 * pharmacy syncing its stock through the Connect API saw its own live rows
 * bucketed as seed data while patients were being shown them. The rule now
 * lives in exactly one place — `MedicinePharmacyStock::scopeReportedByRealSource()`,
 * an allow-list that withholds `SYNTHETIC_SOURCE_SYSTEMS` and NULL — and both
 * surfaces read through it.
 *
 * The assertion that matters is the EQUALITY, not the two numbers: a future
 * change to what counts as real should move both sides or fail here.
 */
class PharmacyCoverageProvenanceTest extends TestCase
{
    use RefreshDatabase;

    /** Distinctive catalog token so the finder query matches only this fixture. */
    private const TERM = 'Zentraxel';

    private Facility $facility;
    private CareFacility $listing;
    private User $pharmacist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'Pharmacie du Centre',
            'type'   => 'pharmacy',
            'status' => 'active',
        ]);

        // Active + geocoded: the finder drops listings that are neither, and
        // this test is about provenance, not about listing hygiene.
        $this->listing = CareFacility::create([
            'facility_id'    => $this->facility->id,
            'facility_name'  => 'Pharmacie du Centre',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaoundé',
            'address'        => 'Avenue Kennedy',
            'latitude'       => 3.8480,
            'longitude'      => 11.5021,
            'phone_primary'  => '+237600000042',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'pharmacist'],
            ['description' => 'Pharmacist', 'dashboard_profile_key' => 'pharmacist'],
        );

        $this->pharmacist = User::create([
            'name'                => 'Bertrand Nkeng',
            'email'               => 'bertrand.pharmacist@example.test',
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $this->facility->id,
            'status'              => 'active',
        ]);
        // `role_id` is deliberately not mass-assignable on User.
        $this->pharmacist->role_id = $role->id;
        $this->pharmacist->save();
    }

    /**
     * One stock row at this listing, with the given provenance.
     *
     * Each row gets its own catalog medicine because (medicine_id,
     * care_facility_id) is unique — the same reason a pharmacy cannot report
     * one medicine twice.
     */
    private function stockRow(?string $sourceSystem, string $strength): MedicinePharmacyStock
    {
        $medicine = Medicine::create([
            'name'                  => self::TERM . ' ' . $strength . ' Tablet',
            'generic_name'          => 'Zentraxelium',
            'strength'              => $strength,
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'prescription_required' => false,
            'default_pack_size'     => '10 tablets',
            'currency'              => 'XAF',
            'is_active'             => true,
        ]);

        return MedicinePharmacyStock::create([
            'medicine_id'         => $medicine->id,
            'care_facility_id'    => $this->listing->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 7,
            'pack_size'           => '10 tablets',
            'unit_price'          => 900.00,
            'currency'            => 'XAF',
            'reservation_enabled' => false,
            'source_system'       => $sourceSystem,
            'last_reported_at'    => now()->subHours(2),
        ]);
    }

    /**
     * One row of every provenance this table sees in the wild.
     *
     * The partner value is built from PartnerStockIngestService's real prefix,
     * `partner_api:<client_id>` — not a tidy literal `'partner'`. A fix that
     * swapped one hardcoded list for another ("portal or partner") would still
     * mis-bucket this row, which is exactly the drift the allow-list prevents.
     *
     * @return array{portal:string, partner:string, bridge:string, seeded:string, unclaimed:null}
     */
    private function seedEveryProvenance(): array
    {
        $sources = [
            'portal'    => PharmacyStockReportService::SOURCE_PORTAL,
            'partner'   => PartnerStockIngestService::SOURCE_PREFIX . ':' . Str::uuid()->toString(),
            'bridge'    => 'bridge_agent',
            'seeded'    => MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS[0],
            'unclaimed' => null,
        ];

        $strength = 100;
        foreach ($sources as $source) {
            $this->stockRow($source, $strength . 'mg');
            $strength += 100;
        }

        return $sources;
    }

    /** How many rows for this listing the public Medicine Finder actually serves. */
    private function rowsThePublicFinderPublishes(): int
    {
        $response = $this->getJson(
            '/api/v1/care-map/pharmacies/medicine-search?' . http_build_query(['medicine' => self::TERM]),
        );

        $response->assertOk();

        $mine = array_filter(
            $response->json('data'),
            fn (array $row) => ($row['matched_stock']['facility_id'] ?? null) === $this->listing->id,
        );

        return count($mine);
    }

    private function coverage(): array
    {
        return app(PharmacyStockReportService::class)->coverage($this->listing);
    }

    /**
     * The invariant: the widget's "reported" count IS the finder's published
     * row count. Not "close to", not "a subset of".
     */
    public function test_coverage_reported_equals_what_the_public_finder_publishes(): void
    {
        $this->seedEveryProvenance();

        $coverage  = $this->coverage();
        $published = $this->rowsThePublicFinderPublishes();

        $this->assertSame(
            $published,
            $coverage['reported'],
            'The portal coverage widget must count exactly the rows the public finder serves.',
        );

        // Pin the arithmetic too, so a fix that made both sides equally wrong
        // (count everything, or count nothing) cannot pass on the equality alone.
        $this->assertSame(5, $coverage['total'], 'Five rows exist at this listing.');
        $this->assertSame(3, $published, 'portal + partner + bridge are published; demo_seed and NULL are not.');
        $this->assertSame(2, $coverage['seeded'], 'Seeded and unclaimed rows are the withheld remainder.');
        $this->assertSame(
            $coverage['total'],
            $coverage['reported'] + $coverage['seeded'],
            'Every row belongs to exactly one bucket.',
        );
    }

    /**
     * The reported defect, stated directly: a pharmacy that syncs through the
     * Connect API was told its own live stock was seed data.
     */
    public function test_a_partner_synced_row_counts_as_reported_not_seeded(): void
    {
        $this->stockRow(
            PartnerStockIngestService::SOURCE_PREFIX . ':' . Str::uuid()->toString(),
            '250mg',
        );

        $coverage = $this->coverage();

        $this->assertSame(1, $coverage['reported'], 'A partner sync is a real report about a real shelf.');
        $this->assertSame(0, $coverage['seeded']);
        $this->assertSame(1, $this->rowsThePublicFinderPublishes(), 'And the finder is already publishing it.');
    }

    /**
     * The other half of the allow-list. A row nobody stamped is not evidence a
     * medicine is on a shelf, so it must never inflate the reported count —
     * this is the assertion that stops a future "just count non-seed rows" fix.
     */
    public function test_an_unclaimed_null_provenance_row_is_never_reported(): void
    {
        $this->stockRow(null, '500mg');

        $coverage = $this->coverage();

        $this->assertSame(0, $coverage['reported'], 'NULL provenance is withheld, not counted.');
        $this->assertSame(1, $coverage['seeded']);
        $this->assertSame(0, $this->rowsThePublicFinderPublishes(), 'And the finder withholds it too.');
    }

    /** Seeded fiction stays fiction on both surfaces. */
    public function test_seeded_rows_are_reported_on_neither_surface(): void
    {
        foreach (MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS as $i => $synthetic) {
            $this->stockRow($synthetic, (600 + ($i * 100)) . 'mg');
        }

        $coverage = $this->coverage();

        $this->assertSame(0, $coverage['reported']);
        $this->assertSame(count(MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS), $coverage['seeded']);
        $this->assertSame(0, $this->rowsThePublicFinderPublishes());
    }

    /**
     * The number a pharmacist actually reads. The widget renders
     * `coverage['reported']` of `coverage['total']` through
     * `public.pharmacy_portal.stock_cov_summary`, so the corrected count has to
     * survive the whole controller → Blade path, not just the service call.
     */
    public function test_the_portal_page_shows_the_corrected_coverage_count(): void
    {
        $this->seedEveryProvenance();

        $this->actingAs($this->pharmacist)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get('/portals/pharmacy/stock')
            ->assertOk()
            ->assertSee(
                __('public.pharmacy_portal.stock_cov_summary', ['reported' => 3, 'total' => 5]),
                false,
            );
    }
}
