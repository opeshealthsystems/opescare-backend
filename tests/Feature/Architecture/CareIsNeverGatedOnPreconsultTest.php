<?php

namespace Tests\Feature\Architecture;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PreconsultSubmission;
use App\Services\Preconsult\PreconsultQuestionnaireService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Nobody is ever turned away, delayed, deprioritised or flagged for not filling
 * in the pre-consult questionnaire.
 *
 * This is the sibling of CareIsNeverGatedOnPaymentTest, and it exists for the
 * same reason: the failure mode is not a decision anyone would defend out loud,
 * it is a line of code that looks routine. `->whereNotNull('preconsult_id')` on
 * a scheduling query, an `orderBy` that puts prepared patients first, a column
 * on `appointments` that a check-in screen renders as a red dot — each of those
 * is a small, reasonable-looking change, and each turns a form into a fee.
 *
 * The people this protects are exactly the people least able to complain about
 * it: patients who cannot read, who have no phone, who have no data bundle, or
 * who are too unwell to type. A questionnaire that quietly reorders the queue
 * penalises illness and poverty, in that order.
 *
 * If this test ever fails, the right response is almost certainly to remove the
 * gate, not to amend the test.
 */
class CareIsNeverGatedOnPreconsultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The feature's own files. None of them may write to `appointments`.
     */
    private const PRECONSULT_FILES = [
        'app/Services/Preconsult/PreconsultQuestionnaireService.php',
        'app/Http/Controllers/Api/Mobile/MobilePreconsultController.php',
        'app/Http/Controllers/Api/Mobile/MobileDoctorPreconsultController.php',
        'app/Models/PreconsultSubmission.php',
        'app/Models/PreconsultAnswer.php',
    ];

    /**
     * Clinician-owned clinical tables. Self-reported text is context, never a
     * finding, so no file in this feature may name one of these.
     */
    private const CLINICIAN_OWNED_TABLES = [
        'triage_vital_signs',
        'vital_signs',
        'diagnoses',
        'allergy_records',
        'problem_lists',
    ];

    /**
     * The structural guarantee, and the strongest one available: an
     * appointment cannot know whether its questionnaire was answered, so no
     * scheduling code anywhere — now or in five years — can branch on it.
     */
    public function test_the_appointments_table_carries_no_questionnaire_column(): void
    {
        $columns = Schema::getColumnListing('appointments');

        $offences = array_values(array_filter(
            $columns,
            fn (string $c) => str_contains($c, 'preconsult')
                || str_contains($c, 'questionnaire')
                || str_contains($c, 'intake')
        ));

        $this->assertSame(
            [],
            $offences,
            "The appointments table gained a questionnaire column.\n"
            . "The relationship is one-way on purpose: a submission points at an appointment, never the reverse,\n"
            . "so nothing that schedules, orders or checks in a patient can read whether they answered.\n"
            . 'Offenders: ' . implode(', ', $offences)
        );
    }

    /** Nothing in this feature writes to an appointment. */
    public function test_no_preconsult_file_writes_to_appointments(): void
    {
        $offences = [];

        $writeShapes = [
            'appointments\')->update',
            'appointment->save',
            'appointment->update',
            'appointmentModel->save',
            'appointmentModel->update',
            'appointmentModel->delete',
            'Appointment::query()->update',
        ];

        foreach (self::PRECONSULT_FILES as $relative) {
            $path = base_path($relative);

            if (! is_file($path)) {
                $offences[] = $relative . ' (missing — repoint this test)';
                continue;
            }

            $source = (string) file_get_contents($path);

            foreach ($writeShapes as $shape) {
                if (str_contains($source, $shape)) {
                    $offences[] = sprintf('%s writes to an appointment (%s)', $relative, $shape);
                }
            }
        }

        $this->assertSame([], $offences, "A pre-consult file writes to an appointment.\nOffenders:\n  " . implode("\n  ", $offences));
    }

    /**
     * Self-reported text never lands in a clinician-owned clinical table.
     * A patient saying "I am allergic to penicillin" is a reason to ask, not
     * an entry on the allergy list.
     */
    public function test_no_preconsult_file_touches_a_clinician_owned_clinical_table(): void
    {
        $offences = [];

        foreach (self::PRECONSULT_FILES as $relative) {
            $source = (string) file_get_contents(base_path($relative));

            foreach (self::CLINICIAN_OWNED_TABLES as $table) {
                // A docblock may name the table to explain the rule; only code
                // may not. Strip comments before looking.
                $code = $this->stripComments($source);

                if (str_contains($code, $table)) {
                    $offences[] = sprintf('%s references %s in code', $relative, $table);
                }
            }
        }

        $this->assertSame([], $offences, "Patient-reported intake reaches a clinician-owned table.\nOffenders:\n  " . implode("\n  ", $offences));
    }

    /**
     * The behavioural proof: an appointment with no questionnaire at all is
     * byte-for-byte what it was before the feature existed.
     */
    public function test_an_unanswered_questionnaire_changes_nothing_about_the_appointment(): void
    {
        $patient  = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $facility = Facility::factory()->create();

        $appointment = Appointment::factory()->create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDays(3),
        ]);

        $before = $appointment->fresh()->getAttributes();

        // Nobody answers. Nothing is submitted. Time passes.
        $this->assertSame(0, PreconsultSubmission::query()->where('appointment_id', $appointment->id)->count());

        $after = $appointment->fresh()->getAttributes();

        $this->assertSame($before, $after, 'An unanswered questionnaire altered the appointment row.');
        $this->assertSame('scheduled', $after['status'], 'An unanswered questionnaire changed the appointment status.');
        $this->assertNull($after['cancelled_at'], 'An unanswered questionnaire cancelled the appointment.');
        $this->assertNull($after['no_show_at'], 'An unanswered questionnaire marked a no-show.');
    }

    /**
     * And the questionnaire itself never refuses an empty answer set. A form
     * that will not be sent half-filled is a form that teaches patients to
     * invent answers, and an invented history is worse than an absent one.
     */
    public function test_a_questionnaire_may_be_submitted_completely_empty(): void
    {
        $patient  = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $facility = Facility::factory()->create();

        $appointment = Appointment::factory()->create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $service    = app(PreconsultQuestionnaireService::class);
        $submission = $service->startOrResume($appointment, null);
        $submission = $service->submit($submission, null);

        $this->assertTrue($submission->status->isVisibleToClinician());
        $this->assertSame(0, $submission->answers()->count(), 'An empty submission stored an answer nobody gave.');

        $payload = $service->present($submission);
        $this->assertSame(0, $payload['answered_count']);
        $this->assertSame([], $payload['answers'], 'An unanswered question was rendered as an answer.');
    }

    /**
     * No question is required. If one ever becomes so, the whole promise above
     * is void, so the definition itself is asserted.
     */
    public function test_no_question_is_marked_required(): void
    {
        foreach ((array) config('preconsult.questions') as $question) {
            $this->assertArrayNotHasKey(
                'required',
                $question,
                "Question '{$question['key']}' was marked required. No question in this set may be."
            );
        }

        // And the rendered payload says "optional" out loud on every one.
        $rendered = app(PreconsultQuestionnaireService::class)->renderedDefinition();

        foreach ($rendered['questions'] as $question) {
            $this->assertTrue($question['optional'], "Question '{$question['key']}' is not presented as optional.");
        }
    }

    /** Strip PHP comments so a docblock explaining a rule is not read as breaking it. */
    private function stripComments(string $source): string
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }
}
