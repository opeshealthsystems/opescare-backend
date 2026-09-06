<?php

namespace App\Services\Patient;

use App\Models\Admission;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\CarePlan;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\PatientRecordedVital;
use App\Models\Prescription;
use App\Models\RadiologyReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The OpesCare Health Passport — one patient's record, organised as thirty
 * pages.
 *
 * The Health ID says WHO SOMEONE IS. The Passport organises WHAT IS KNOWN
 * ABOUT THEIR HEALTH, in the canonical page order the printed passport uses,
 * so a patient (or a clinician they have let in) can leaf through a whole life
 * of care rather than hunting through separate screens.
 *
 * ── EVERY PAGE IS BUILT FROM THE PATIENT'S OWN RECORDS ────────────────────
 *
 * There is no specimen content here and no placeholder prose. A page with
 * nothing on it returns NO SECTIONS, and the app says so plainly: "Nothing has
 * been recorded on this page yet." That is a true statement about the record.
 * Inventing a plausible-looking line for an empty page would make the passport
 * worse than useless — it would make it dangerous, because a clinician reading
 * "no known allergies" must be able to trust that somebody actually checked.
 *
 * Some pages have no source in this deployment at all (procedures, cardiology,
 * maternal and child health, family history, screening, devices and
 * transfusions). They are part of the canonical sequence, so they keep their
 * place and their number, and they are honestly empty until the platform
 * records those domains. A missing page would be a worse lie than an empty one.
 *
 * ── SCOPE ────────────────────────────────────────────────────────────────
 *
 * The caller hands in the patient the request resolved. On the mobile API that
 * is the token's own patient, or — when a guardian is acting for a dependent —
 * the dependent, because ActAsDependent has already swapped the id and checked
 * the link. This class never decides who may read a record; it only assembles
 * what the caller was already entitled to.
 *
 * Every read is defensive: one domain that fails (a table this deployment does
 * not carry, a relation that is null) must cost the reader that section, not
 * the whole passport.
 */
class HealthPassportBuilder
{
    public const VERSION = 'HP-2.1';

    public const TOTAL_PAGES = 30;

    /** Recent-history windows, so a page is a useful summary rather than a dump. */
    private const RECENT_LIMIT = 8;

    /**
     * The canonical thirty, in order. Titles live in lang/{en,fr}/passport.php.
     *
     * @var list<array{id: string, number: int, icon: string}>
     */
    public const PAGES = [
        ['id' => 'cover', 'number' => 1, 'icon' => 'shield-check'],
        ['id' => 'patient-identity', 'number' => 2, 'icon' => 'id-card'],
        ['id' => 'emergency-profile', 'number' => 3, 'icon' => 'first-aid'],
        ['id' => 'medical-summary', 'number' => 4, 'icon' => 'document-text'],
        ['id' => 'allergies-alerts', 'number' => 5, 'icon' => 'alert-triangle'],
        ['id' => 'current-medications', 'number' => 6, 'icon' => 'pill'],
        ['id' => 'conditions', 'number' => 7, 'icon' => 'activity'],
        ['id' => 'encounters', 'number' => 8, 'icon' => 'calendar-check'],
        ['id' => 'hospital-admissions', 'number' => 9, 'icon' => 'building'],
        ['id' => 'procedures', 'number' => 10, 'icon' => 'first-aid'],
        ['id' => 'laboratory', 'number' => 11, 'icon' => 'flask'],
        ['id' => 'radiology', 'number' => 12, 'icon' => 'image'],
        ['id' => 'cardiology', 'number' => 13, 'icon' => 'heart-pulse'],
        ['id' => 'immunization', 'number' => 14, 'icon' => 'shield-check'],
        ['id' => 'prescriptions', 'number' => 15, 'icon' => 'document-text'],
        ['id' => 'dispensing', 'number' => 16, 'icon' => 'pill'],
        ['id' => 'referrals', 'number' => 17, 'icon' => 'arrow-right'],
        ['id' => 'medical-certificates', 'number' => 18, 'icon' => 'document-text'],
        ['id' => 'discharge-summary', 'number' => 19, 'icon' => 'folder-plus'],
        ['id' => 'maternal-child', 'number' => 20, 'icon' => 'baby'],
        ['id' => 'care-providers', 'number' => 21, 'icon' => 'users'],
        ['id' => 'insurance', 'number' => 22, 'icon' => 'credit-card'],
        ['id' => 'consent-sharing', 'number' => 23, 'icon' => 'lock'],
        ['id' => 'verification', 'number' => 24, 'icon' => 'qr'],
        ['id' => 'vitals', 'number' => 25, 'icon' => 'heart-rate'],
        ['id' => 'family-genetic', 'number' => 26, 'icon' => 'users'],
        ['id' => 'preventive-screening', 'number' => 27, 'icon' => 'check-circle'],
        ['id' => 'devices-transfusion', 'number' => 28, 'icon' => 'activity'],
        ['id' => 'care-plans', 'number' => 29, 'icon' => 'clipboard'],
        ['id' => 'back-cover', 'number' => 30, 'icon' => 'shield-check'],
    ];

    /** The passport's covers: rendered by the app, not filled from records. */
    private const COVER_PAGES = ['cover', 'back-cover'];

    /**
     * Pages whose domain this deployment does not record yet. They keep their
     * place in the sequence and say plainly that nothing is recorded.
     */
    private const NOT_YET_SOURCED = [
        'procedures',
        'cardiology',
        'maternal-child',
        'family-genetic',
        'preventive-screening',
        'devices-transfusion',
    ];

    /** The head of the passport: status, identity, and what each page holds. */
    public function summary(Patient $patient): array
    {
        return [
            'passport_version' => self::VERSION,
            'status'           => $this->credentialStatus($patient),
            'health_id'        => $patient->health_id,
            'display_name'     => $this->displayName($patient),
            'last_updated_at'  => optional($patient->updated_at)->toIso8601String(),
            'total_pages'      => self::TOTAL_PAGES,
            'pages'            => array_map(fn (array $p) => [
                'id'       => $p['id'],
                'number'   => $p['number'],
                'icon'     => $p['icon'],
                'title'    => __("passport.pages.{$p['id']}.title"),
                'subtitle' => __("passport.pages.{$p['id']}.subtitle"),
            ], self::PAGES),
        ];
    }

    /** One page, or null when the id is not one of the thirty. */
    public function page(Patient $patient, string $pageId): ?array
    {
        $meta = collect(self::PAGES)->firstWhere('id', $pageId);

        if (! $meta) {
            return null;
        }

        return [
            'id'       => $meta['id'],
            'number'   => $meta['number'],
            'icon'     => $meta['icon'],
            'title'    => __("passport.pages.{$meta['id']}.title"),
            'subtitle' => __("passport.pages.{$meta['id']}.subtitle"),
            'kind'     => in_array($meta['id'], self::COVER_PAGES, true) ? $meta['id'] : 'record',
            'sections' => $this->sectionsFor($patient, $meta['id']),
        ];
    }

    /**
     * @return list<array{title: string, items: list<array{label: string, value: string, tone?: string}>}>
     */
    private function sectionsFor(Patient $patient, string $pageId): array
    {
        if (in_array($pageId, self::COVER_PAGES, true) || in_array($pageId, self::NOT_YET_SOURCED, true)) {
            return [];
        }

        try {
            return match ($pageId) {
                'patient-identity'     => $this->identity($patient),
                'emergency-profile'    => $this->emergency($patient),
                'medical-summary'      => $this->medicalSummary($patient),
                'allergies-alerts'     => $this->allergies($patient),
                'current-medications'  => $this->medications($patient),
                'conditions'           => $this->conditions($patient),
                'encounters'           => $this->encounters($patient),
                'hospital-admissions'  => $this->admissions($patient),
                'laboratory'           => $this->laboratory($patient),
                'radiology'            => $this->radiology($patient),
                'immunization'         => $this->immunisations($patient),
                'prescriptions'        => $this->prescriptions($patient),
                'dispensing'           => $this->dispensing($patient),
                'referrals'            => $this->referrals($patient),
                'medical-certificates' => $this->documents($patient, ['medical_certificate', 'certificate']),
                'discharge-summary'    => $this->discharges($patient),
                'care-providers'       => $this->careProviders($patient),
                'insurance'            => $this->insurance($patient),
                'consent-sharing'      => $this->consent($patient),
                'verification'         => $this->verification($patient),
                'vitals'               => $this->vitals($patient),
                'care-plans'           => $this->carePlans($patient),
                default                => [],
            };
        } catch (\Throwable $e) {
            // One domain must not cost the reader their whole passport. An
            // empty page reads as "nothing recorded", which is wrong here, so
            // it is logged loudly rather than passed off as a clean page.
            Log::warning('health passport: a page could not be assembled', [
                'patient_id' => $patient->id,
                'page'       => $pageId,
                'error'      => $e->getMessage(),
            ]);

            return [];
        }
    }

    // ── Pages ────────────────────────────────────────────────────────────

    private function identity(Patient $patient): array
    {
        $items = [
            $this->item(__('passport.common_name'), $this->displayName($patient)),
            $this->item(__('passport.health_id'), (string) $patient->health_id),
        ];

        if ($patient->date_of_birth) {
            $items[] = $this->item(__('passport.dob'), Carbon::parse($patient->date_of_birth)->format('d M Y'));
        }

        if ($patient->sex) {
            $items[] = $this->item(__('passport.sex'), $this->sexLabel((string) $patient->sex));
        }

        $sections = [$this->section('identity', $items)];

        $status = $this->credentialStatus($patient);
        $sections[] = $this->section('credential', [
            $this->item(__('passport.status'), $this->statusLabel($status), $status === 'ACTIVE' ? 'success' : 'warning'),
            $this->item(__('passport.version'), self::VERSION),
        ]);

        return $sections;
    }

    private function emergency(Patient $patient): array
    {
        $items = [];

        if ($patient->blood_group) {
            $items[] = $this->item(__('passport.blood_group'), (string) $patient->blood_group);
        }

        $allergies = $this->allergyRows($patient);
        $items[] = $this->item(
            __('passport.allergies_label'),
            $allergies->isEmpty() ? __('passport.none_recorded') : $allergies->take(4)->implode(', '),
            $allergies->isEmpty() ? null : 'danger'
        );

        $contact = $patient->emergency_contact;
        if (is_array($contact) && filled($contact['name'] ?? null)) {
            $items[] = $this->item(__('passport.emergency_contact'), trim(($contact['name'] ?? '') . ' ' . ($contact['phone'] ?? '')));
        }

        return $items === [] ? [] : [$this->section('emergency', $items)];
    }

    private function medicalSummary(Patient $patient): array
    {
        $conditions = Diagnosis::where('patient_id', $patient->id)->count();
        $medicines  = Prescription::where('patient_id', $patient->id)->whereIn('status', ['active', 'partially_dispensed'])->count();
        $encounters = Encounter::where('patient_id', $patient->id)->count();
        $allergies  = AllergyRecord::where('patient_id', $patient->id)->count();

        if ($conditions + $medicines + $encounters + $allergies === 0) {
            return [];
        }

        return [$this->section('summary', [
            $this->item(__('passport.pages.conditions.title'), (string) $conditions),
            $this->item(__('passport.pages.current-medications.title'), (string) $medicines),
            $this->item(__('passport.pages.encounters.title'), (string) $encounters),
            $this->item(__('passport.pages.allergies-alerts.title'), (string) $allergies, $allergies > 0 ? 'warning' : null),
        ])];
    }

    private function allergies(Patient $patient): array
    {
        $rows = AllergyRecord::where('patient_id', $patient->id)->orderByDesc('created_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('allergies', $rows->map(fn (AllergyRecord $a) => $this->item(
            (string) $a->substance,
            (string) ($a->severity ?: $a->status ?: __('passport.recorded')),
            in_array(strtolower((string) $a->severity), ['severe', 'critical', 'high'], true) ? 'danger' : 'warning'
        ))->all())];
    }

    private function medications(Patient $patient): array
    {
        $rows = Prescription::with('items')
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'partially_dispensed'])
            ->orderByDesc('prescribed_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        $items = [];
        foreach ($rows as $rx) {
            foreach ($rx->items as $line) {
                $items[] = $this->item(
                    (string) $line->drug_name,
                    trim(implode(' · ', array_filter([$line->dose, $line->frequency])) ?: __('passport.as_prescribed'))
                );
            }
        }

        return $items === [] ? [] : [$this->section('medications', array_slice($items, 0, 12))];
    }

    private function conditions(Patient $patient): array
    {
        $rows = Diagnosis::where('patient_id', $patient->id)->orderByDesc('created_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('conditions', $rows->map(fn (Diagnosis $d) => $this->item(
            (string) ($d->display_name ?: $d->snomed_display ?: $d->code ?: __('passport.recorded')),
            (string) ($d->status ?: __('passport.recorded'))
        ))->all())];
    }

    private function encounters(Patient $patient): array
    {
        $rows = Encounter::where('patient_id', $patient->id)->orderByDesc('started_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('encounters', $rows->map(fn (Encounter $e) => $this->item(
            $e->started_at ? Carbon::parse($e->started_at)->format('d M Y') : __('passport.not_recorded'),
            (string) ($e->reason_for_encounter ?: $e->encounter_type ?: __('passport.recorded'))
        ))->all())];
    }

    private function admissions(Patient $patient): array
    {
        $rows = Admission::where('patient_id', $patient->id)->orderByDesc('admitted_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('admissions', $rows->map(fn (Admission $a) => $this->item(
            $a->admitted_at ? Carbon::parse($a->admitted_at)->format('d M Y') : __('passport.not_recorded'),
            (string) ($a->admission_reason ?: $a->status ?: __('passport.recorded'))
        ))->all())];
    }

    private function laboratory(Patient $patient): array
    {
        $rows = LabOrder::where('patient_id', $patient->id)->orderByDesc('ordered_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('laboratory', $rows->map(fn (LabOrder $o) => $this->item(
            (string) $o->test_name,
            $o->resulted_at ? Carbon::parse($o->resulted_at)->format('d M Y') : $this->statusLabel((string) $o->status)
        ))->all())];
    }

    private function radiology(Patient $patient): array
    {
        $rows = RadiologyReport::where('patient_id', $patient->id)->orderByDesc('created_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('imaging', $rows->map(fn (RadiologyReport $r) => $this->item(
            (string) ($r->study_description ?? $r->modality ?? __('passport.recorded')),
            $r->created_at ? $r->created_at->format('d M Y') : __('passport.not_recorded')
        ))->all())];
    }

    private function immunisations(Patient $patient): array
    {
        $rows = ImmunizationRecord::where('patient_id', $patient->id)->orderByDesc('administered_at')->limit(12)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('immunisations', $rows->map(fn (ImmunizationRecord $i) => $this->item(
            (string) $i->vaccine_name,
            $i->administered_at ? Carbon::parse($i->administered_at)->format('d M Y') : __('passport.not_recorded'),
            'success'
        ))->all())];
    }

    private function prescriptions(Patient $patient): array
    {
        $rows = Prescription::where('patient_id', $patient->id)->orderByDesc('prescribed_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('prescriptions', $rows->map(fn (Prescription $p) => $this->item(
            (string) ($p->reference ?: __('passport.recorded')),
            $p->prescribed_at ? Carbon::parse($p->prescribed_at)->format('d M Y') : __('passport.not_recorded')
        ))->all())];
    }

    private function dispensing(Patient $patient): array
    {
        $rows = Prescription::where('patient_id', $patient->id)->whereNotNull('dispensed_at')
            ->orderByDesc('dispensed_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('dispensing', $rows->map(fn (Prescription $p) => $this->item(
            (string) ($p->dispensing_pharmacy_name ?: $p->reference ?: __('passport.recorded')),
            Carbon::parse($p->dispensed_at)->format('d M Y'),
            'success'
        ))->all())];
    }

    private function referrals(Patient $patient): array
    {
        if (! class_exists(\App\Models\ReferralCase::class)) {
            return [];
        }

        $rows = \App\Models\ReferralCase::where('patient_id', $patient->id)->orderByDesc('created_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('referrals', $rows->map(fn ($r) => $this->item(
            (string) ($r->reason ?? $r->specialty ?? __('passport.recorded')),
            (string) ($r->status ?? __('passport.recorded'))
        ))->all())];
    }

    /** Certificates and other issued documents, by type. */
    private function documents(Patient $patient, array $types): array
    {
        $rows = OfficialDocument::where('patient_id', $patient->id)
            ->whereIn('document_type', $types)
            ->orderByDesc('issued_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('documents', $rows->map(fn (OfficialDocument $d) => $this->item(
            (string) ($d->title ?: $d->document_number ?: __('passport.recorded')),
            $d->issued_at ? Carbon::parse($d->issued_at)->format('d M Y') : __('passport.not_recorded')
        ))->all())];
    }

    private function discharges(Patient $patient): array
    {
        $rows = Admission::where('patient_id', $patient->id)->whereNotNull('discharged_at')
            ->orderByDesc('discharged_at')->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('discharge', $rows->map(fn (Admission $a) => $this->item(
            Carbon::parse($a->discharged_at)->format('d M Y'),
            (string) ($a->discharge_reason ?: $a->discharge_destination ?: __('passport.recorded'))
        ))->all())];
    }

    private function careProviders(Patient $patient): array
    {
        $rows = CareTeamMember::where('patient_id', $patient->id)->limit(self::RECENT_LIMIT)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $names = User::whereIn('id', $rows->pluck('user_id')->filter()->all())->pluck('name', 'id');

        return [$this->section('providers', $rows->map(fn ($m) => $this->item(
            (string) ($names[$m->user_id] ?? __('passport.care_team_member')),
            (string) ($m->role ?? __('passport.recorded'))
        ))->all())];
    }

    private function insurance(Patient $patient): array
    {
        $rows = PatientInsurancePolicy::where('patient_id', $patient->id)->orderByDesc('effective_date')->limit(4)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('insurance', $rows->map(fn (PatientInsurancePolicy $p) => $this->item(
            (string) ($p->policy_number ?: __('passport.recorded')),
            (string) ($p->status ?: __('passport.recorded')),
            strtolower((string) $p->status) === 'active' ? 'success' : null
        ))->all())];
    }

    private function consent(Patient $patient): array
    {
        $rows = ConsentGrant::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now()))
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $facilities = \App\Models\Facility::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $rows->pluck('facility_id')->filter()->all())
            ->pluck('name', 'id');

        return [$this->section('consent', $rows->map(fn (ConsentGrant $g) => $this->item(
            (string) ($facilities[$g->facility_id] ?? __('passport.a_facility')),
            $g->expires_at ? __('passport.until', ['date' => Carbon::parse($g->expires_at)->format('d M Y')]) : $this->statusLabel('ACTIVE'),
            'success'
        ))->all())];
    }

    /**
     * How to prove the passport is current — never a credential in itself.
     *
     * The QR the app shows is minted separately and is short lived; nothing
     * clinical is ever encoded in it. This page only explains the check.
     */
    private function verification(Patient $patient): array
    {
        $status = $this->credentialStatus($patient);

        return [$this->section('verification', [
            $this->item(__('passport.status'), $this->statusLabel($status), $status === 'ACTIVE' ? 'success' : 'warning'),
            $this->item(__('passport.health_id'), (string) $patient->health_id),
            $this->item(__('passport.checked_against'), __('passport.checked_against_value')),
        ])];
    }

    private function vitals(Patient $patient): array
    {
        $rows = PatientRecordedVital::where('patient_id', $patient->id)
            ->orderByDesc('measured_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [$this->section('vitals', $rows->map(function (PatientRecordedVital $v) {
            $value = trim((string) $v->value_primary . ($v->value_secondary ? '/' . $v->value_secondary : '') . ' ' . (string) $v->unit);

            return $this->item(
                (string) ($v->measure instanceof \BackedEnum ? $v->measure->value : $v->measure),
                $value . ($v->measured_at ? ' · ' . Carbon::parse($v->measured_at)->format('d M') : '')
            );
        })->all())];
    }

    private function carePlans(Patient $patient): array
    {
        $plans = CarePlan::where('patient_id', $patient->id)->orderByDesc('start_date')->limit(4)->get();
        $next  = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['scheduled', 'booked', 'confirmed'])
            ->where('scheduled_at', '>=', Carbon::now())
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get();

        $items = $plans->map(fn (CarePlan $p) => $this->item(
            (string) ($p->title ?: __('passport.recorded')),
            (string) ($p->status ?: __('passport.recorded'))
        ))->all();

        foreach ($next as $appointment) {
            $items[] = $this->item(
                __('passport.next_appointment'),
                Carbon::parse($appointment->scheduled_at)->format('d M Y, H:i')
            );
        }

        return $items === [] ? [] : [$this->section('care_plans', $items)];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** @return \Illuminate\Support\Collection<int,string> */
    private function allergyRows(Patient $patient)
    {
        return AllergyRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->pluck('substance')
            ->filter()
            ->values();
    }

    private function credentialStatus(Patient $patient): string
    {
        // identity_status is a backed enum on this model (App\Enums\IdentityStatus)
        // and a plain string on older rows; read both without assuming either.
        $raw    = $patient->identity_status;
        $status = strtolower((string) ($raw instanceof \BackedEnum ? $raw->value : ($raw ?? '')));

        return match ($status) {
            'verified', 'active' => 'ACTIVE',
            'provisional'        => 'PROVISIONAL',
            'suspended'          => 'SUSPENDED',
            'revoked', 'merged'  => 'REVOKED',
            default              => 'PROVISIONAL',
        };
    }

    private function displayName(Patient $patient): string
    {
        return trim(((string) $patient->first_name) . ' ' . ((string) $patient->last_name)) ?: (string) $patient->health_id;
    }

    /**
     * A status the reader can understand, and never a raw translation key.
     *
     * Statuses arrive from several tables and some carry spellings this
     * codebase has not met. A missing translation therefore falls back to the
     * recorded word itself, tidied — "in_progress" reads as "In progress", not
     * as "passport.status_in_progress".
     */
    private function statusLabel(?string $status): string
    {
        $raw = trim((string) $status);

        if ($raw === '') {
            return __('passport.not_recorded');
        }

        $key = 'passport.status_' . strtolower($raw);

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : ucfirst(str_replace('_', ' ', strtolower($raw)));
    }

    private function sexLabel(?string $sex): string
    {
        $key = 'passport.sex_' . strtolower(trim((string) $sex));

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : __('passport.not_recorded');
    }

    private function section(string $key, array $items): array
    {
        return ['title' => __("passport.sections.{$key}"), 'items' => array_values($items)];
    }

    private function item(string $label, string $value, ?string $tone = null): array
    {
        $item = ['label' => $label, 'value' => $value !== '' ? $value : __('passport.not_recorded')];

        if ($tone !== null) {
            $item['tone'] = $tone;
        }

        return $item;
    }
}
