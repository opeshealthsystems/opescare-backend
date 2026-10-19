<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\GoodsReceipt;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\PharmacyStockImport;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\User;
use App\Support\AvailabilityProvenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;
use ZipArchive;

/**
 * Stock import from a spreadsheet: template, upload, column mapping, matching,
 * preview, commit — and the rules that keep it honest:
 *
 *   - committed lines publish through the pharmacy desk ('portal'), so they
 *     reach the patient finder and the provenance allow-list is unchanged;
 *   - a line that matches no catalogue medicine is flagged, never invented;
 *   - another pharmacy's import does not exist (404); a role without
 *     pharmacy_stock is refused (403);
 *   - the error report cannot carry a spreadsheet formula.
 */
class StockImportTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $amox;
    private Medicine $pcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        [$this->facility, $this->listing] = $this->pharmacy('PharmaPlus Akwa');

        $this->amox = Medicine::create([
            'name' => 'Amoxicillin 500mg Capsule', 'generic_name' => 'Amoxicillin', 'strength' => '500mg', 'form' => 'capsule',
            'category' => MedicineCategory::PainRelief->value, 'atc_code' => 'J01CA04', 'currency' => 'XAF',
        ]);
        $this->pcm = Medicine::create([
            'name' => 'Paracetamol 500mg Tablet', 'generic_name' => 'Paracetamol', 'strength' => '500mg', 'form' => 'tablet',
            'category' => MedicineCategory::PainRelief->value, 'atc_code' => 'N02BE01', 'currency' => 'XAF',
        ]);
    }

    /** @return array{0: Facility, 1: CareFacility} */
    private function pharmacy(string $name): array
    {
        $facility = Facility::create(['name' => $name, 'type' => 'pharmacy', 'status' => 'active']);
        $listing  = CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $name, 'facility_type' => 'pharmacy',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Rue Joss, Akwa', 'latitude' => 4.0520, 'longitude' => 9.7690, 'phone_primary' => '+237600000001',
        ]);

        return [$facility, $listing];
    }

    private function staff(string $roleName, ?Facility $facility = null): User
    {
        $facility ??= $this->facility;
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id, 'is_active' => true, 'assigned_at' => now(),
        ]);

        return $user;
    }

    private function as(User $user, ?Facility $facility = null): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => ($facility ?? $this->facility)->id]);
    }

    private function csvUpload(string $content, string $name = 'stock.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'stk');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** A real .xlsx with shared strings and a numeric cell, written the way Excel does. @param list<list<string|int>> $rows */
    private function xlsxUpload(array $rows): UploadedFile
    {
        $shared = [];
        $sheet  = '';
        foreach ($rows as $r => $row) {
            $sheet .= '<row r="' . ($r + 1) . '">';
            foreach ($row as $c => $v) {
                $ref = chr(65 + $c) . ($r + 1);
                if (is_int($v)) {
                    $sheet .= "<c r=\"$ref\"><v>$v</v></c>";
                } else {
                    $shared[] = htmlspecialchars($v, ENT_XML1);
                    $sheet .= "<c r=\"$ref\" t=\"s\"><v>" . (count($shared) - 1) . '</v></c>';
                }
            }
            $sheet .= '</row>';
        }
        $path = tempnam(sys_get_temp_dir(), 'stk');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Stock" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . implode('', array_map(fn ($s) => "<si><t>$s</t></si>", $shared)) . '</sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheet . '</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, 'stock.xlsx', null, null, true);
    }

    private function upload(User $user, UploadedFile $file): PharmacyStockImport
    {
        $this->as($user)->post('/pharmacy/stock/import', ['file' => $file])->assertRedirect();

        return PharmacyStockImport::where('facility_id', $this->facility->id)->latest()->firstOrFail();
    }

    private function confirmMapping(User $user, PharmacyStockImport $import, string $mode = 'delivery'): PharmacyStockImport
    {
        $map = array_map(fn ($c) => $c ?? '', $import->mapping);
        $this->as($user)->post("/pharmacy/stock/import/{$import->id}/mapping", ['map' => $map, 'mode' => $mode])
            ->assertRedirect("/pharmacy/stock/import/{$import->id}");

        return $import->fresh();
    }

    private function expiry(): string
    {
        return now()->addYear()->format('d/m/Y');
    }

    // ── Template ──────────────────────────────────────────────────────────

    public function test_the_template_downloads_as_csv_and_as_a_readable_xlsx(): void
    {
        $user = $this->staff('pharmacist');

        $csv = $this->as($user)->get('/pharmacy/stock/import/template.csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $this->assertStringContainsString('Medicine name', $csv->getContent());
        $this->assertStringContainsString('Expiry date', $csv->getContent());

        $xlsx = $this->as($user)->get('/pharmacy/stock/import/template.xlsx');
        $xlsx->assertOk();
        $path = $xlsx->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet2.xml'), 'the instructions sheet is there');
        $zip->close();

        // Our own reader can read our own template back, and maps every column.
        $parsed = app(\App\Services\Pharmacy\StockImport\SpreadsheetReader::class)->read($path);
        $this->assertSame('xlsx', $parsed['format']);
        $map = app(\App\Services\Pharmacy\StockImport\ColumnMapper::class)->guess($parsed['rows'][0]);
        $this->assertNotContains(null, $map, 'every template header is recognised');
    }

    public function test_the_french_template_has_french_headers_that_map_back(): void
    {
        app()->setLocale('fr');
        $tpl = app(\App\Services\Pharmacy\StockImport\StockTemplate::class);
        $this->assertContains('Désignation', $tpl->headers());
        $map = app(\App\Services\Pharmacy\StockImport\ColumnMapper::class)->guess($tpl->headers());
        $this->assertNotContains(null, $map);
    }

    // ── Parsing and mapping ───────────────────────────────────────────────

    public function test_a_csv_delivery_is_matched_previewed_and_published_to_the_patient_finder_as_portal_stock(): void
    {
        $user = $this->staff('pharmacist');
        $csv = "Medicine name,Quantity,Selling price,Expiry date,Batch\n"
            . "Amoxicillin 500mg Capsule,30,2 500 FCFA,{$this->expiry()},AMX-1\n"
            . "Paracetamol 500 mg tablet,12,500,12/2027,PCM-1\n";

        $import = $this->upload($user, $this->csvUpload($csv));
        $this->assertSame('csv', $import->file_format);
        $import = $this->confirmMapping($user, $import);

        $this->assertSame(['ok', 'ok'], array_column($import->results, 'status'));
        $this->assertSame('exact', $import->results[0]['method']);
        $this->assertSame('normalised', $import->results[1]['method']);

        // Nothing is published before the pharmacist confirms.
        $this->assertSame(0, MedicinePharmacyStock::count());

        $this->as($user)->post("/pharmacy/stock/import/{$import->id}/commit", ['confirm' => '1'])->assertRedirect();

        $import->refresh();
        $this->assertSame('committed', $import->status);
        $this->assertSame(2, $import->imported_count);
        $this->assertNull($import->rows, 'the sheet cells are dropped once committed');
        $this->assertNotNull(GoodsReceipt::find($import->goods_receipt_id));

        $row = MedicinePharmacyStock::where('care_facility_id', $this->listing->id)->where('medicine_id', $this->amox->id)->firstOrFail();
        $this->assertSame(30, $row->packs_available);
        $this->assertSame(2500.0, $row->unit_price);
        $this->assertSame('portal', $row->source_system);
        $this->assertTrue(AvailabilityProvenance::isLive($row->source_system, MedicinePharmacyStock::LIVE_SOURCE_SYSTEMS));

        // …and the patient finder shows it.
        $patient = Patient::create(['health_id' => 'OC-TST-7401-0001-01', 'first_name' => 'A', 'last_name' => 'B', 'sex' => 'female', 'date_of_birth' => '1990-01-01', 'is_demo' => false]);
        $res = $this->mobileGetJson($patient, '/api/mobile/pharmacy/nearby?' . http_build_query(['lat' => 4.0511, 'lng' => 9.7679, 'radius_km' => 5, 'medicine_id' => $this->amox->id]));
        $res->assertOk();
        $this->assertContains($this->listing->id, array_column($res->json('data'), 'id'));
    }

    public function test_french_headers_semicolons_and_windows_1252_are_read(): void
    {
        $user = $this->staff('pharmacist');
        $csv = "Pharmacie du Port — inventaire\n\n"
            . "Désignation;DCI;Qté;Prix;Péremption;N° de lot\n"
            . "Amoxicilline gélules;Amoxicillin 500 mg gélule;7;1 800;{$this->expiry()};L-É1\n";

        $import = $this->upload($user, $this->csvUpload(mb_convert_encoding($csv, 'Windows-1252', 'UTF-8')));

        $this->assertSame(2, $import->header_row, 'the title block above the headers is skipped');
        $this->assertSame('Désignation', $import->headers[0], 'Windows-1252 accents arrive intact');
        $this->assertSame(0, $import->mapping['medicine']);
        $this->assertSame(1, $import->mapping['inn']);
        $this->assertSame(2, $import->mapping['quantity']);
        $this->assertSame(3, $import->mapping['unit_price']);
        $this->assertSame(4, $import->mapping['expiry']);
        $this->assertSame(5, $import->mapping['batch']);

        $import = $this->confirmMapping($user, $import);
        $this->assertSame('ok', $import->results[0]['status']);
        $this->assertSame($this->amox->id, $import->results[0]['medicine_id'], 'matched through the DCI + French form word');
        $this->assertSame('L-É1', $import->results[0]['batch']);
    }

    public function test_an_xlsx_file_is_read(): void
    {
        $user = $this->staff('pharmacy_manager');
        $import = $this->upload($user, $this->xlsxUpload([
            ['Produit', 'Quantité', 'Date de péremption'],
            ['Paracetamol 500mg Tablet', 40, (string) now()->addYear()->format('Y-m-d')],
        ]));

        $this->assertSame('xlsx', $import->file_format);
        $import = $this->confirmMapping($user, $import);
        $this->assertSame('ok', $import->results[0]['status']);
        $this->assertSame(40, $import->results[0]['quantity']);
    }

    public function test_unmatched_and_invalid_lines_are_flagged_and_never_imported(): void
    {
        $user = $this->staff('pharmacist');
        $csv = "Medicine,Qty,Expiry\n"
            . "Unobtainium 10mg Tablet,5,{$this->expiry()}\n"
            . "Amoxicillin 500mg Capsule,five,{$this->expiry()}\n"
            . "Paracetamol 500mg Tablet,3,01/01/2020\n"
            . "Amoxicillin 500mg Capsule,4,{$this->expiry()}\n";

        $import = $this->confirmMapping($user, $this->upload($user, $this->csvUpload($csv)));
        $statuses = array_column($import->results, 'status');
        $this->assertSame(['error', 'error', 'error', 'ok'], $statuses);
        $this->assertSame('unmatched', $import->results[0]['method']);
        $this->assertSame('err_row_quantity', $import->results[1]['errors'][0][0]);
        $this->assertSame('err_row_expired', $import->results[2]['errors'][0][0]);

        $this->as($user)->post("/pharmacy/stock/import/{$import->id}/commit", ['confirm' => '1']);

        $this->assertSame(2, Medicine::count(), 'no medicine is created from a file');
        $this->assertSame(1, StockBatch::where('facility_id', $this->facility->id)->count());
        $this->assertSame(3, $import->fresh()->error_count);
    }

    public function test_a_stocktake_corrects_an_existing_batch_instead_of_adding_to_it(): void
    {
        $user = $this->staff('pharmacist');
        $first = "Medicine,Qty,Batch\nAmoxicillin 500mg Capsule,30,AMX-1\n";
        $import = $this->confirmMapping($user, $this->upload($user, $this->csvUpload($first)));
        $this->as($user)->post("/pharmacy/stock/import/{$import->id}/commit", ['confirm' => '1']);

        $count = "Medicine,Qty,Batch\nAmoxicillin 500mg Capsule,22,AMX-1\n";
        $import = $this->confirmMapping($user, $this->upload($user, $this->csvUpload($count)), 'stocktake');
        $this->as($user)->post("/pharmacy/stock/import/{$import->id}/commit", ['confirm' => '1']);

        $this->assertSame(1, StockBatch::where('facility_id', $this->facility->id)->count());
        $this->assertSame(22, StockBatch::where('facility_id', $this->facility->id)->first()->availableQty());
        $this->assertSame(22, MedicinePharmacyStock::where('medicine_id', $this->amox->id)->value('packs_available'));
    }

    public function test_pdf_and_legacy_xls_are_refused_with_a_reason(): void
    {
        $user = $this->staff('pharmacist');

        $this->as($user)->post('/pharmacy/stock/import', ['file' => $this->csvUpload("%PDF-1.4\n...", 'stock.pdf')])
            ->assertSessionHasErrors('file');
        $this->as($user)->post('/pharmacy/stock/import', ['file' => $this->csvUpload("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 100), 'stock.xls')])
            ->assertSessionHasErrors(['file' => __('pharmacy_stock_import.err_legacy_xls')]);
        // A PDF renamed to .csv is still a PDF — the bytes decide.
        $this->as($user)->post('/pharmacy/stock/import', ['file' => $this->csvUpload("%PDF-1.4\n...", 'stock.csv')])
            ->assertSessionHasErrors(['file' => __('pharmacy_stock_import.err_pdf')]);

        $this->assertSame(0, PharmacyStockImport::count());
    }

    // ── Scope and roles ───────────────────────────────────────────────────

    public function test_another_pharmacys_import_does_not_exist(): void
    {
        $user = $this->staff('pharmacist');
        $import = $this->upload($user, $this->csvUpload("Medicine,Qty\nAmoxicillin 500mg Capsule,3\n"));

        [$other] = $this->pharmacy('Pharmacie Rivale');
        $rival = $this->staff('pharmacist', $other);

        $this->as($rival, $other)->get("/pharmacy/stock/import/{$import->id}/mapping")->assertNotFound();
        $this->as($rival, $other)->post("/pharmacy/stock/import/{$import->id}/commit", ['confirm' => '1'])->assertNotFound();
        $this->as($rival, $other)->get("/pharmacy/stock/import/{$import->id}/errors.csv")->assertNotFound();
    }

    public function test_a_role_without_pharmacy_stock_is_refused(): void
    {
        $receptionist = $this->staff('receptionist');

        $this->as($receptionist)->get('/pharmacy/stock/import')->assertForbidden();
        $this->as($receptionist)->post('/pharmacy/stock/import', ['file' => $this->csvUpload("a,b\n1,2\n")])->assertForbidden();
        $this->assertSame(0, PharmacyStockImport::count());
    }

    public function test_the_import_page_is_linked_from_the_pharmacist_sidebar(): void
    {
        $this->as($this->staff('pharmacist'))->get('/pharmacy/stock/import')
            ->assertOk()
            ->assertSee(route('portals.pharmacy.stock.import'), false)
            ->assertSee(route('portals.pharmacy.stock.import.template', 'xlsx'), false);
    }

    // ── Error report ──────────────────────────────────────────────────────

    public function test_the_error_report_neutralises_formulas(): void
    {
        $user = $this->staff('pharmacist');
        $csv = "Medicine,Qty\n\"=HYPERLINK(\"\"http://evil\"\",\"\"x\"\")\",3\n@SUM(A1),2\n";
        $import = $this->confirmMapping($user, $this->upload($user, $this->csvUpload($csv)));

        $res = $this->as($user)->get("/pharmacy/stock/import/{$import->id}/errors.csv");
        $res->assertOk();
        $body = $res->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringContainsString("'@SUM(A1)", $body);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?=HYPERLINK/m', $body);
    }
}
