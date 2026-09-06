<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\Patient\HealthPassportBuilder;
use Illuminate\Console\Command;

/**
 * Print one Health Passport page exactly as the app receives it.
 *
 * Building a page against a design sheet is guesswork until you can see what
 * the page actually produces for a real record. This prints that, so the shape
 * of a page can be checked against the approved design without exporting the
 * app or waiting for a deploy.
 *
 *   php artisan passport:page current-medications
 *   php artisan passport:page allergies-alerts --health-id=CM-HID-...
 *   php artisan passport:page --all
 */
class ShowPassportPage extends Command
{
    protected $signature = 'passport:page
        {page? : The page id, e.g. current-medications}
        {--health-id= : Which patient to read; defaults to the first demo patient}
        {--all : Print a one-line summary of all thirty pages}
        {--json : Print raw JSON rather than a table}';

    protected $description = 'Print a Health Passport page as the app receives it';

    public function handle(HealthPassportBuilder $builder): int
    {
        $patient = $this->patient();

        if (! $patient) {
            $this->error('No patient found. Pass --health-id, or seed the demo data.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            '<info>%s</info>  %s  %s',
            trim($patient->first_name . ' ' . $patient->last_name),
            $patient->health_id,
            $patient->is_demo ? '(demo account)' : ''
        ));

        if ($this->option('all')) {
            foreach (HealthPassportBuilder::PAGES as $meta) {
                $page  = $builder->page($patient, $meta['id']) ?? [];
                $secs  = $page['sections'] ?? [];
                $rows  = 0;
                foreach ($secs as $s) {
                    $rows += count($s['rows'] ?? []) + count($s['items'] ?? []) + count($s['stats'] ?? []) + count($s['lines'] ?? []);
                }
                $this->line(sprintf(
                    '%02d %-22s sections=%d rows=%d %s',
                    $meta['number'],
                    $meta['id'],
                    count($secs),
                    $rows,
                    $rows === 0 ? '<comment>empty</comment>' : ''
                ));
            }

            return self::SUCCESS;
        }

        $pageId = (string) $this->argument('page');

        if ($pageId === '') {
            $this->error('Give a page id, or use --all.');

            return self::FAILURE;
        }

        $page = $builder->page($patient, $pageId);

        if ($page === null) {
            $this->error("'{$pageId}' is not one of the thirty pages.");

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf('<info>%02d  %s</info>  — %s', $page['number'], $page['title'], $page['subtitle']));

        if ($page['sections'] === []) {
            $this->newLine();
            $this->comment('Nothing recorded on this page. That is a fact about the record, not a bug.');

            return self::SUCCESS;
        }

        foreach ($page['sections'] as $section) {
            $this->newLine();
            $this->line(sprintf(
                '<comment>%s</comment>  [%s / %s]',
                $section['title'],
                $section['layout'] ?? 'list',
                $section['accent'] ?? 'blue'
            ));

            $layout = $section['layout'] ?? 'list';

            if ($layout === 'table') {
                $this->table(
                    $section['columns'] ?? [],
                    array_map(
                        fn (array $row) => array_map(
                            fn (array $cell) => $cell['v'] . (isset($cell['pill']) ? " [{$cell['pill']}]" : ''),
                            $row
                        ),
                        $section['rows'] ?? []
                    )
                );
            } elseif ($layout === 'stats') {
                foreach ($section['stats'] ?? [] as $s) {
                    $this->line(sprintf('   %-6s %s', $s['value'], $s['label']));
                }
            } elseif ($layout === 'bullets') {
                foreach ($section['lines'] ?? [] as $l) {
                    $this->line('   • ' . $l);
                }
            } elseif ($layout === 'callout') {
                $this->line('   ' . ($section['body'] ?? ''));
            } else {
                foreach ($section['items'] ?? [] as $i) {
                    $this->line(sprintf('   %-28s %s%s', $i['label'], $i['value'], isset($i['tone']) ? " [{$i['tone']}]" : ''));
                }
            }
        }

        return self::SUCCESS;
    }

    private function patient(): ?Patient
    {
        $query = Patient::withoutGlobalScopes();

        if ($healthId = $this->option('health-id')) {
            return $query->where('health_id', $healthId)->first();
        }

        return $query->where('is_demo', true)->orderBy('created_at')->first();
    }
}
