<?php

namespace Tests\Feature\CareMap;

use App\Modules\CareMap\Services\OsmFacilityNormalizer;
use App\Services\Marketing\LeadConversionService;
use App\Support\FacilityTypeLabel;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every facility type the data can hold has an EN and an FR label, and no
 * staff or public page prints the raw code (pharma_categorie_pro, health_org).
 */
class FacilityTypeLabelTest extends TestCase
{
    /** Every type value written anywhere: the KNOWN list, the lead form, the normalizer families, the seeders. */
    private function everyType(): array
    {
        $types = array_merge(FacilityTypeLabel::KNOWN, LeadConversionService::TYPES);

        $families = (new \ReflectionClassConstant(OsmFacilityNormalizer::class, 'TYPE_FAMILIES'))->getValue();
        foreach ($families as $members) {
            $types = array_merge($types, $members);
        }

        foreach (File::allFiles(database_path('seeders')) as $file) {
            preg_match_all("/'(?:facility_)?type'\s*=>\s*'([a-z_]+)'/", $file->getContents(), $m);
            foreach ($m[1] as $value) {
                // Seeders also use 'type' for non-facility rows; keep only values
                // that look like a facility type the directory would hold.
                if (in_array($value, FacilityTypeLabel::KNOWN, true)) {
                    $types[] = $value;
                }
            }
        }

        return array_values(array_unique($types));
    }

    public function test_every_known_type_has_an_english_and_french_label(): void
    {
        $en = require lang_path('en/finder_directory.php');
        $fr = require lang_path('fr/finder_directory.php');

        foreach ($this->everyType() as $type) {
            $this->assertArrayHasKey($type, $en['types'], "EN label missing for facility type '{$type}'");
            $this->assertArrayHasKey($type, $fr['types'], "FR label missing for facility type '{$type}'");
            $this->assertNotSame('', trim($en['types'][$type]));
            $this->assertNotSame('', trim($fr['types'][$type]));
        }
    }

    public function test_labels_follow_the_locale_and_never_return_the_raw_code(): void
    {
        app()->setLocale('fr');
        $this->assertSame('Centre de santé', FacilityTypeLabel::label('health_center'));
        $this->assertSame('Centre de santé', FacilityTypeLabel::label('Health Center'));

        foreach ($this->everyType() as $type) {
            $label = FacilityTypeLabel::label($type);
            $this->assertStringNotContainsString('finder_directory.types', $label);
            $this->assertStringNotContainsString('_', $label, "Raw code leaked for '{$type}'");
        }

        // An unknown value falls back to the translated generic word.
        $this->assertSame(__('finder_directory.types.other'), FacilityTypeLabel::label('pharma_categorie_pro'));
        app()->setLocale('en');
        $this->assertSame('Facility', FacilityTypeLabel::label('pharma_categorie_pro'));
        $this->assertSame('Facility', FacilityTypeLabel::label(null));
    }
}
