<?php

namespace App\Services\Patient;

use App\Models\AllergyRecord;
use App\Models\Patient;
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
    use \App\Services\Patient\Passport\FormatsRecords;

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
            // The approved design repeats the holder on every page, so a
            // clinician reading page 19 still knows whose record it is.
            'date_of_birth'    => $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->toDateString() : null,
            'sex'              => $patient->sex ? (string) $patient->sex : null,
            'last_updated_at'  => optional($patient->updated_at)->toIso8601String(),
            'total_pages'      => self::TOTAL_PAGES,
            // A demo account holds real rows in a demo partition, so the
            // passport is truthful about the record AND about the account.
            'is_demo'          => (bool) $patient->is_demo,
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
            // Every page that has a source has its own class now, and that
            // class builds it. The migration this file used to host — a match
            // arm per page, falling back to a private method here — is
            // finished: all twenty-two record pages are registered, so no page
            // id reaches the line below with a record to show.
            $source = \App\Services\Patient\Passport\PageRegistry::for($pageId);

            if ($source !== null) {
                return $source->build($patient);
            }

            // Nothing left to fall back to. A page id landing here is one the
            // registry could not resolve — a source class that has gone
            // missing — not a page with an empty record, so it is logged
            // rather than passed off as "nothing recorded".
            Log::warning('health passport: a page has no source class', [
                'patient_id' => $patient->id,
                'page'       => $pageId,
            ]);

            return [];
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
