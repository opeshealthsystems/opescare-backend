<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\CareFacility;
use App\Models\CareTeamMember;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\ProviderCredential;
use App\Models\ProviderProfile;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Patient\Passport\PageSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Page 21 — Care Providers: who is looking after this patient, and where.
 *
 * The sheet sets the page in three blocks, and this page keeps that order:
 *
 *   1. PRIMARY CARE PROVIDER — the one clinician who holds the thread, set out
 *      as a list of facts.
 *   2. THE CARE TEAM — everyone else on the record, as a table.
 *   3. FACILITIES — the institutions that team works out of, with the published
 *      number to call. The page's own subtitle promises "the health
 *      professionals AND FACILITIES involved in your care"; this is the half
 *      that answers "where do I go".
 *
 * ── A MEMBER WHO HAS LEFT MUST NEVER READ AS CURRENT ──────────────────────
 *
 * This is the failure that would matter in a clinic. `care_team_members`
 * records the end of a membership twice over — `left_at`, and the `is_active`
 * flag added by the 2026-05-28 migration — and a page that read neither would
 * print a doctor who handed over eighteen months ago as the person to ring
 * today. So:
 *
 *   • A member is CURRENT only when `is_active` is true AND `left_at` is unset
 *     or still in the future. Either column alone ending the membership is
 *     enough to move them to the past; a future `left_at` is a planned
 *     handover, and until that date they really are still on the team.
 *   • A past member is NOT dropped. They are care that happened, and a reader
 *     given a shorter list believes it complete — the treatment pages 05, 07
 *     and 14 settled on. They stay in the table, below the current members,
 *     with the recorded standing printed in a Status column that is never
 *     omitted however uniform the rows look.
 *   • The PRIMARY block only ever shows a CURRENT primary. A primary who has
 *     left keeps their row in the table with its Inactive standing rather than
 *     being set at the head of the page as though nothing had changed, because
 *     that block has no room to say otherwise.
 *
 * ── A CLINICIAN'S PRIVATE NUMBER IS NOT THE PATIENT'S TO CARRY ────────────
 *
 * The sheet prints a Phone and an Email against the named provider. This page
 * prints neither, and the omission is deliberate rather than a missing source.
 * The two candidate columns are `staff_profiles.phone`/`.email` — a facility's
 * HR record of how to reach its employee, which is frequently a personal mobile
 * — and `users.email`, which is a LOGIN. Copying either onto a document the
 * patient carries, photographs and hands across a counter publishes a
 * clinician's personal contact details to everyone who ever sees the passport,
 * and a login address additionally hands out half of a credential.
 *
 * What IS published, deliberately and by the institution itself, is the
 * facility's own number in the national directory (`care_facilities`), and that
 * is what the CONTACT column carries — in the facilities block, attached to the
 * facility whose number it is, so it can never be mistaken for a direct line to
 * the named clinician. Reaching a clinician through their facility's switchboard
 * is how it is done anyway.
 *
 * ── WHAT THE SHEET SHOWS THAT THIS DEPLOYMENT CANNOT SUPPLY ───────────────
 *
 *  • QUALIFICATION ("MBBS (Family Medicine)"). No degree is recorded anywhere.
 *    What is recorded is a LICENCE — `provider_credentials` — so the primary
 *    block carries that instead, and only when it has actually been verified
 *    (`verified_at`), is not expired and is not withdrawn. An unverified
 *    credential is a clinician's own assertion about themselves; printed on a
 *    passport it would read as an attested fact, and someone would rely on it.
 *  • ADDRESS. The directory holds one (`care_facilities.address`) but the
 *    shared passport vocabulary has no column heading for it, so the facilities
 *    block names the institution and its number and stops there. The missing key
 *    is named in the handover rather than invented here.
 *  • SERVICES ("General, Immunization", "Lab Tests"). `care_facility_services`
 *    exists and holds eleven rows across a directory of some 2,700 facilities:
 *    for all but a handful of institutions the column would be blank, and
 *    filling it from the facility TYPE would be this page composing a service
 *    list nobody published.
 *  • The sheet's row NUMBERS (1, 2, 3 …) are artwork, not data; the app draws
 *    its own rows.
 *
 * ── WHERE "SPECIALTY" COMES FROM, AND WHY IN THAT ORDER ───────────────────
 *
 * Two columns could answer it and they disagree on this deployment. The
 * employing facility's HR record of the post (`staff_profiles.job_title`) is
 * read FIRST, and the clinician's own published `provider_profiles.specialty`
 * second — not because the self-published one is worse written, but because it
 * is self-asserted with nothing checking it. The provider_profiles migration
 * makes the same judgement about licence standing, and keeps it out of the
 * clinician's own hands for the same reason. A patient who rings the facility
 * to check should hear back what their passport says.
 */
class CareProvidersPage extends PageSource
{
    /**
     * How many memberships the page reads.
     *
     * A care team is a handful of people, and even a long, complicated history
     * of handovers does not reach sixty. The cap is a guard against a corrupted
     * or duplicated record building a page of thousands of rows — not a
     * summary; this page is meant to be the whole team.
     */
    private const WINDOW = 60;

    /**
     * Registry values that mean "no number", written into the phone column as
     * literal text by the directory imports.
     *
     * Printing "N/A" under a heading that says Contact states that N/A is the
     * number to call. These read as the blank they are.
     */
    private const NOT_A_NUMBER = ['n/a', 'n.a.', 'na', 'none', 'null', 'nil', '-', '--', '—', 'unknown', 'tbd', '0'];

    public function build(Patient $patient): array
    {
        $members = CareTeamMember::query()
            ->where('patient_id', $patient->id)
            ->limit(self::WINDOW)
            ->get();

        if ($members->isEmpty()) {
            return [];
        }

        $members    = $this->ordered($members);
        $providers  = $this->providerNames($members->pluck('provider_id')->all());
        $specialty  = $this->specialties($members->pluck('provider_id')->all());
        $facilities = $this->facilities($members->pluck('facility_id')->all());

        $primary = $members->first(fn (CareTeamMember $m) => $this->isCurrent($m) && (bool) $m->is_primary);

        $sections = [];

        if ($primary !== null) {
            $sections[] = $this->primaryBlock($primary, $providers, $specialty, $facilities);
        }

        // The sheet's second block is "OTHER care providers": whoever is not
        // already set out in full above. With no primary on the record it is
        // simply the whole team.
        $rest = $primary === null
            ? $members
            : $members->reject(fn (CareTeamMember $m) => (string) $m->getKey() === (string) $primary->getKey())->values();

        if ($rest->isNotEmpty()) {
            $sections[] = $this->teamBlock($rest, $providers, $specialty, $facilities);
        }

        $facilityBlock = $this->facilityBlock($members, $facilities);

        if ($facilityBlock !== null) {
            $sections[] = $facilityBlock;
        }

        return $sections;
    }

    // ── The blocks ───────────────────────────────────────────────────────

    /**
     * Block 1 — the clinician who holds the thread, as a list of facts.
     *
     * Name, role, facility and since are always shown: a blank there is itself
     * worth reading ("this membership records no facility"). Specialty, licence
     * and nothing else are shown only when recorded, because a facts block
     * three quarters of which reads "Not recorded" tells the reader less than a
     * shorter one that is entirely true.
     *
     * @param  array<string,string>  $providers
     * @param  array<string,string>  $specialty
     * @param  array<string,array{name:string,type:string,contact:string}>  $facilities
     */
    private function primaryBlock(CareTeamMember $primary, array $providers, array $specialty, array $facilities): array
    {
        $providerId = (string) $primary->provider_id;

        $items = [
            $this->item(__('passport.common_name'), $this->providerName($providerId, $providers)),
        ];

        if (($specialty[$providerId] ?? '') !== '') {
            $items[] = $this->item(__('passport.columns.specialty'), $specialty[$providerId]);
        }

        $items[] = $this->item(__('passport.columns.role'), $this->roleLabel($primary));
        $items[] = $this->item(__('passport.columns.facility'), $this->facilityField($primary, $facilities, 'name'));

        if (($licence = $this->licence($providerId)) !== '') {
            $items[] = $this->item(__('passport.sections.credential'), $licence);
        }

        // The facility's published switchboard — see the note at the head of
        // this class on why it is never the clinician's own number.
        if (($contact = $this->facilityField($primary, $facilities, 'contact')) !== '') {
            $items[] = $this->item(__('passport.columns.contact'), $contact);
        }

        $items[] = $this->item(__('passport.columns.since'), $this->date($primary->joined_at));

        // No `sections` key says "primary care provider". `columns.provider`
        // is the word itself and is already 1:1 in EN/FR, so it is borrowed
        // rather than a key added — the same choice page 08 made for its
        // outcome block.
        return $this->section(__('passport.columns.provider'), $items, 'blue', 'users');
    }

    /**
     * Block 2 — the rest of the team, current members first.
     *
     * @param  Collection<int,CareTeamMember>  $members
     * @param  array<string,string>  $providers
     * @param  array<string,string>  $specialty
     * @param  array<string,array{name:string,type:string,contact:string}>  $facilities
     */
    private function teamBlock(Collection $members, array $providers, array $specialty, array $facilities): array
    {
        $showsSpecialty = $members->contains(
            fn (CareTeamMember $m) => ($specialty[(string) $m->provider_id] ?? '') !== ''
        );

        $columns = [
            __('passport.columns.provider'),
            __('passport.columns.role'),
        ];

        if ($showsSpecialty) {
            $columns[] = __('passport.columns.specialty');
        }

        $columns[] = __('passport.columns.facility');
        $columns[] = __('passport.columns.since');

        // Never dropped, however uniform the record looks: it is the only thing
        // separating a clinician who is still on the team from one who left.
        $columns[] = __('passport.columns.status');

        $rows = $members->map(function (CareTeamMember $m) use ($providers, $specialty, $facilities, $showsSpecialty) {
            $providerId = (string) $m->provider_id;

            $row = [
                $this->cell($this->providerName($providerId, $providers)),
                $this->cell($this->roleLabel($m)),
            ];

            if ($showsSpecialty) {
                $row[] = $this->cell($specialty[$providerId] ?? '');
            }

            $row[] = $this->cell($this->facilityField($m, $facilities, 'name'));
            $row[] = $this->cell($this->date($m->joined_at));
            $row[] = $this->standingCell($m);

            return $row;
        })->all();

        return $this->table(__('passport.sections.providers'), $columns, $rows, 'blue', 'users');
    }

    /**
     * Block 3 — the institutions behind the names, oldest involvement first.
     *
     * One row per facility, not per membership: three clinicians at one
     * hospital is one place to go, and repeating the hospital three times with
     * the same number beside it would make the block longer without saying
     * anything more. "Since" is therefore the earliest date ANY member of this
     * team joined it — which is when the patient's care at that institution
     * begins on this record.
     *
     * @param  Collection<int,CareTeamMember>  $members
     * @param  array<string,array{name:string,type:string,contact:string}>  $facilities
     */
    private function facilityBlock(Collection $members, array $facilities): ?array
    {
        $rows = $members
            ->filter(fn (CareTeamMember $m) => ($facilities[(string) $m->facility_id]['name'] ?? '') !== '')
            ->groupBy(fn (CareTeamMember $m) => (string) $m->facility_id)
            ->map(fn (Collection $group) => [
                'facility' => $facilities[(string) $group->first()->facility_id],
                'since'    => $group->min(fn (CareTeamMember $m) => $this->sortKey($m->joined_at)),
            ])
            ->sortBy('since')
            ->values();

        if ($rows->isEmpty()) {
            return null;
        }

        $showsType    = $rows->contains(fn (array $r) => $r['facility']['type'] !== '');
        $showsContact = $rows->contains(fn (array $r) => $r['facility']['contact'] !== '');

        $columns = [__('passport.columns.facility')];

        if ($showsType) {
            $columns[] = __('passport.columns.type');
        }

        if ($showsContact) {
            $columns[] = __('passport.columns.contact');
        }

        $columns[] = __('passport.columns.since');

        $body = $rows->map(function (array $r) use ($showsType, $showsContact) {
            $row = [$this->cell($r['facility']['name'])];

            if ($showsType) {
                $row[] = $this->cell($r['facility']['type'] === '' ? '' : $this->statusLabel($r['facility']['type']));
            }

            if ($showsContact) {
                $row[] = $this->cell($r['facility']['contact']);
            }

            $row[] = $this->cell($r['since'] === PHP_INT_MAX ? '' : $this->date(Carbon::createFromTimestamp($r['since'])));

            return $row;
        })->all();

        return $this->table(__('passport.columns.facility'), $columns, $body, 'blue', 'building');
    }

    // ── Reading one membership ───────────────────────────────────────────

    /**
     * Is this person on the team TODAY?
     *
     * Both endings count. `is_active` false ends the membership on its own —
     * a handover recorded by clearing the flag and nothing else is still a
     * handover — and so does a `left_at` that has passed. A `left_at` in the
     * future is a planned departure and does not end anything yet.
     */
    private function isCurrent(CareTeamMember $member): bool
    {
        if ($member->is_active !== null && ! $member->is_active) {
            return false;
        }

        $left = $this->sortKey($member->left_at);

        return $left === PHP_INT_MAX || $left > Carbon::now()->getTimestamp();
    }

    /**
     * The standing, as a pill.
     *
     * The words are the record's own two states rendered through the shared
     * status vocabulary, so they read the same here as anywhere else in the
     * passport. The tones are chosen rather than looked up because this is a
     * DERIVED standing, not a recorded status word: a member who has left is
     * not a failure or an alarm — they are simply no longer current — so the
     * pill is neutral rather than the red `statusTone()` would give a word like
     * "expired".
     */
    private function standingCell(CareTeamMember $member): array
    {
        return $this->isCurrent($member)
            ? $this->cell($this->statusLabel('active'), 'success')
            : $this->cell($this->statusLabel('inactive'), 'neutral');
    }

    /**
     * The recorded role — attending, consulting, nursing, pharmacy, social
     * work — tidied by the shared label helper, so "social_work" reads as
     * "Social work" rather than as a column value.
     */
    private function roleLabel(CareTeamMember $member): string
    {
        return trim((string) $member->role) === '' ? '' : $this->statusLabel($member->role);
    }

    /** @param  array<string,string>  $providers */
    private function providerName(string $providerId, array $providers): string
    {
        $name = trim((string) ($providers[$providerId] ?? ''));

        // The membership itself is a fact even when the account behind it
        // cannot be read; the row keeps its role, facility and dates and says
        // plainly that it is a member of the team.
        return $name !== '' ? $name : __('passport.care_team_member');
    }

    /**
     * @param  array<string,array{name:string,type:string,contact:string}>  $facilities
     * @param  'name'|'type'|'contact'  $field
     */
    private function facilityField(CareTeamMember $member, array $facilities, string $field): string
    {
        return $facilities[(string) $member->facility_id][$field] ?? '';
    }

    // ── Order ────────────────────────────────────────────────────────────

    /**
     * Current members first — the primary at their head — then the people who
     * have left, most recent departure first.
     *
     * Within the current group the order is by how long they have been
     * involved, which is the order the sheet's table reads in: the clinician
     * who has known the patient longest at the top.
     *
     * @param  Collection<int,CareTeamMember>  $members
     * @return Collection<int,CareTeamMember>
     */
    private function ordered(Collection $members): Collection
    {
        return $members
            ->sortBy(fn (CareTeamMember $m) => [
                $this->isCurrent($m) ? 0 : 1,
                $this->isCurrent($m) && $m->is_primary ? 0 : 1,
                // Current: longest-standing first. Past: latest departure
                // first, so the person who handed over most recently — the one
                // a reader is most likely to be asking about — is at the top.
                $this->isCurrent($m) ? $this->sortKey($m->joined_at) : -$this->sortKey($m->left_at ?: $m->joined_at),
            ])
            ->values();
    }

    /** A timestamp for ordering; a date that cannot be read sorts last rather than to 1970. */
    private function sortKey($value): int
    {
        if (! $value) {
            return PHP_INT_MAX;
        }

        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    // ── Resolving what sits behind the ids ───────────────────────────────

    /**
     * The clinicians' names.
     *
     * The demo-isolation scope is lifted because this is a lookup BY ID of
     * someone already named on the patient's own care team, not a search: with
     * the scope on, a real clinician would lose their name whenever the app
     * runs in demo mode, and a demo one whenever it does not. Page 08 resolves
     * its attending clinicians the same way.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function providerNames(array $ids): array
    {
        $ids = $this->ids($ids);

        if ($ids === []) {
            return [];
        }

        try {
            return array_map('strval', User::withoutGlobalScope('isolate_demo')
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * What kind of clinician each one is.
     *
     * The employing facility's record of the post first, the clinician's own
     * published specialty second — see the note at the head of this class. A
     * provider with neither gets an honest blank, and the column disappears
     * when no one on the team has either.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function specialties(array $ids): array
    {
        $ids = $this->ids($ids);

        if ($ids === []) {
            return [];
        }

        $posts = [];

        try {
            $posts = StaffProfile::whereIn('user_id', $ids)
                ->pluck('job_title', 'user_id')
                ->all();
        } catch (\Throwable) {
            $posts = [];
        }

        $published = [];

        try {
            $published = ProviderProfile::whereIn('user_id', $ids)
                ->pluck('specialty', 'user_id')
                ->all();
        } catch (\Throwable) {
            // One table being unavailable must not cost the reader the whole
            // page; the specialty simply stays blank.
        }

        $specialties = [];

        foreach ($ids as $id) {
            $specialties[$id] = trim((string) ($posts[$id] ?? '')) ?: trim((string) ($published[$id] ?? ''));
        }

        return $specialties;
    }

    /**
     * The provider's licence, when it has been verified and still stands.
     *
     * Three conditions, all of them the point of the block. `verified_at` set
     * means a credentialing officer checked it against the issuing register —
     * without that it is only what the clinician typed about themselves.
     * `status` must not have been withdrawn, and an expiry date must not have
     * passed: a licence that lapsed last year says the opposite of what a
     * reader would take from seeing it printed here.
     *
     * The most recently issued qualifying credential is shown. The number is
     * included on purpose — it is what makes the line checkable against the
     * issuing body's own register, which is the only reason to print it at all.
     */
    private function licence(string $providerId): string
    {
        if ($providerId === '') {
            return '';
        }

        try {
            $credential = ProviderCredential::query()
                ->where('provider_id', $providerId)
                ->whereNotNull('verified_at')
                ->whereNotIn('status', ['revoked', 'suspended', 'expired', 'withdrawn', 'rejected'])
                ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', Carbon::now()->toDateString()))
                ->orderByDesc('issued_date')
                ->first();
        } catch (\Throwable) {
            return '';
        }

        if ($credential === null) {
            return '';
        }

        $parts = array_values(array_filter([
            $this->statusLabel($credential->credential_type),
            trim((string) $credential->credential_number),
        ], fn (string $p) => $p !== '' && $p !== __('passport.not_recorded')));

        $line = implode(' · ', $parts);
        $body = trim((string) $credential->issuing_body);

        if ($line === '') {
            return $body;
        }

        return $body === '' ? $line : $line . ' (' . $body . ')';
    }

    /**
     * Name, type and published number for each facility on the team.
     *
     * This platform carries two facility registers — `facilities` (the tenants
     * that record care) and `care_facilities` (the national directory of some
     * 2,700 institutions) — and `care_team_members.facility_id` carries no
     * foreign key, so a row may point at either. Both are asked, in that order,
     * exactly as pages 08 and 14 ask them.
     *
     * The CONTACT can only come from the directory: it is the number the
     * institution publishes about itself, which is the only telephone number on
     * this page that belongs on a document the patient carries.
     *
     * @param  list<mixed>  $ids
     * @return array<string,array{name:string,type:string,contact:string}>
     */
    private function facilities(array $ids): array
    {
        $ids = $this->ids($ids);

        if ($ids === []) {
            return [];
        }

        $resolved = [];

        try {
            foreach (Facility::withoutGlobalScope('isolate_demo')->whereIn('id', $ids)->get(['id', 'name', 'type']) as $facility) {
                $resolved[(string) $facility->id] = [
                    'name'    => trim((string) $facility->name),
                    'type'    => trim((string) $facility->type),
                    'contact' => '',
                ];
            }
        } catch (\Throwable) {
            $resolved = [];
        }

        try {
            // Two ways a directory row answers to an id on this page: it is the
            // directory entry FOR an operational facility (facility_id), or the
            // membership pointed straight at the directory row (id).
            $directory = CareFacility::query()
                ->whereIn('facility_id', $ids)
                ->orWhereIn('id', $ids)
                ->get(['id', 'facility_id', 'facility_name', 'facility_type', 'phone_primary']);

            foreach ($directory as $row) {
                foreach ([(string) $row->facility_id, (string) $row->id] as $key) {
                    if ($key === '' || ! in_array($key, $ids, true)) {
                        continue;
                    }

                    $known = $resolved[$key] ?? ['name' => '', 'type' => '', 'contact' => ''];

                    // The operational register wins on name and type where it
                    // has them — that is the row the care was recorded against
                    // — and the directory fills the gaps and supplies the
                    // number, which only it holds.
                    $resolved[$key] = [
                        'name'    => $known['name'] !== '' ? $known['name'] : trim((string) $row->facility_name),
                        'type'    => $known['type'] !== '' ? $known['type'] : trim((string) $row->facility_type),
                        'contact' => $this->publishedNumber($row->phone_primary),
                    ];
                }
            }
        } catch (\Throwable) {
            // One register being unavailable must not cost the reader the whole
            // team; the facility simply keeps whatever the other one knew.
        }

        return $resolved;
    }

    /** A directory phone number, or the blank that "N/A" actually means. */
    private function publishedNumber($value): string
    {
        $number = trim((string) $value);

        return in_array(strtolower($number), self::NOT_A_NUMBER, true) ? '' : $number;
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<string>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('strval', $ids))));
    }
}
