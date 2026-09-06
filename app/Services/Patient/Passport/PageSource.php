<?php

namespace App\Services\Patient\Passport;

use App\Models\Patient;

/**
 * One page of the Health Passport, built from the patient's own records.
 *
 * WHY A CLASS PER PAGE. The passport is thirty pages and each one reads a
 * different corner of the record with a different shape on the printed sheet.
 * Held in one method-per-page service, the pages could not be worked on
 * independently and every change risked a page nobody was looking at. Each page
 * now owns its file: what it reads, and how the approved design lays it out.
 *
 * THE ONE RULE THAT OVERRIDES EVERY DESIGN CONCERN: never invent. A page shows
 * what the record holds. Where nothing is recorded it returns no sections, and
 * the reader is told plainly that nothing is recorded — which is a fact about
 * the record, not a gap in the app. A passport that fills its empty pages with
 * plausible-looking content is worse than useless in a clinic; someone will act
 * on it.
 *
 * The section shapes below are what the app knows how to draw. They match the
 * blocks in the approved sheets: a table with column headings, a row of counted
 * tiles, a bulleted recommendation card, a summary callout, or a plain list of
 * label/value facts.
 */
abstract class PageSource
{
    use FormatsRecords;

    /** How far back a page reaches before it stops being "recent". */
    protected const RECENT_LIMIT = 8;

    /**
     * The sections of this page, in reading order.
     *
     * @return list<array<string,mixed>>
     */
    abstract public function build(Patient $patient): array;

    // ── The blocks the approved design uses ──────────────────────────────

    /**
     * A table: column headings, then rows. This is the design's workhorse —
     * medicines, conditions, results and vaccines are all tables on the sheet.
     *
     * @param  list<string>  $columns  already-translated headings
     * @param  list<list<array{v:string,pill?:string}|string>>  $rows
     */
    protected function table(string $title, array $columns, array $rows, string $accent = 'blue', ?string $icon = null): array
    {
        return [
            'title'   => $title,
            'accent'  => $accent,
            'icon'    => $icon,
            'layout'  => 'table',
            'columns' => array_values($columns),
            'rows'    => array_values(array_map(
                fn (array $row) => array_values(array_map(
                    fn ($cell) => is_array($cell) ? $cell : ['v' => (string) $cell],
                    $row
                )),
                $rows
            )),
            'items'   => [],
        ];
    }

    /** A cell that carries a coloured pill, as the sheet does for severity and status. */
    protected function cell(string $value, ?string $pill = null): array
    {
        $cell = ['v' => $value !== '' ? $value : __('passport.not_recorded')];

        if ($pill !== null) {
            $cell['pill'] = $pill;
        }

        return $cell;
    }

    /**
     * The row of counted tiles the summary pages open with.
     *
     * @param  list<array{value:string|int,label:string,tone?:string}>  $stats
     */
    protected function stats(string $title, array $stats, string $accent = 'blue', ?string $icon = null): array
    {
        return [
            'title'  => $title,
            'accent' => $accent,
            'icon'   => $icon,
            'layout' => 'stats',
            'stats'  => array_values(array_map(
                fn (array $s) => ['value' => (string) $s['value'], 'label' => $s['label']] + (isset($s['tone']) ? ['tone' => $s['tone']] : []),
                $stats
            )),
            'items'  => [],
        ];
    }

    /** The bulleted recommendation card. */
    protected function bullets(string $title, array $lines, string $accent = 'purple', ?string $icon = null): array
    {
        return [
            'title'  => $title,
            'accent' => $accent,
            'icon'   => $icon,
            'layout' => 'bullets',
            'lines'  => array_values(array_filter(array_map('strval', $lines), fn ($l) => trim($l) !== '')),
            'items'  => [],
        ];
    }

    /** The tinted summary banner that opens a page. */
    protected function callout(string $title, string $body, string $accent = 'green', ?string $icon = null): array
    {
        return [
            'title'  => $title,
            'accent' => $accent,
            'icon'   => $icon,
            'layout' => 'callout',
            'body'   => $body,
            'items'  => [],
        ];
    }

    /**
     * A plain list of label/value facts — the original shape, kept because some
     * pages genuinely are a list of facts rather than a table.
     */
    protected function section(string $title, array $items, string $accent = 'blue', ?string $icon = null): array
    {
        return [
            'title'  => $title,
            'accent' => $accent,
            'icon'   => $icon,
            'layout' => 'list',
            'items'  => array_values($items),
        ];
    }

    protected function item(string $label, string $value, ?string $tone = null): array
    {
        $item = ['label' => $label, 'value' => $value !== '' ? $value : __('passport.not_recorded')];

        if ($tone !== null) {
            $item['tone'] = $tone;
        }

        return $item;
    }

    // ── Shared reading of tone ───────────────────────────────────────────

    /** The design's pill colour for a severity word, or none when unrecognised. */
    protected function severityTone(?string $severity): ?string
    {
        return match (strtolower(trim((string) $severity))) {
            'severe', 'critical', 'high', 'life_threatening' => 'danger',
            'moderate', 'medium', 'warning'                  => 'warning',
            'mild', 'low', 'info'                            => 'caution',
            default                                          => null,
        };
    }

    /** The design's pill colour for a status word, or none when unrecognised. */
    protected function statusTone(?string $status): ?string
    {
        return match (strtolower(trim((string) $status))) {
            'active', 'completed', 'resolved', 'verified', 'dispensed',
            'chronic', 'confirmed', 'discharged'                        => 'success',
            'transferred'                                              => 'neutral',
            'pending', 'in_progress', 'partially_dispensed', 'booked'  => 'warning',
            'cancelled', 'expired', 'revoked', 'failed'                => 'danger',
            default                                                    => null,
        };
    }
}
