<?php

namespace App\Console\Commands;

use App\Services\Terminology\TerminologyReconciler;
use Illuminate\Console\Command;

/**
 * opescare:terminology-gaps — print the terminology reconciliation report.
 *
 * READ-ONLY. This command has no --fix, no --normalise and no --apply, and it
 * must never grow one. It issues SELECTs and prints what it found; deciding
 * what to do about a mis-coded record is a clinical act with an author, and
 * that author is a person at the review desk, not a cron job.
 *
 * Exit code is 0 whether or not gaps were found. A gap is a fact to look at,
 * not a build failure, and wiring it into CI as a red would push somebody to
 * make the report quiet rather than make the data right.
 */
class TerminologyGapReport extends Command
{
    protected $signature = 'opescare:terminology-gaps
                            {--json : Emit the raw report as JSON instead of a table}';

    protected $description = 'Report clinical values that do not resolve against the code dictionary. Reads only; changes nothing.';

    public function handle(TerminologyReconciler $reconciler): int
    {
        $report = $reconciler->report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Terminology reconciliation — read-only. Nothing below has been changed.');
        $this->newLine();

        $this->line('Vocabularies loaded in this deployment:');
        $vocabularies = $reconciler->vocabularies();

        if ($vocabularies === []) {
            $this->warn('  none — the dictionary is empty, so every column below will read as unbacked.');
        }

        foreach ($vocabularies as $v) {
            $this->line(sprintf('  %-18s %7s rows (%s active)', $v['system'], number_format($v['total']), number_format($v['active'])));
        }

        $this->newLine();

        foreach ($report as $finding) {
            $this->renderFinding($finding);
        }

        $this->renderSummary($report);

        return self::SUCCESS;
    }

    private function renderFinding(array $f): void
    {
        $this->line('────────────────────────────────────────────────────────────────');
        $this->line('<options=bold>' . $f['key'] . '</>  expected: ' . implode(' | ', $f['systems']));

        switch ($f['state']) {
            case TerminologyReconciler::STATE_COLUMN_ABSENT:
                $this->line('  <fg=gray>NOT PRESENT in this deployment — no such table or column. Nothing to check.</>');
                $this->newLine();

                return;

            case TerminologyReconciler::STATE_EMPTY:
                $this->line(sprintf('  EMPTY — %s rows, none carrying a value. Empty is not the same as zero gaps:', number_format($f['rows'])));
                $this->line('  nothing here is coded at all.');
                $this->newLine();

                return;

            case TerminologyReconciler::STATE_VOCABULARY_ABSENT:
                $this->line(sprintf(
                    '  UNBACKED — %s distinct values over %s rows, and none of the expected',
                    number_format($f['distinct']),
                    number_format($f['filled'])
                ));
                $this->line('  vocabularies is loaded here, so nothing can be checked against anything.');
                $this->renderValues($f);
                $this->renderDuplicates($f);
                $this->newLine();

                return;
        }

        $this->line(sprintf(
            '  checked against %s — %s of %s distinct values resolve (%s of %s rows)',
            $f['system'],
            number_format($f['resolved_values']),
            number_format($f['distinct']),
            number_format($f['resolved_rows']),
            number_format($f['filled'])
        ));

        if ($f['unresolved'] === []) {
            $this->line('  <fg=green>every value resolves cleanly.</>');
        }

        foreach ($f['unresolved'] as $u) {
            $this->line(sprintf(
                '  <fg=red>UNRESOLVED</> %s  (%s rows)%s',
                $u['value'],
                number_format($u['rows']),
                $u['heading_only'] ? '  — present in the vocabulary but not as an assignable code' : ''
            ));

            foreach ($u['suggestions'] as $s) {
                $this->line(sprintf('      near miss (%s): %s  %s', $s['basis'], $s['code'], $s['display']));
            }

            if ($u['suggestions'] !== []) {
                $this->line('      suggestions only — not applied, and not necessarily right.');
            }
        }

        $this->renderDuplicates($f);

        foreach ($f['whitespace'] as $w) {
            $this->line(sprintf('  <fg=yellow>PADDED</> "%s" carries leading or trailing whitespace (%s rows)', $w['value'], number_format($w['rows'])));
        }

        $this->newLine();
    }

    private function renderValues(array $f): void
    {
        foreach (array_slice($f['values'], 0, 20) as $v) {
            $this->line(sprintf('    %-24s %s rows', $v['value'], number_format($v['rows'])));
        }

        if (count($f['values']) > 20) {
            $this->line(sprintf('    ... and %s more', number_format(count($f['values']) - 20)));
        }
    }

    private function renderDuplicates(array $f): void
    {
        foreach ($f['duplicates'] as $d) {
            $members = implode('  /  ', array_map(
                fn ($m) => $m['value'] . ' (' . number_format($m['rows']) . ')',
                $d['members']
            ));

            $this->line(sprintf('  <fg=yellow>PROBABLY THE SAME</> [%s] %s', $d['rule'], $members));
        }
    }

    private function renderSummary(array $report): void
    {
        $unresolved = 0;
        $duplicates = 0;
        $unbacked = 0;
        $absent = 0;

        foreach ($report as $f) {
            $unresolved += count($f['unresolved']);
            $duplicates += count($f['duplicates']);
            $unbacked += $f['state'] === TerminologyReconciler::STATE_VOCABULARY_ABSENT ? 1 : 0;
            $absent += $f['state'] === TerminologyReconciler::STATE_COLUMN_ABSENT ? 1 : 0;
        }

        $this->line('════════════════════════════════════════════════════════════════');
        $this->line(sprintf(
            '%d unresolved values · %d probable duplicate pairs · %d unbacked columns · %d columns not in this deployment',
            $unresolved,
            $duplicates,
            $unbacked,
            $absent
        ));
        $this->line('Nothing was written. Every one of these is for a person to decide.');
        $this->newLine();
    }
}
