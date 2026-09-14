<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * No two PHP files under app/ may share a basename.
 *
 * Two classes called NotificationEvent in different namespaces once wrote to
 * the wrong table silently, because an IDE import picked the other one. Six
 * such pairs were resolved on 2026-09-14 (CarePlanService,
 * DocumentVerificationService, NotificationEvent, PaymentMethod,
 * ProviderPerformanceService, WaitlistService). A name that exists twice is a
 * name that will be imported wrongly; this test keeps the tree unambiguous.
 *
 * If it fails: rename the newer class to say what it actually is (e.g.
 * AppointmentWaitlistService, OfficialDocumentTokenService), never delete a
 * class that has callers to get past it.
 */
class NoDuplicateClassBasenamesTest extends TestCase
{
    public function test_no_php_basename_under_app_is_duplicated(): void
    {
        $root = base_path('app');
        $seen = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $seen[$file->getFilename()][] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        }

        $duplicates = array_filter($seen, fn (array $paths) => count($paths) > 1);
        ksort($duplicates);

        $report = [];
        foreach ($duplicates as $name => $paths) {
            $report[] = $name . ":\n    " . implode("\n    ", $paths);
        }

        $this->assertSame(
            [],
            $report,
            "Duplicate class basenames under app/ — rename one of each pair to say what it is:\n  "
            . implode("\n  ", $report)
        );
    }
}
