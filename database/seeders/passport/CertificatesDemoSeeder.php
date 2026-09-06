<?php

namespace Database\Seeders\Passport;

use App\Services\Documents\DocumentNumberService;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * The certificates the demo patient's own history would have produced —
 * page 18 (Medical Certificates & Documents).
 *
 * WHY THIS EXISTS. `official_documents` holds nothing but `RX` rows on every
 * account, so page 18 read as "nothing recorded" everywhere. That is an honest
 * sentence about the record, but it hides the one thing the page has to get
 * right: a certificate that has run out must read as run out, in red, because
 * the person holding it is about to hand it to an employer or a school.
 *
 * EVERY CERTIFICATE HANGS OFF SOMETHING THE RECORD ALREADY HOLDS. A medical
 * certificate is a statement a clinician made about an episode of care, so each
 * one here is anchored to the episode that produced it and is skipped outright
 * when that episode is not in the record:
 *
 *   - Sick leave, issued the day the patient was discharged from the stay the
 *     record carries (hyperglycaemia with dehydration), covering the fortnight
 *     off work that followed. Its validity ended a fortnight after a discharge
 *     that was over a year ago, so it is EXPIRED — deliberately, because a
 *     demo whose every pill is green proves nothing.
 *   - Fitness to work, issued at the most recent clinic visit, valid twelve
 *     months. This is the live one: issued, not withdrawn, in date.
 *   - International certificate of vaccination for yellow fever, issued the day
 *     the dose in the immunisation record was given. No expiry, because under
 *     the IHR a yellow-fever certificate has been valid for the life of the
 *     person vaccinated since 2016 — the passport shows the honest blank rather
 *     than a date nobody set.
 *
 * The dates are read from the anchoring rows and never computed from `now()`.
 * The story seeders write their history relative to the day THEY were run, so a
 * certificate dated from today's clock lands days away from the discharge it is
 * supposed to have been written at, and the two records then contradict each
 * other on the demo server a fortnight later.
 *
 * NO `pdf_path`, EVER. Nothing in this codebase writes a document file — that
 * is the whole point of PatientDocumentService, which renders a sheet at the
 * moment it is asked for and stores nothing. A row claiming a file that does
 * not exist is what produced the dead "Download" buttons this area was repaired
 * for, and a seeder is no more entitled to write that claim than a controller.
 *
 * Deliberate boundaries:
 *   - Demo patients only, resolved by email exactly as DemoPatientStorySeeder
 *     resolves them (DEMO_STORY_EMAILS overrides). A patient that is not
 *     `is_demo` is refused by name and never written to. Every row written is
 *     `is_demo = true`.
 *   - Idempotent: every row id, document number and verification code is
 *     derived from the patient id and a fixed key, so a second run rewrites the
 *     same rows and inserts nothing new. It is run repeatedly on the live demo
 *     server.
 *   - Real institutions only. The issuing facility is the facility on the
 *     anchoring record — the hospital that actually recorded the discharge, the
 *     visit or the dose — falling back to the primary hospital through
 *     DemoFacilityResolver.
 *   - The one invented row is the document template, and only because
 *     `official_documents.template_id` is NOT NULL behind a foreign key: a
 *     document cannot be recorded without one. Its `template_code` carries a
 *     `-DEMO` suffix so it can never be picked up — or collided with — by
 *     DocumentIssuanceService issuing a real certificate later.
 */
class CertificatesDemoSeeder extends Seeder
{
    /** The demo logins the story is told for — the same list the story seeder uses. */
    private const DEFAULT_EMAILS = [
        'demo.patient@opescare.test',
        'demo.reviewer@opescare.test',
    ];

    /** How long the sick-leave certificate signed the patient off for. */
    private const SICK_LEAVE_DAYS = 14;

    /** How long a fitness-to-work certificate is good for. */
    private const FITNESS_MONTHS = 12;

    public function run(): void
    {
        $issuer   = DemoCareTeam::doctor();
        $fallback = DemoFacilityResolver::primaryHospital();
        $seeded   = 0;

        foreach ($this->emails() as $email) {
            $patient = DB::table('patients')->where('email', $email)->first(['id', 'health_id', 'is_demo']);

            if (! $patient) {
                $this->command?->warn("  skip {$email}: no patient record");

                continue;
            }

            if (! $patient->is_demo) {
                $this->command?->warn("  refuse {$email}: not a demo record — a certificate is never issued onto a real person");

                continue;
            }

            $written = $this->seedCertificates($patient, $issuer, $fallback);
            $seeded++;

            $this->command?->info(sprintf(
                '  %s  certificates=%d (of 3 possible; the rest have no episode in the record)',
                $patient->health_id,
                $written
            ));
        }

        if ($seeded === 0) {
            $this->command?->warn('CertificatesDemoSeeder: no demo patient matched — nothing seeded.');
        }
    }

    /** @return list<string> */
    private function emails(): array
    {
        $raw = (string) (env('DEMO_STORY_EMAILS') ?: implode(',', self::DEFAULT_EMAILS));

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * The certificates this patient's record can justify.
     *
     * Each entry is written only when its anchor exists. A certificate with no
     * episode behind it would be a clinician's statement about care that never
     * happened, which is precisely the thing a certificate must never be.
     */
    private function seedCertificates(object $patient, ?string $issuer, ?string $fallbackFacility): int
    {
        $patientId = (string) $patient->id;
        $written   = 0;

        // ── Sick leave, out of the stay the record carries ────────────────
        $discharge = DB::table('admissions')
            ->where('patient_id', $patientId)
            ->whereNotNull('discharged_at')
            ->orderByDesc('discharged_at')
            ->first(['discharged_at', 'facility_id', 'admission_reason', 'id']);

        if ($discharge) {
            $from = Carbon::parse($discharge->discharged_at);
            $to   = $from->copy()->addDays(self::SICK_LEAVE_DAYS)->endOfDay();

            $written += $this->write($patient, [
                'key'         => 'passport:certificate:sick-leave',
                'type'        => 'MC',
                'title'       => 'Medical certificate — sick leave',
                'facility_id' => $discharge->facility_id ?: $fallbackFacility,
                'issuer'      => $issuer,
                'issued_at'   => $from,
                'expires_at'  => $to,
                'payload'     => [
                    'certificate_type' => 'sick_leave',
                    'admission_id'     => (string) $discharge->id,
                    'grounds'          => (string) ($discharge->admission_reason ?: ''),
                    'unfit_from'       => $from->toDateString(),
                    'unfit_until'      => $to->toDateString(),
                    'working_days'     => self::SICK_LEAVE_DAYS,
                ],
            ]);
        }

        // ── Fitness to work, at the most recent clinic visit ──────────────
        $visit = DB::table('visits')
            ->where('patient_id', $patientId)
            ->orderByDesc('started_at')
            ->first(['started_at', 'facility_id', 'id']);

        if ($visit) {
            $issuedAt = Carbon::parse($visit->started_at);

            $written += $this->write($patient, [
                'key'         => 'passport:certificate:fitness-to-work',
                'type'        => 'MC',
                'title'       => 'Medical certificate — fitness to work',
                'facility_id' => $visit->facility_id ?: $fallbackFacility,
                'issuer'      => $issuer,
                'issued_at'   => $issuedAt,
                'expires_at'  => $issuedAt->copy()->addMonthsNoOverflow(self::FITNESS_MONTHS)->endOfDay(),
                'payload'     => [
                    'certificate_type' => 'fitness_to_work',
                    'visit_id'         => (string) $visit->id,
                    'finding'          => 'Fit for work. Chronic conditions under review and controlled on current therapy.',
                    'restrictions'     => 'None.',
                    'review_months'    => self::FITNESS_MONTHS,
                ],
            ]);
        }

        // ── International certificate of vaccination — yellow fever ───────
        $dose = DB::table('immunization_records')
            ->where('patient_id', $patientId)
            ->where('vaccine_name', 'like', 'Yellow Fever%')
            ->orderByDesc('administered_at')
            ->first(['administered_at', 'facility_id', 'vaccine_name', 'id']);

        if ($dose && $dose->administered_at) {
            $written += $this->write($patient, [
                'key'         => 'passport:certificate:yellow-fever',
                'type'        => 'ICV',
                'title'       => 'International certificate of vaccination — yellow fever',
                'facility_id' => $dose->facility_id ?: $fallbackFacility,
                'issuer'      => $issuer,
                'issued_at'   => Carbon::parse($dose->administered_at),
                // No expiry: valid for the life of the person vaccinated
                // (IHR Annex 7, in force since 11 July 2016).
                'expires_at'  => null,
                'payload'     => [
                    'certificate_type'  => 'vaccination',
                    'immunisation_id'   => (string) $dose->id,
                    'vaccine'           => (string) $dose->vaccine_name,
                    'validity'          => 'Life of the person vaccinated (IHR Annex 7).',
                ],
            ]);
        }

        return $written;
    }

    /**
     * One certificate row, rewritten in place on every run.
     *
     * Keyed on a UUID derived from the patient and the certificate's own key,
     * so the row, its number and its verification code are the same on the
     * hundredth run as on the first — which is what lets this be re-run on the
     * demo server without growing a pile of near-duplicate certificates.
     */
    private function write(object $patient, array $spec): int
    {
        $patientId = (string) $patient->id;
        $id        = DemoStoryIds::derived($patientId, $spec['key']);
        $issuedAt  = $spec['issued_at'];
        $payload   = json_encode($spec['payload'], JSON_UNESCAPED_UNICODE);

        [$number, $code] = $this->identifiers($patientId, $spec['key'], $spec['type'], (int) $issuedAt->format('Y'));

        DB::table('official_documents')->updateOrInsert(['id' => $id], [
            'document_type'     => $spec['type'],
            'document_number'   => $number,
            'verification_code' => $code,
            'patient_id'        => $patientId,
            'health_id'         => $patient->health_id,
            'facility_id'       => $spec['facility_id'],
            'organization_id'   => null,
            'issuer_user_id'    => $spec['issuer'],
            'template_id'       => $this->template($spec['type']),
            'template_version'  => '1.0',
            'status'            => 'issued',
            'version'           => '1.0',
            'sensitivity_level' => 'normal',
            'title'             => $spec['title'],
            'payload_json'      => $payload,
            'payload_hash'      => hash('sha256', (string) $payload),
            // pdf_path is left null on purpose — see the class docblock.
            'issued_at'         => $issuedAt,
            'released_at'       => $issuedAt,
            'expires_at'        => $spec['expires_at'],
            'revoked_at'        => null,
            'is_demo'           => true,
            'demo_seed_key'     => DemoStoryIds::seedKey($patientId, $spec['key']),
            'demo_reset_group'  => 'passport',
            // The certificate's own dates, not now(): anything ordering by
            // created_at would file last year's certificate under this week.
            'created_at'        => $issuedAt,
            'updated_at'        => $issuedAt,
        ]);

        return 1;
    }

    /**
     * A document number and verification code in the platform's own format,
     * derived rather than random so a re-run produces the same pair.
     *
     * The check character comes from DocumentNumberService so these read
     * exactly like an issued document's identifiers and pass the same check —
     * a demo certificate whose number is malformed teaches a reviewer the wrong
     * thing about what the register looks like.
     *
     * @return array{0:string,1:string}
     */
    private function identifiers(string $patientId, string $key, string $type, int $year): array
    {
        $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $hash    = sha1($patientId . ':' . $key);
        $random  = '';

        for ($i = 0; $i < 4; $i++) {
            $random .= $charset[hexdec(substr($hash, $i * 2, 2)) % 36];
        }

        $numbers = app(DocumentNumberService::class);
        $base    = "{$type}-CM-{$year}-{$random}";
        $vBase   = "VFY-CM-{$type}-{$year}-{$random}";

        return [
            $base . '-' . $numbers->calculateCheckDigit($base),
            $vBase . '-' . $numbers->calculateCheckDigit($vBase),
        ];
    }

    /**
     * The template row a document is required to point at.
     *
     * `official_documents.template_id` is NOT NULL behind a foreign key onto
     * `document_templates`, so there is no way to record a certificate without
     * one; this is the only row here that is not read from the patient's
     * history. An existing row with the same code is reused rather than
     * duplicated — `template_code` is unique.
     */
    private function template(string $type): string
    {
        $code     = $type . '-DEMO';
        $existing = DB::table('document_templates')->where('template_code', $code)->value('id');

        if ($existing) {
            return (string) $existing;
        }

        $id = (string) Uuid::uuid5(Uuid::NAMESPACE_OID, 'opescare:passport:certificate-template:' . $code);

        DB::table('document_templates')->updateOrInsert(['id' => $id], [
            'template_code' => $code,
            'document_type' => $type,
            'language'      => 'en',
            'version'       => '1.0',
            'status'        => 'published',
            // The passport reads the register, never the template body; a
            // certificate the patient can hold is rendered from the record by
            // PatientDocumentService, not from a stored HTML blob.
            'html_template' => '<div>OpesCare demonstration certificate</div>',
            'is_demo'       => true,
            'demo_reset_group' => 'passport',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $id;
    }
}
