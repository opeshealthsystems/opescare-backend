<?php

namespace Database\Seeders\Passport;

use App\Models\Patient;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration imaging for page 12 of the Health Passport.
 *
 * The demo estate records prescriptions, labs, vitals, a referral and a care
 * plan, but never an imaging study, so the Radiology page is blank on the
 * deployed demo — which tells a reviewer nothing about whether the page works.
 *
 * ── THE IMAGING FITS THE STORY THE REST OF THE DEMO ALREADY TELLS ─────────
 *
 * DEMO_DATA_BRIEF.md: a woman in her thirties in Yaoundé with Type 2 diabetes
 * (three years) and hypertension (eighteen months), reviewed quarterly at
 * Hôpital Central de Yaoundé, blood pressure persistently above target, and a
 * cardiology referral to CHU de Yaoundé five weeks ago. The three studies below
 * are the imaging that story would actually generate, in the order it would
 * generate them:
 *
 *   -18 months  chest radiograph at the hypertension diagnosis — the baseline
 *               film a Cameroonian clinic takes to look at heart size.
 *               Later AMENDED, because a radiology addendum is ordinary and
 *               the passport is supposed to show that a report is corrected,
 *               never overwritten.
 *   -9 months   renal ultrasound — the kidneys of a diabetic hypertensive
 *               patient, checked and normal (her creatinine is 84 µmol/L in
 *               the seeded labs, so a normal scan is the consistent finding).
 *   -4 weeks    transthoracic echocardiogram at CHU de Yaoundé, days after the
 *               cardiology referral was accepted — mild concentric left
 *               ventricular hypertrophy, which is what eighteen months of
 *               above-target blood pressure looks like on a scan.
 *
 * Nothing unrelated is added. An MRI of a knee would fill the page and mean
 * nothing; imaging that answers a question the record actually asks is what
 * makes the demo readable.
 *
 * ── BOUNDARIES ───────────────────────────────────────────────────────────
 *
 *  - DEMO PATIENTS ONLY, and only those whose own record carries the
 *    indication: the seeder reads each demo patient's diagnoses and writes the
 *    chest film and the echocardiogram only where hypertension is recorded, the
 *    renal ultrasound only where diabetes is recorded too. The demo child, the
 *    emergency fixture and every real patient are therefore never touched.
 *    `radiology_reports` has no `is_demo` column in this schema, so the patient
 *    the row hangs off is the only isolation there is — hence the double check.
 *  - Idempotent: each row is keyed on a stable UUID (the reserved 0d12 block
 *    for the canonical demo patient, a v5 UUID derived from it for any other),
 *    so a second run rewrites the same three rows with their dates refreshed
 *    relative to the new "now".
 *  - Real institutions only, resolved through DemoFacilityResolver, and the
 *    demo care team through DemoCareTeam. No invented facility, no invented
 *    clinician.
 *  - Discovered and run by DemoHealthPassportSeeder; registered nowhere.
 */
class RadiologyDemoSeeder extends Seeder
{
    /** Reserved id block — 0d12 = passport page 12. */
    private const STUDY_CHEST  = '00000000-0000-0000-0d12-000000000001';
    private const STUDY_RENAL  = '00000000-0000-0000-0d12-000000000002';
    private const STUDY_CARDIO = '00000000-0000-0000-0d12-000000000003';

    public function run(): void
    {
        $doctor = DemoCareTeam::doctor();

        if (! $doctor) {
            $this->command?->warn('RadiologyDemoSeeder: no demo clinician (radiology_reports.ordered_by and reported_by are NOT NULL) — nothing seeded.');

            return;
        }

        // No radiologist exists in this estate's demo team. The general
        // practitioner reports the plain film and the ultrasound as she would
        // in a district service, and the cardiologist reports the echo — which
        // is who reports an echocardiogram anywhere.
        $cardiologist = DemoCareTeam::specialist() ?? $doctor;

        $hospital  = DemoFacilityResolver::primaryHospital();
        $cardiology = DemoFacilityResolver::resolve('CHU de Yaoundé');

        if (! $hospital) {
            $this->command?->warn('RadiologyDemoSeeder: "' . DemoFacilityResolver::PRIMARY_HOSPITAL . '" is not in the care_facilities registry — nothing seeded.');

            return;
        }

        $patients = $this->hypertensivePatients();

        if ($patients->isEmpty()) {
            $this->command?->info('RadiologyDemoSeeder: no demo patient carries a hypertension diagnosis — nothing seeded.');

            return;
        }

        $now     = Carbon::now();
        $written = 0;

        foreach ($patients as $patient) {
            $written += $this->chestFilm($patient, $now, $hospital, $doctor);

            if ($this->hasDiabetes($patient->id)) {
                $written += $this->renalUltrasound($patient, $now, $hospital, $doctor);
            }

            if ($cardiology) {
                $written += $this->echocardiogram($patient, $now, $cardiology, $cardiologist);
            }
        }

        if (! $cardiology) {
            $this->command?->warn('RadiologyDemoSeeder: "CHU de Yaoundé" is not in the care_facilities registry — the echocardiogram was skipped rather than moved to another hospital.');
        }

        $this->command?->info("RadiologyDemoSeeder: {$written} imaging report(s) for {$patients->count()} demo patient(s).");
    }

    // ── Who gets imaging ─────────────────────────────────────────────────

    /**
     * The demo patients whose record actually asks for these studies.
     *
     * Matched on the ICD-10 code the clinical-core seeder writes (I10), with a
     * text fallback for a record that carries the condition under a name rather
     * than a code.
     *
     * @return \Illuminate\Support\Collection<int,Patient>
     */
    private function hypertensivePatients(): \Illuminate\Support\Collection
    {
        $ids = DB::table('diagnoses')
            ->where(fn ($q) => $q->where('code', 'I10')->orWhere('display_name', 'ilike', '%hypertens%'))
            ->distinct()
            ->pluck('patient_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Patient::query()
            ->withoutGlobalScopes()
            ->where('is_demo', true)
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->get();
    }

    private function hasDiabetes(string $patientId): bool
    {
        return DB::table('diagnoses')
            ->where('patient_id', $patientId)
            ->where(fn ($q) => $q->where('code', 'like', 'E11%')->orWhere('display_name', 'ilike', '%diabet%'))
            ->exists();
    }

    // ── The three studies ────────────────────────────────────────────────

    /**
     * The baseline chest film taken when hypertension was diagnosed, and the
     * addendum issued after the departmental review meeting.
     *
     * It is the page's one amended report on purpose: a clinical document is
     * corrected and reissued, never quietly rewritten, and the passport has to
     * be able to say so.
     */
    private function chestFilm(Patient $patient, Carbon $now, string $facility, string $reporter): int
    {
        $studyAt = $now->copy()->subMonthsNoOverflow(18)->setTime(11, 40);

        return $this->write($patient, self::STUDY_CHEST, [
            'facility_id'         => $facility,
            'ordered_by'          => $reporter,
            'reported_by'         => $reporter,
            'modality'            => 'xray',
            'body_part'           => 'Chest',
            'study_date'          => $studyAt,
            'clinical_indication' => 'Newly diagnosed hypertension. Baseline assessment of heart size and lung fields.',
            'findings'            => 'PA erect film with adequate inspiration and penetration. Lung fields clear, with no focal consolidation, pleural effusion or pneumothorax. Cardiothoracic ratio 0.47. Aortic contour and mediastinal outline normal. Both hemidiaphragms smooth and both costophrenic angles clear. Bony thorax and soft tissues unremarkable.',
            'impression'          => 'Normal chest radiograph. No cardiomegaly and no pulmonary venous congestion.',
            'recommendation'      => null,
            'status'              => 'amended',
            'finalized_at'        => $studyAt->copy()->addHours(3),
            'amended_at'          => $studyAt->copy()->addDays(2)->setTime(8, 30),
            'amendment_reason'    => 'Addendum after the departmental review meeting: cardiothoracic ratio measured and stated in the findings. Impression unchanged.',
        ]);
    }

    /** The kidneys of a diabetic hypertensive patient, looked at and normal. */
    private function renalUltrasound(Patient $patient, Carbon $now, string $facility, string $reporter): int
    {
        $studyAt = $now->copy()->subMonthsNoOverflow(9)->setTime(9, 15);

        return $this->write($patient, self::STUDY_RENAL, [
            'facility_id'         => $facility,
            'ordered_by'          => $reporter,
            'reported_by'         => $reporter,
            'modality'            => 'ultrasound',
            'body_part'           => 'Kidneys, ureters and bladder',
            'study_date'          => $studyAt,
            'clinical_indication' => 'Type 2 diabetes with hypertension. Assessment for diabetic kidney disease.',
            'findings'            => 'Right kidney 10.6 cm, left kidney 11.0 cm, both normally sited with preserved corticomedullary differentiation and normal cortical echogenicity. No hydronephrosis, calculus or focal lesion. Ureters not dilated. Bladder wall smooth with no significant post-void residual.',
            'impression'          => 'Normal renal ultrasound. No sonographic features of chronic kidney disease or obstruction.',
            'recommendation'      => 'Continue the annual urine albumin-to-creatinine ratio; repeat imaging only if renal function changes.',
            'status'              => 'final',
            'finalized_at'        => $studyAt->copy()->addHours(2),
            'amended_at'          => null,
            'amendment_reason'    => null,
        ]);
    }

    /**
     * The echocardiogram the cardiology referral leads to — done days after the
     * referral was accepted, and reported by the cardiologist.
     */
    private function echocardiogram(Patient $patient, Carbon $now, string $facility, string $reporter): int
    {
        $studyAt = $now->copy()->subWeeks(4)->setTime(14, 30);

        return $this->write($patient, self::STUDY_CARDIO, [
            'facility_id'         => $facility,
            'ordered_by'          => $reporter,
            'reported_by'         => $reporter,
            'modality'            => 'echo',
            'body_part'           => 'Heart (transthoracic)',
            'study_date'          => $studyAt,
            'clinical_indication' => 'Hypertension above target on treatment. Assessment of left ventricular structure and function.',
            'findings'            => 'Left ventricle not dilated, with mild concentric hypertrophy — interventricular septum 12 mm, posterior wall 11 mm. Ejection fraction 58% by Simpson biplane, with no regional wall motion abnormality. Grade I diastolic dysfunction (E/A 0.78, e\' 7 cm/s). Left atrium at the upper limit of normal. Valves structurally normal with trivial mitral regurgitation. Aortic root normal. No pericardial effusion.',
            'impression'          => 'Mild concentric left ventricular hypertrophy with grade I diastolic dysfunction, in keeping with hypertensive heart disease. Systolic function preserved.',
            'recommendation'      => 'Optimise blood-pressure control; repeat echocardiography in twelve months, or sooner if symptoms change.',
            'status'              => 'final',
            'finalized_at'        => $studyAt->copy()->addMinutes(90),
            'amended_at'          => null,
            'amendment_reason'    => null,
        ]);
    }

    // ── Writing ──────────────────────────────────────────────────────────

    /**
     * One report, written straight to the table.
     *
     * `updateOrInsert` rather than the model, so a re-run refreshes the row's
     * relative dates and a soft-deleted row is updated in place rather than
     * colliding with its own primary key.
     *
     * @param  array<string,mixed>  $attributes
     */
    private function write(Patient $patient, string $reservedId, array $attributes): int
    {
        $id = DemoStoryIds::uid($patient->id, $reservedId);

        DB::table('radiology_reports')->updateOrInsert(
            ['id' => $id],
            $attributes + [
                'patient_id'       => $patient->id,
                'imaging_order_id' => null,
                'distributed_to'   => null,
                'distributed_at'   => null,
                'deleted_at'       => null,
                'created_at'       => $attributes['study_date'],
                'updated_at'       => Carbon::now(),
            ]
        );

        return 1;
    }
}
