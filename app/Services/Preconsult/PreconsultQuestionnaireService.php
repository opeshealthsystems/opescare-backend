<?php

namespace App\Services\Preconsult;

use App\Enums\PreconsultStatus;
use App\Models\Appointment;
use App\Models\PreconsultAnswer;
use App\Models\PreconsultSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * THE ONE writer of pre-consult questionnaires.
 *
 * Both controllers (the patient's and the clinician's) go through here, so
 * there is exactly one place that decides what a submission is, what an answer
 * may contain, and what `facility_id` a submission carries.
 *
 * THREE RULES THIS CLASS EXISTS TO HOLD:
 *
 *  1. `patient_id` and `facility_id` are COPIED OFF THE APPOINTMENT ROW and
 *     from nowhere else. Not a header, not the body, not "the one facility we
 *     found". The caller hands over an authenticated patient id and an
 *     appointment id; if the appointment is not that patient's, this class
 *     answers null and the controller turns that into a 404 — the same answer
 *     as "no such appointment", so nothing here confirms another patient's
 *     booking exists.
 *
 *  2. EVERY answer is patient-reported. Answers are written only to
 *     `preconsult_answers`, whose provenance column is
 *     PreconsultAnswer::PROVENANCE. This class contains no reference to
 *     `triage_vital_signs`, `vital_signs`, `diagnoses` or `allergy_records`,
 *     and adding one would be a defect: a patient saying "I am allergic to
 *     penicillin" is a reason for a clinician to ask, never an entry on the
 *     allergy list.
 *
 *  3. NOTHING HERE TOUCHES THE APPOINTMENT. Not its status, not its time, not
 *     a flag, not a note. Read the appointment, write the questionnaire. An
 *     unanswered questionnaire is the normal case and has no consequences —
 *     held by tests/Feature/Architecture/CareIsNeverGatedOnPreconsultTest.php.
 *
 * NO QUESTION IS REQUIRED. `submit()` accepts a submission with zero answers.
 * Refusing to accept a partly-filled form would push the patient to invent
 * answers, and an invented history is worse than an absent one.
 */
class PreconsultQuestionnaireService
{
    /** Appointment statuses for which collecting an intake still makes sense. */
    private const COLLECTABLE_STATUSES = ['scheduled', 'confirmed', 'checked_in', 'in_progress'];

    // ══════════════════════════════════════════════════════════════════
    // The definition
    // ══════════════════════════════════════════════════════════════════

    /** @return array<string,mixed> */
    public function definition(): array
    {
        return (array) config('preconsult');
    }

    public function definitionKey(): string
    {
        return (string) ($this->definition()['key'] ?? 'general_intake');
    }

    public function definitionVersion(): int
    {
        return (int) ($this->definition()['version'] ?? 1);
    }

    /** @return array<int,array<string,mixed>> */
    public function questions(): array
    {
        return (array) ($this->definition()['questions'] ?? []);
    }

    /** @return array<string,mixed>|null */
    public function question(string $key): ?array
    {
        foreach ($this->questions() as $question) {
            if (($question['key'] ?? null) === $key) {
                return $question;
            }
        }

        return null;
    }

    /**
     * The questionnaire as the client renders it, in the ACTIVE LOCALE.
     *
     * Every string in the payload comes from `__()`. There is not one literal
     * question or option label in this file or in config/preconsult.php.
     *
     * @return array<string,mixed>
     */
    public function renderedDefinition(): array
    {
        $questions = [];

        foreach ($this->questions() as $question) {
            $rendered = [
                'key'          => $question['key'],
                'type'         => $question['type'],
                'label'        => __($question['label_key']),
                'help'         => isset($question['help_key']) ? __($question['help_key']) : null,
                'clinical_use' => $question['clinical_use'] ?? null,
                // Said out loud on every single question, in the patient's
                // language: none of this is required of them.
                'optional'      => true,
                'optional_hint' => __('preconsult.optional_hint'),
                'shown_when'    => $question['shown_when'] ?? null,
            ];

            if (($question['type'] ?? null) === 'single_choice') {
                $rendered['options'] = array_map(
                    fn (array $option) => [
                        'value' => $option['value'],
                        'label' => __($option['label_key']),
                    ],
                    (array) ($question['options'] ?? [])
                );
            }

            if (($question['type'] ?? null) === 'text') {
                $rendered['max_length'] = $this->maxTextLength();
            }

            $questions[] = $rendered;
        }

        return [
            'key'           => $this->definitionKey(),
            'version'       => $this->definitionVersion(),
            'title'         => __('preconsult.title'),
            'intro'         => __('preconsult.intro'),
            'never_gated'   => __('preconsult.never_gated'),
            'provenance'    => [
                'value'       => PreconsultAnswer::PROVENANCE,
                'label'       => __('preconsult.provenance.label'),
                'description' => __('preconsult.provenance.description'),
            ],
            'questions' => $questions,
        ];
    }

    public function maxTextLength(): int
    {
        return (int) ($this->definition()['max_text_length'] ?? 1000);
    }

    // ══════════════════════════════════════════════════════════════════
    // Reaching an appointment
    // ══════════════════════════════════════════════════════════════════

    /**
     * The patient's OWN appointment, or null.
     *
     * Null covers every miss the caller must not be able to tell apart: no
     * such appointment, someone else's appointment, an appointment at a
     * facility this deployment isolates away. The controller answers 404 for
     * all of them — cross-patient access is 404, never 403.
     */
    public function appointmentForPatient(string $patientId, string $appointmentId): ?Appointment
    {
        if ($patientId === '' || $appointmentId === '') {
            return null;
        }

        return Appointment::query()
            ->whereKey($appointmentId)
            ->where('patient_id', $patientId)
            ->first();
    }

    /**
     * Is the questionnaire still collecting for this appointment?
     *
     * "closed" stops the FORM, never the appointment. A closed window means
     * the answers would arrive after they could be read, so the API says so
     * plainly rather than storing something nobody will see.
     *
     * @return array{open:bool,reason:?string}
     */
    public function window(Appointment $appointment, ?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();
        $definition = $this->definition();

        $status = (string) $appointment->status;

        if (! in_array($status, self::COLLECTABLE_STATUSES, true)) {
            // Cancelled or completed. Nothing to prepare for.
            return ['open' => false, 'reason' => 'appointment_not_upcoming'];
        }

        $scheduledAt = $appointment->scheduled_at;

        if ($scheduledAt === null) {
            return ['open' => true, 'reason' => null];
        }

        $opensAt  = $scheduledAt->copy()->subDays((int) ($definition['opens_days_before'] ?? 30));
        $closesAt = $scheduledAt->copy()->addHours((int) ($definition['closes_hours_after'] ?? 24));

        if ($now->lt($opensAt)) {
            return ['open' => false, 'reason' => 'not_open_yet'];
        }

        if ($now->gt($closesAt)) {
            return ['open' => false, 'reason' => 'closed'];
        }

        return ['open' => true, 'reason' => null];
    }

    // ══════════════════════════════════════════════════════════════════
    // Writing
    // ══════════════════════════════════════════════════════════════════

    /** The existing submission for this appointment, if any. */
    public function existing(Appointment $appointment): ?PreconsultSubmission
    {
        return PreconsultSubmission::query()
            ->where('appointment_id', $appointment->id)
            ->with('answers')
            ->first();
    }

    /**
     * Start the questionnaire, or return the one already started.
     *
     * `patient_id` and `facility_id` come off `$appointment` — the row the
     * caller has already been proven to own — and are set with forceFill
     * precisely because they are not fillable.
     */
    public function startOrResume(Appointment $appointment, ?string $completedByUserId): PreconsultSubmission
    {
        $existing = $this->existing($appointment);

        if ($existing !== null) {
            return $existing;
        }

        $submission = new PreconsultSubmission([
            'appointment_id'       => $appointment->id,
            'definition_key'       => $this->definitionKey(),
            'definition_version'   => $this->definitionVersion(),
            'status'               => PreconsultStatus::Draft->value,
            'completed_by_user_id' => $completedByUserId,
            'started_at'           => Carbon::now(),
        ]);

        // Server-derived, never request-derived.
        $submission->forceFill([
            'patient_id'  => $appointment->patient_id,
            'facility_id' => $appointment->facility_id,
        ]);

        $submission->save();

        return $submission->fresh(['answers']) ?? $submission;
    }

    /**
     * Save answers onto a draft.
     *
     * Only keys that exist in the definition are stored; anything else is
     * dropped silently rather than persisted as an uninterpretable row. A key
     * present with an empty value CLEARS that answer — a patient must be able
     * to take back something they typed, and the cleared state is absence, not
     * an empty string standing in for "no".
     *
     * @param  array<string,mixed>  $answers  question_key => value
     */
    public function saveAnswers(PreconsultSubmission $submission, array $answers): PreconsultSubmission
    {
        DB::transaction(function () use ($submission, $answers) {
            foreach ($answers as $key => $raw) {
                $question = $this->question((string) $key);

                if ($question === null) {
                    continue;
                }

                $normalised = $this->normalise($question, $raw);

                if ($normalised === null) {
                    PreconsultAnswer::query()
                        ->where('submission_id', $submission->id)
                        ->where('question_key', $key)
                        ->delete();

                    continue;
                }

                $answer = PreconsultAnswer::query()->firstOrNew([
                    'submission_id' => $submission->id,
                    'question_key'  => (string) $key,
                ]);

                $answer->fill([
                    'value_text'   => $normalised['value_text'],
                    'value_choice' => $normalised['value_choice'],
                ]);

                // The one place provenance is ever written, and it is a
                // constant — no caller chooses it.
                $answer->forceFill([
                    'submission_id' => $submission->id,
                    'question_key'  => (string) $key,
                    'provenance'    => PreconsultAnswer::PROVENANCE,
                ]);

                $answer->save();
            }
        });

        return $submission->fresh(['answers']) ?? $submission;
    }

    /**
     * Hand the questionnaire to the facility.
     *
     * Accepts an empty submission. Idempotent: submitting an already-submitted
     * questionnaire returns it unchanged rather than erroring, because a
     * retried request from a phone on a bad connection must not look like a
     * failure to the patient.
     */
    public function submit(PreconsultSubmission $submission, ?string $completedByUserId): PreconsultSubmission
    {
        if ($submission->status === PreconsultStatus::Submitted) {
            return $submission;
        }

        $submission->status       = PreconsultStatus::Submitted;
        $submission->submitted_at = Carbon::now();

        if ($completedByUserId !== null) {
            $submission->completed_by_user_id = $completedByUserId;
        }

        $submission->save();

        return $submission->fresh(['answers']) ?? $submission;
    }

    // ══════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════

    /**
     * A submission as an API payload, in the ACTIVE LOCALE.
     *
     * Every answer carries its own `provenance` block. That is not decoration:
     * it is the only thing separating "the patient told us this" from "a
     * clinician recorded this", and a clinician-facing list that renders both
     * identically is how self-reported text becomes a finding.
     *
     * Unanswered questions are ABSENT from `answers`. They are not returned as
     * empty strings, and never as "none" — see config/preconsult.php.
     *
     * @return array<string,mixed>
     */
    public function present(PreconsultSubmission $submission): array
    {
        $byKey = $submission->answers->keyBy('question_key');
        $items = [];

        foreach ($this->questions() as $question) {
            /** @var PreconsultAnswer|null $answer */
            $answer = $byKey->get($question['key']);

            if ($answer === null || $answer->value() === null) {
                continue;
            }

            $items[] = [
                'question_key'   => $question['key'],
                'question_label' => __($question['label_key']),
                'type'           => $question['type'],
                'clinical_use'   => $question['clinical_use'] ?? null,
                'value'          => $answer->value(),
                // For a choice, the token AND the label it was chosen under.
                'value_label'    => $this->choiceLabel($question, $answer->value_choice),
                'provenance'     => [
                    'value'       => $answer->provenance,
                    'label'       => __('preconsult.provenance.label'),
                    'description' => __('preconsult.provenance.description'),
                ],
                'answered_at' => optional($answer->updated_at)->toIso8601String(),
            ];
        }

        return [
            'id'                 => $submission->id,
            'appointment_id'     => $submission->appointment_id,
            'status'             => $submission->status->value,
            'status_label'       => __($submission->status->labelKey()),
            'editable'           => $submission->status->isEditable(),
            'definition_key'     => $submission->definition_key,
            'definition_version' => $submission->definition_version,
            'started_at'         => optional($submission->started_at)->toIso8601String(),
            'submitted_at'       => optional($submission->submitted_at)->toIso8601String(),
            'answered_count'     => count($items),
            'answers'            => $items,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════

    /**
     * Coerce one raw value into the two storage columns, or null for "clear".
     *
     * An out-of-range choice is a CLEAR, not a stored token: storing a value
     * the definition does not contain would produce an answer nothing can
     * render, and inventing a nearest match would be fabricating what the
     * patient said.
     *
     * @param  array<string,mixed>  $question
     * @return array{value_text:?string,value_choice:?string}|null
     */
    private function normalise(array $question, mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        if (is_bool($raw) || is_array($raw)) {
            return null;
        }

        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        if (($question['type'] ?? null) === 'single_choice') {
            $allowed = array_column((array) ($question['options'] ?? []), 'value');

            if (! in_array($value, $allowed, true)) {
                return null;
            }

            return ['value_text' => null, 'value_choice' => $value];
        }

        return [
            'value_text'   => mb_substr($value, 0, $this->maxTextLength()),
            'value_choice' => null,
        ];
    }

    /**
     * The translated label a choice was selected under. Null for free text and
     * for a token no longer in the definition — null is "we cannot say", which
     * is honest; the raw token is still returned as `value`.
     *
     * @param  array<string,mixed>  $question
     */
    private function choiceLabel(array $question, ?string $choice): ?string
    {
        if ($choice === null) {
            return null;
        }

        foreach ((array) ($question['options'] ?? []) as $option) {
            if (($option['value'] ?? null) === $choice) {
                return __($option['label_key']);
            }
        }

        return null;
    }
}
