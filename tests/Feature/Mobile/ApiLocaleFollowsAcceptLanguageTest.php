<?php

namespace Tests\Feature\Mobile;

use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\Patient;
use App\Support\PatientVocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The phone app sends Accept-Language from the user's chosen language; every
 * server-generated word on /api/* (consent labels, validation errors) must
 * come back in that language, and in English when no header is sent.
 */
class ApiLocaleFollowsAcceptLanguageTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8600-0001-01',
            'first_name'    => 'Lucie',
            'last_name'     => 'Langue',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-03',
        ]);

        $row = new ConsentRequest();
        $row->patient_id             = $this->patient->id;
        $row->requesting_facility_id = Facility::factory()->create()->id;
        $row->purpose                = 'treatment';
        $row->requested_scope        = ['patient.summary'];
        $row->duration_minutes       = 60;
        $row->status                 = 'pending_patient_approval';
        $row->save();
    }

    private function list(?string $lang, string $query = '')
    {
        $headers = $this->mobileAuthHeaders($this->patient);
        if ($lang !== null) {
            $headers['Accept-Language'] = $lang;
        }

        return $this->withHeaders($headers)->getJson('/api/mobile/consent-requests' . $query);
    }

    private function expected(string $locale): array
    {
        return [
            'scope'    => PatientVocabulary::scopes(['patient.summary'], $locale)['items'],
            'duration' => PatientVocabulary::duration(60, $locale)['label'],
        ];
    }

    public function test_french_header_returns_french_labels(): void
    {
        $item = $this->list('fr-CM,fr;q=0.9')->assertOk()->json('0');
        $fr = $this->expected('fr');

        $this->assertNotSame($this->expected('en')['duration'], $fr['duration']);
        $this->assertSame($fr['scope'], $item['scope_items']);
        $this->assertSame($fr['duration'], $item['duration_label']);
    }

    public function test_english_header_returns_english_labels(): void
    {
        $item = $this->list('en')->assertOk()->json('0');
        $en = $this->expected('en');

        $this->assertSame($en['scope'], $item['scope_items']);
        $this->assertSame($en['duration'], $item['duration_label']);
    }

    public function test_missing_header_returns_english_labels(): void
    {
        $item = $this->list(null)->assertOk()->json('0');

        $this->assertSame($this->expected('en')['duration'], $item['duration_label']);
    }

    public function test_validation_errors_come_back_in_french(): void
    {
        $en = $this->list('en', '?status=whatever')->assertStatus(422)->json('errors.status.0');
        $fr = $this->list('fr', '?status=whatever')->assertStatus(422)->json('errors.status.0');

        $this->assertNotSame($en, $fr);
        $this->assertStringContainsStringIgnoringCase('invalid', $en);
        $this->assertStringContainsStringIgnoringCase('invalide', $fr);
    }
}
