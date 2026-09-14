<?php

namespace Tests\Feature\Documents;

use App\Models\Patient;
use App\Services\Documents\DocumentDataAssembler;
use App\Services\Documents\DocumentOnDemandAssembler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit finding S9: the ward charts and on-demand forms were assembled from
 * hardcoded English that scripts/i18n-audit.php could not see, because those
 * strings never entered the lang tree. On a platform that is bilingual by
 * regulation, a French-speaking patient was handed "Not recorded" and
 * "Assigned Nurse" whatever locale the request resolved.
 *
 * Both assemblers now read lang/{en,fr}/document.php and format dates with
 * Carbon::isoFormat(), so the document follows app()->getLocale() — which
 * App\Http\Middleware\SetLocale sets from the patient's own session or
 * Accept-Language when they download a document themselves.
 *
 * Instrument item labels for the Morse Fall Scale are deliberately left in
 * English in the French file (no validated French wording was verified), so
 * they are not in the "must be absent" list here.
 */
class WardDocumentsAreBilingualTest extends TestCase
{
    use RefreshDatabase;

    /** English chrome that used to be hardcoded and must not survive a French render. */
    private const ENGLISH_CHROME = [
        'Not recorded',
        'Not assigned',
        'Pending',
        'Pending documentation',
        'Pending assessment',
        'Assigned Nurse',
        'Attending Physician',
        'Nursing Staff',
        'Nursing Team',
        'None known',
        'None documented',
        'Not documented',
        'Unknown Patient',
        'Outgoing shift',
        'Incoming shift',
        'Per prescription',
        'Braden Scale',
        'Sensory Perception',
        'Room air',
        'N/A',
    ];

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-WARD-0001',
            'first_name' => 'Amina',
            'last_name'  => 'Ngo',
        ]);
    }

    /** @return array<string, array<string|int, mixed>> every document keyed by its assembler method */
    private function assembleEverything(): array
    {
        $ward     = app(DocumentDataAssembler::class);
        $onDemand = app(DocumentOnDemandAssembler::class);
        $id       = (string) $this->patient->id;

        return [
            'assembleMar'                       => $ward->assembleMar($id),
            'assembleIcuFlowsheet'              => $ward->assembleIcuFlowsheet($id),
            'assembleNursingChart'              => $ward->assembleNursingChart($id),
            'assembleDailyProgressNote'         => $ward->assembleDailyProgressNote($id),
            'assembleGlucoseLog'                => $ward->assembleGlucoseLog($id),
            'assembleHandoverNote'              => $ward->assembleHandoverNote($id),
            'assemblePartograph'                => $ward->assemblePartograph($id),
            'assembleNicuChart'                 => $ward->assembleNicuChart($id),
            'assembleGrowthChart'               => $ward->assembleGrowthChart($id),
            'assembleSurgicalSafetyChecklist'   => $onDemand->assembleSurgicalSafetyChecklist($id),
            'assembleNursingAdmissionAssessment' => $onDemand->assembleNursingAdmissionAssessment($id),
            'assembleInvestigationRequest'      => $onDemand->assembleInvestigationRequest($id),
            'assembleFallRiskAssessment'        => $onDemand->assembleFallRiskAssessment($id),
            'assemblePressureUlcerAssessment'   => $onDemand->assemblePressureUlcerAssessment($id),
            'assembleWoundCareChart'            => $onDemand->assembleWoundCareChart($id),
        ];
    }

    /** Every string leaf (values and array keys) of a nested document array, as "path => value". */
    private function leaves(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_string($key)) {
                $out["{$path}#key"] = $key;
            }
            if (is_array($value)) {
                $out += $this->leaves($value, $path);
            } elseif (is_string($value)) {
                $out[$path] = $value;
            }
        }

        return $out;
    }

    public function test_a_french_reader_gets_no_english_chrome_in_any_ward_document(): void
    {
        app()->setLocale('fr');

        $offenders = [];
        foreach ($this->assembleEverything() as $method => $document) {
            foreach ($this->leaves($document) as $path => $value) {
                if (in_array($value, self::ENGLISH_CHROME, true)) {
                    $offenders[] = "{$method}.{$path} = \"{$value}\"";
                }
            }
        }

        $this->assertSame([], $offenders, "English chrome survived a French render:\n" . implode("\n", $offenders));
    }

    public function test_the_french_document_carries_the_french_keys(): void
    {
        app()->setLocale('fr');
        $docs = $this->assembleEverything();

        $this->assertSame('Non attribué', $docs['assembleMar']['ward']);
        $this->assertSame('Non renseigné', $docs['assembleMar']['weight_kg']);
        $this->assertSame('Infirmier(ère) désigné(e)', $docs['assembleMar']['nurse_in_charge']);
        $this->assertSame('Médecin traitant', $docs['assembleIcuFlowsheet']['intensivist']);
        $this->assertSame('N/D', $docs['assembleIcuFlowsheet']['ventilator_settings']['pip']);
        $this->assertSame('Documentation en attente', $docs['assembleDailyProgressNote']['assessment']);
        $this->assertSame('Aucun enregistrement', $docs['assembleGlucoseLog']['monitoring_period']);
        $this->assertSame('Équipe sortante', $docs['assembleHandoverNote']['handing_over']);
        $this->assertSame('Air ambiant', $docs['assembleNicuChart']['respiratory_support']['mode']);
        $this->assertSame('Normes OMS de croissance de l’enfant (2006)', $docs['assembleGrowthChart']['who_standard']);
        $this->assertSame('Aucune connue', $docs['assembleSurgicalSafetyChecklist']['sign_in']['allergies_known']);
        $this->assertSame('Non documenté', $docs['assembleNursingAdmissionAssessment']['past_medical_history']);
        $this->assertSame('Selon le protocole du laboratoire', $docs['assembleInvestigationRequest']['expected_turnaround']);
        $this->assertSame('Clinicien demandeur', $docs['assembleInvestigationRequest']['sample_taken_by']);
        $this->assertSame('Échelle de Braden', $docs['assemblePressureUlcerAssessment']['assessment_tool']);
        $this->assertSame('Perception sensorielle', $docs['assemblePressureUlcerAssessment']['braden_items'][0]['subscale']);
        $this->assertArrayHasKey('Talons', $docs['assemblePressureUlcerAssessment']['skin_inspection']);
        $this->assertSame('Infirmier(ère) référent(e) plaies', $docs['assembleWoundCareChart']['wound_care_nurse']);

        // Dates follow the locale too: "05 mars 2026", not "05 Mar 2026".
        $expected = Carbon::today()->locale('fr')->isoFormat('DD MMM YYYY');
        $this->assertSame($expected, $docs['assembleFallRiskAssessment']['assessment_date']);
        $this->assertSame($expected, $docs['assembleNursingAdmissionAssessment']['admission_date']);
    }

    public function test_the_english_document_is_what_it_was_before_the_strings_moved(): void
    {
        app()->setLocale('en');
        $docs = $this->assembleEverything();

        // The literals that used to be hardcoded, verbatim.
        $this->assertSame('Not assigned', $docs['assembleMar']['ward']);
        $this->assertSame('Not assigned', $docs['assembleMar']['bed_number']);
        $this->assertSame('Not recorded', $docs['assembleMar']['weight_kg']);
        $this->assertSame('Assigned Nurse', $docs['assembleMar']['nurse_in_charge']);
        $this->assertSame(['06:00', '10:00', '14:00', '18:00', '22:00'], $docs['assembleMar']['scheduled_times']);
        $this->assertSame('ICU Bed', $docs['assembleIcuFlowsheet']['icu_bed']);
        $this->assertSame('Pending', $docs['assembleIcuFlowsheet']['apache_ii_score']);
        $this->assertSame('Not set', $docs['assembleIcuFlowsheet']['ventilator_settings']['mode']);
        $this->assertSame('N/A', $docs['assembleIcuFlowsheet']['ventilator_settings']['tidal_volume']);
        $this->assertSame('Attending Physician', $docs['assembleIcuFlowsheet']['intensivist']);
        $this->assertSame('Amina Ngo', $docs['assembleNursingChart']['patient_name']);
        $this->assertSame('N/A', $docs['assembleDailyProgressNote']['day_of_admission']);
        $this->assertSame('N/A/N/A', $docs['assembleDailyProgressNote']['vital_signs']['bp']);
        $this->assertSame('Pending documentation', $docs['assembleDailyProgressNote']['subjective']);
        $this->assertSame('Resident', $docs['assembleDailyProgressNote']['resident_physician']);
        $this->assertSame('No records', $docs['assembleGlucoseLog']['monitoring_period']);
        $this->assertSame('Fasting: 4.4–7.2 mmol/L | Post-prandial: < 10.0 mmol/L', $docs['assembleGlucoseLog']['target_range']);
        // No ICD-10 row in the test database, so the fallback title is printed.
        $this->assertSame('Type 2 Diabetes Mellitus', $docs['assembleGlucoseLog']['diabetes_type']);
        $this->assertSame('Per prescription', $docs['assembleGlucoseLog']['current_therapy']);
        $this->assertSame('Nursing Team', $docs['assembleGlucoseLog']['monitored_by']);
        $this->assertSame('Outgoing shift', $docs['assembleHandoverNote']['handing_over']);
        $this->assertSame('Incoming shift', $docs['assembleHandoverNote']['receiving']);
        $this->assertSame('None', $docs['assembleHandoverNote']['critical_alerts']);
        $this->assertSame('Not recorded', $docs['assemblePartograph']['gestational_age']);
        $this->assertSame('Assigned Midwife', $docs['assemblePartograph']['midwife']);
        $this->assertSame('Attending Obstetrician', $docs['assemblePartograph']['obstetrician']);
        $this->assertSame('NICU Incubator', $docs['assembleNicuChart']['nicu_bed']);
        $this->assertSame('Room air', $docs['assembleNicuChart']['respiratory_support']['mode']);
        $this->assertSame('Per order', $docs['assembleNicuChart']['feeds']['route']);
        $this->assertSame('Monitoring', $docs['assembleNicuChart']['feeds']['tolerance']);
        $this->assertSame(['Per prescription'], $docs['assembleNicuChart']['medications']);
        $this->assertSame('Neonatologist', $docs['assembleNicuChart']['attending_neonatologist']);
        $this->assertSame('WHO Child Growth Standards 2006', $docs['assembleGrowthChart']['who_standard']);
        $this->assertSame('No records', $docs['assembleGrowthChart']['last_updated']);
        $this->assertSame('Scheduled procedure', $docs['assembleSurgicalSafetyChecklist']['procedure']);
        $this->assertSame('Attending Surgeon', $docs['assembleSurgicalSafetyChecklist']['surgeon']);
        $this->assertSame('Anaesthetist', $docs['assembleSurgicalSafetyChecklist']['anaesthetist']);
        $this->assertSame('Scrub Nurse', $docs['assembleSurgicalSafetyChecklist']['scrub_nurse']);
        $this->assertSame('None known', $docs['assembleSurgicalSafetyChecklist']['sign_in']['allergies_known']);
        $this->assertSame('Unknown', $docs['assembleSurgicalSafetyChecklist']['sign_in']['blood_loss_risk']);
        $this->assertSame('Amina Ngo — CM-HID-WARD-0001', $docs['assembleSurgicalSafetyChecklist']['time_out']['patient_identity']);
        $this->assertSame('Emergency', $docs['assembleNursingAdmissionAssessment']['admission_mode']);
        $this->assertSame('Ward', $docs['assembleNursingAdmissionAssessment']['ward']);
        $this->assertSame('Not documented', $docs['assembleNursingAdmissionAssessment']['chief_complaint']);
        $this->assertSame('Not recorded', $docs['assembleNursingAdmissionAssessment']['vital_signs_on_admission']['bmi']);
        $this->assertSame('Pending assessment', $docs['assembleNursingAdmissionAssessment']['fall_risk']['risk_level']);
        $this->assertSame('Intact', $docs['assembleNursingAdmissionAssessment']['pressure_ulcer_risk']['skin_condition']);
        $this->assertSame('Normal', $docs['assembleNursingAdmissionAssessment']['nutritional_screen']['diet_on_admission']);
        $this->assertSame('Admitting Nurse', $docs['assembleNursingAdmissionAssessment']['admitting_nurse']);
        $this->assertSame('ROUTINE', $docs['assembleInvestigationRequest']['urgency']);
        $this->assertSame('Ward', $docs['assembleInvestigationRequest']['requesting_ward']);
        $this->assertSame('As per clinical assessment', $docs['assembleInvestigationRequest']['clinical_indication']);
        $this->assertSame('Requesting Clinician', $docs['assembleInvestigationRequest']['sample_taken_by']);
        $this->assertSame('As per laboratory protocol', $docs['assembleInvestigationRequest']['expected_turnaround']);
        $this->assertSame('Morse Fall Scale', $docs['assembleFallRiskAssessment']['assessment_tool']);
        $this->assertSame('History of falling (immediate or within 3 months)', $docs['assembleFallRiskAssessment']['morse_items'][0]['item']);
        $this->assertSame(['Fall risk assessment pending — complete on admission'], $docs['assembleFallRiskAssessment']['interventions']);
        $this->assertSame('Nursing Staff', $docs['assembleFallRiskAssessment']['assessed_by']);
        $this->assertSame('Braden Scale', $docs['assemblePressureUlcerAssessment']['assessment_tool']);
        $this->assertSame('Friction & Shear', $docs['assemblePressureUlcerAssessment']['braden_items'][5]['subscale']);
        $this->assertSame(['Sacrum' => 'Pending', 'Heels' => 'Pending', 'Elbows' => 'Pending'], $docs['assemblePressureUlcerAssessment']['skin_inspection']);
        $this->assertSame('None documented', $docs['assemblePressureUlcerAssessment']['existing_wounds']);
        $this->assertSame(['Assessment pending — complete at admission'], $docs['assemblePressureUlcerAssessment']['prevention_plan']);
        $this->assertSame('Wound Care Nurse', $docs['assembleWoundCareChart']['wound_care_nurse']);

        // Dates: the old format('d M Y') output, byte for byte.
        $this->assertSame(Carbon::today()->format('d M Y'), $docs['assembleFallRiskAssessment']['assessment_date']);
        $this->assertSame(Carbon::tomorrow()->format('d M Y'), $docs['assembleWoundCareChart']['next_assessment_due']);
        $this->assertSame(Carbon::today()->format('d M Y'), $docs['assembleNursingAdmissionAssessment']['admission_date']);
    }
}
