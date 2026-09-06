<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Facility;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;
use Carbon\Carbon;

/**
 * Page 18 — Medical Certificates & Documents.
 *
 * WHAT THIS PAGE IS, AND THE ONE THING IT MUST NOT BE CONFUSED WITH. A patient
 * can hold two very different things, and only one of them belongs here:
 *
 *   1. `official_documents` — a document a facility formally ISSUED and
 *      registered. It has a number, a verification code, an issuing facility
 *      and, usually, a date it stops being valid. Somebody with authority put
 *      their name to it, and a third party — an employer, a school, a border
 *      post — is entitled to rely on it. That is what this page lists.
 *   2. The sheets `PatientDocumentService` renders on demand — a lab report, a
 *      prescription, an immunisation record, a discharge summary. Those are
 *      never stored and are never `official_documents` rows: they are the
 *      record itself, printed at the moment the patient asks for it. They are
 *      deliberately NOT listed here. Putting them on a certificates page would
 *      say a facility issued and stands behind a certificate it never issued,
 *      which is the one claim this page exists to make truthfully.
 *
 * WHY THE STATUS COLUMN IS COMPUTED AND NOT COPIED. `official_documents.status`
 * answers "what happened to this row" (draft, issued, amended, superseded,
 * revoked) and cannot on its own answer the question a reader of this page is
 * actually asking: *is this certificate still good today?* That answer lives in
 * `expires_at`. So the pill is derived, in this order — revoked beats
 * everything, then expiry, then the row's own word:
 *
 *   - revoked (`revoked_at`, or the status word) → Revoked, red. The facility
 *     withdrew it; nobody should accept it.
 *   - `expires_at` in the past → Expired, red. This is the case the page is
 *     built around. Someone will present this to an employer or a school, and a
 *     certificate that ran out in March must not read as if it is current
 *     merely because the row still says "issued".
 *   - otherwise issued/released → Active, green: issued, not withdrawn, in
 *     date. Any other word the register carries is passed through
 *     `statusLabel()`/`statusTone()` untouched, so an unfamiliar state gets no
 *     pill rather than a flattering one.
 *
 * An expiry timestamp that cannot be read yields no pill at all: claiming
 * "Active" over a date the app could not parse is exactly the false
 * reassurance the red pill exists to prevent.
 *
 * WHICH ROWS ARE LISTED. Certificates only (`self::TYPES`) — a prescription
 * document is page 15's business and appears there, not as a certificate. Two
 * statuses are excluded: `draft`, which was never released and so is not
 * something the patient holds, and `entered_in_error`, which the register says
 * should never have existed. A revoked or expired certificate IS listed, and
 * loudly: the patient may be carrying the paper, and being told it is dead is
 * the whole point.
 *
 * WHAT THE SHEET SHOWS THAT THIS PAGE DOES NOT. The reference sheet's document
 * block ends with a "verified by" line naming the verification system, and a
 * security-features checklist. Neither is a fact about a certificate this
 * deployment holds — the checklist is not performed at all (see
 * VerificationPage for why a green tick beside a check nobody runs is a false
 * security claim), and the register records who ISSUED a document, not who last
 * verified it. The issuing facility is shown instead, under "Authorised by",
 * because that is the party actually standing behind the certificate. The
 * document's `verification_code` is not printed: there is no passport label for
 * it, and it is carried on the certificate itself.
 */
class MedicalCertificatesPage extends PageSource
{
    /**
     * The `document_type` values that are a certificate.
     *
     * Two spellings, deliberately. The issuance path in this codebase writes
     * short codes (`RX` for a prescription, `APT` for an appointment
     * confirmation), so certificates follow that convention; the long snake
     * words are what `HealthPassportBuilder::documents()` filtered on before
     * this page existed, and are kept so a deployment already holding such rows
     * does not lose them the day this page ships.
     */
    private const TYPES = [
        'MC',                        // medical certificate
        'ICV',                       // international certificate of vaccination
        'medical_certificate',
        'certificate',
        'vaccination_certificate',
    ];

    /** Statuses that are not a certificate the patient holds. */
    private const NOT_HELD = ['draft', 'entered_in_error'];

    public function build(Patient $patient): array
    {
        $certificates = OfficialDocument::where('patient_id', $patient->id)
            ->whereIn('document_type', self::TYPES)
            ->whereNotIn('status', self::NOT_HELD)
            // Newest first. The `IS NULL` term is not decoration: Postgres puts
            // NULLs first on a DESC sort, so a row with no issue date would
            // otherwise head the table and become the subject of the details
            // block below it.
            ->orderByRaw('issued_at IS NULL')
            ->orderByDesc('issued_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($certificates->isEmpty()) {
            return [];
        }

        $facilities = $this->facilityNames($certificates->pluck('facility_id')->all());

        return [
            $this->table(
                __('passport.sections.documents'),
                [
                    __('passport.columns.document'),
                    __('passport.columns.issued'),
                    __('passport.columns.valid_until'),
                    __('passport.columns.status'),
                ],
                $certificates->map(function (OfficialDocument $doc) {
                    [$label, $tone] = $this->state($doc);

                    return [
                        $this->cell($this->name($doc)),
                        $this->cell($this->date($doc->issued_at)),
                        $this->cell($doc->expires_at ? $this->date($doc->expires_at) : ''),
                        $this->cell($label, $tone),
                    ];
                })->all(),
                'blue',
                'document-text'
            ),

            $this->details($certificates->first(), $facilities),
        ];
    }

    /**
     * The identifying detail of the certificate at the top of the table.
     *
     * The sheet gives an issued document a label/value block — document id,
     * issue date, validity, who stands behind it, status — and that is the
     * block a person presenting a certificate is read from. It describes the
     * NEWEST certificate, which is the one being presented in practically every
     * case, and it names it in its first line so it can never be read as
     * belonging to some other row of the table above.
     *
     * The number and the issuing facility are here rather than in the table
     * because this page is read on a phone: six columns of a certificate
     * register is unreadable, and these two are the fields a checker types in
     * rather than scans down.
     */
    private function details(OfficialDocument $doc, array $facilities): array
    {
        [$label, $tone] = $this->state($doc);

        return $this->section(
            __('passport.sections.document_details'),
            [
                $this->item(__('passport.columns.document'), $this->name($doc)),
                $this->item(__('passport.blocks.document_id'), (string) ($doc->document_number ?: '')),
                $this->item(__('passport.blocks.issued_on'), $this->date($doc->issued_at)),
                $this->item(__('passport.blocks.valid_until_label'), $doc->expires_at ? $this->date($doc->expires_at) : ''),
                $this->item(__('passport.blocks.authorised_by'), (string) ($facilities[(string) $doc->facility_id] ?? '')),
                $this->item(__('passport.status'), $label, $tone),
            ],
            'teal',
            'shield-check'
        );
    }

    /** What the certificate calls itself, falling back to its number. */
    private function name(OfficialDocument $doc): string
    {
        return (string) ($doc->title ?: $doc->document_number ?: '');
    }

    /**
     * Whether the certificate is still good, as a label and a pill.
     *
     * @return array{0:string,1:?string}
     */
    private function state(OfficialDocument $doc): array
    {
        $status = strtolower(trim((string) $doc->status));

        if ($doc->revoked_at !== null || $status === 'revoked') {
            return [$this->statusLabel('revoked'), 'danger'];
        }

        if ($doc->expires_at) {
            $expired = $this->hasExpired($doc->expires_at);

            if ($expired === true) {
                return [$this->statusLabel('expired'), 'danger'];
            }

            // An expiry the app could not read. The certificate is not claimed
            // to be current — the reader gets the register's own word and no
            // colour. A certificate with NO expiry is a different thing
            // entirely and falls through: it does not run out.
            if ($expired === null) {
                return [$this->statusLabel($status), $this->statusTone($status)];
            }
        }

        return match ($status) {
            'issued', 'released', 'active' => [$this->statusLabel('active'), 'success'],
            default                        => [$this->statusLabel($status), $this->statusTone($status)],
        };
    }

    /**
     * true expired · false still in date · null the recorded expiry cannot be
     * read (callers must check for an expiry before asking).
     *
     * The stored instant is compared as it stands rather than being rounded up
     * to the end of its day, which is also how DocumentVerificationService
     * judges the same row when somebody checks the certificate by its code: the
     * passport and the verifier must not disagree about whether a document is
     * live. A certificate read as expired an hour early is an inconvenience;
     * one read as current after it lapsed is the failure this page exists to
     * prevent.
     */
    private function hasExpired($expiresAt): ?bool
    {
        if (! $expiresAt) {
            return null;
        }

        try {
            return Carbon::parse($expiresAt)->isPast();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Names for the issuing facilities.
     *
     * The demo-isolation scope is lifted for the lookup only. A facility row
     * carries the flag of the environment that created it, not of the record
     * pointing at it — the real Yaoundé hospital the demo story is told against
     * is an `is_demo = false` row — so the scoped lookup would silently return
     * nothing and the certificate would appear to have been issued by nobody.
     * Nothing but the name is read.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function facilityNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

        if ($ids === []) {
            return [];
        }

        try {
            return array_map('strval', Facility::withoutGlobalScope('isolate_demo')
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
        } catch (\Throwable) {
            // A name that cannot be looked up leaves the honest blank; it never
            // costs the reader the certificate itself.
            return [];
        }
    }
}
