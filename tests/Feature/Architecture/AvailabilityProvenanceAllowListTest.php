<?php

namespace Tests\Feature\Architecture;

use App\Http\Controllers\MedicalId\InventoryPortalController;
use App\Models\BloodAvailability;
use App\Models\LabTestAvailability;
use App\Models\MedicinePharmacyStock;
use App\Models\PharmacyStockAvailability;
use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use App\Modules\Connect\Services\PartnerStockIngestService;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Support\AvailabilityProvenance;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Tests\TestCase;

/**
 * Availability provenance is an ALLOW-LIST (CLAUDE.md "Data invariants").
 *
 * Fails when:
 *  - a synthetic/seed value appears on any LIVE_SOURCE_SYSTEMS;
 *  - a known live writer stamps a value its table would not publish;
 *  - any app/ file that writes an availability table stamps a literal (or a
 *    self:: constant) source that is not on that table's allow-list;
 *  - a seeder stamps a value that WOULD be published as live.
 *
 * Adding a new live writer: add its value to the model's LIVE_SOURCE_SYSTEMS
 * with a docblock line naming the writer. Never add a seeder stamp.
 */
class AvailabilityProvenanceAllowListTest extends TestCase
{
    /** Marker (class/table name in a file) → allow-list it writes into. */
    private const MARKERS = [
        'BloodAvailabilityProjector' => BloodAvailability::class,
        'BloodInventoryService'      => BloodAvailability::class,
        'blood_availability'         => BloodAvailability::class,
        'MedicinePharmacyStock'      => MedicinePharmacyStock::class,
        'medicine_pharmacy_stocks'   => MedicinePharmacyStock::class,
        'PharmacyStockAvailability'  => PharmacyStockAvailability::class,
        'pharmacy_stock_availability' => PharmacyStockAvailability::class,
        'LabTestAvailability'        => LabTestAvailability::class,
        'lab_test_availability'      => LabTestAvailability::class,
    ];

    /**
     * Literals the scan matches that are NOT availability writes. Each entry
     * names the table it actually goes to. Keep this short and justified.
     */
    private const NOT_AVAILABILITY_WRITES = [
        'TerminologyApplier.php:terminology_desk' => 'audit_events.source_system, not lab_test_availability',
    ];

    private const MODELS = [
        BloodAvailability::class,
        MedicinePharmacyStock::class,
        PharmacyStockAvailability::class,
        LabTestAvailability::class,
    ];

    public function test_no_synthetic_value_is_on_any_allow_list(): void
    {
        foreach (self::MODELS as $model) {
            $this->assertNotEmpty($model::LIVE_SOURCE_SYSTEMS, $model);
            foreach (AvailabilityProvenance::SYNTHETIC as $synthetic) {
                $this->assertFalse(
                    AvailabilityProvenance::isLive($synthetic, $model::LIVE_SOURCE_SYSTEMS),
                    "{$model}::LIVE_SOURCE_SYSTEMS publishes synthetic '{$synthetic}'",
                );
            }
            foreach ([null, '', 'demo', 'unknown_new_source', 'partner_api:', 'partnerXapi:abc'] as $bad) {
                $this->assertFalse(
                    AvailabilityProvenance::isLive($bad, $model::LIVE_SOURCE_SYSTEMS),
                    "{$model} publishes " . var_export($bad, true),
                );
            }
        }
    }

    public function test_every_known_live_writer_is_on_its_allow_list(): void
    {
        $partner = app(PartnerStockIngestService::class);
        $portalBlood = (new ReflectionClass(InventoryPortalController::class))->getConstant('BLOOD_SOURCE');

        $writers = [
            [BloodAvailability::class, BloodAvailabilityProjector::SOURCE_OPERATIONAL],
            [BloodAvailability::class, $portalBlood],
            [BloodAvailability::class, $partner->sourceFor(null)],
            [BloodAvailability::class, $partner->sourceFor('client-123')],
            [MedicinePharmacyStock::class, PharmacyStockReportService::SOURCE_PORTAL],
            [MedicinePharmacyStock::class, $partner->sourceFor(null)],
            [MedicinePharmacyStock::class, $partner->sourceFor('client-123')],
            [LabTestAvailability::class, LabTestAvailability::SOURCE_PORTAL],
        ];

        foreach ($writers as [$model, $source]) {
            $this->assertTrue(
                AvailabilityProvenance::isLive($source, $model::LIVE_SOURCE_SYSTEMS),
                "Live writer stamps '{$source}' but {$model}::LIVE_SOURCE_SYSTEMS withholds it",
            );
        }
    }

    public function test_app_writers_only_stamp_allow_listed_sources(): void
    {
        $violations = [];

        foreach ($this->phpFiles(app_path()) as $path) {
            if (str_contains($path, DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR)
                || str_ends_with($path, 'AvailabilityProvenance.php')) {
                continue;
            }
            $src = file_get_contents($path);
            $models = $this->modelsReferenced($src);
            if ($models === []) {
                continue;
            }

            foreach ($this->stampedSources($src, $path) as $source) {
                if (isset(self::NOT_AVAILABILITY_WRITES[basename($path) . ':' . $source])) {
                    continue;
                }
                foreach ($models as $model) {
                    if (! AvailabilityProvenance::isLive($source, $model::LIVE_SOURCE_SYSTEMS)
                        && ! $this->isLiveAnywhere($source)) {
                        $violations[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path)
                            . " stamps '{$source}' (not on {$model}::LIVE_SOURCE_SYSTEMS)";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($violations)),
            "A writer stamps a source no allow-list publishes. If it is a real live source, add it to the model's LIVE_SOURCE_SYSTEMS with the writer named; if it is synthetic, it must be in AvailabilityProvenance::SYNTHETIC.\n"
            . implode("\n", array_unique($violations)));
    }

    public function test_seeders_never_stamp_a_live_source(): void
    {
        $violations = [];

        foreach ($this->phpFiles(database_path('seeders')) as $path) {
            $src = file_get_contents($path);
            $models = $this->modelsReferenced($src);
            foreach ($this->stampedSources($src, null) as $source) {
                foreach ($models as $model) {
                    if (AvailabilityProvenance::isLive($source, $model::LIVE_SOURCE_SYSTEMS)) {
                        $violations[] = basename($path) . " stamps live '{$source}' into {$model}";
                    }
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * A file may touch several tables (the partner ingest writes blood AND
     * medicine stock); a literal aimed at one table is fine if that table
     * publishes it — exact per-table attribution is beyond a text scan.
     */
    private function isLiveAnywhere(string $source): bool
    {
        foreach (self::MODELS as $model) {
            if (AvailabilityProvenance::isLive($source, $model::LIVE_SOURCE_SYSTEMS)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<class-string> */
    private function modelsReferenced(string $src): array
    {
        $models = [];
        foreach (self::MARKERS as $marker => $model) {
            if (preg_match('/\b' . preg_quote($marker, '/') . '\b/', $src)) {
                $models[$model] = $model;
            }
        }

        return array_values($models);
    }

    /**
     * Sources a file stamps: `'source_system' => 'lit'`, `'source_system' =>
     * self::CONST`, and a `self::CONST` passed as the trailing source argument
     * of projectFacility/upsertUnit/adjustUnits/setFlags.
     *
     * @return list<string>
     */
    private function stampedSources(string $src, ?string $path): array
    {
        $out = [];
        preg_match_all("/'source_system'\s*=>\s*'([^']+)'/", $src, $m);
        $out = array_merge($out, $m[1]);

        $consts = [];
        preg_match_all("/'source_system'\s*=>\s*self::([A-Z_]+)/", $src, $m);
        $consts = array_merge($consts, $m[1]);
        preg_match_all('/->(?:projectFacility|upsertUnit|adjustUnits|setFlags)\((?:[^;]*?),\s*self::([A-Z_]+)\s*,?\s*\)/s', $src, $m);
        $consts = array_merge($consts, $m[1]);

        foreach (array_unique($consts) as $const) {
            if (preg_match("/const\s+{$const}\s*=\s*'([^']+)'/", $src, $c)) {
                $out[] = $c[1];
            }
        }

        return array_values(array_unique($out));
    }

    /** @return iterable<string> */
    private function phpFiles(string $dir): iterable
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }
}
