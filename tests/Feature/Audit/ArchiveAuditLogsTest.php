<?php

namespace Tests\Feature\Audit;

use App\Models\MedicalIdAccessEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GAP-009 (audit half) — immutable, tamper-evident audit archive.
 *
 * Archives must be written to the configured immutable disk as write-once
 * objects, each with a SHA-256 digest sidecar, and the hot table purged only
 * after a successful archive write.
 */
class ArchiveAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('audit.archive_disk', 'local');
    }

    private function seedOldEvents(int $n, string $ym): void
    {
        $when = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->addDays(3);
        for ($i = 0; $i < $n; $i++) {
            $event = MedicalIdAccessEvent::create([
                'access_type' => 'view_profile',
                'result'      => 'success',
                'purpose'     => 'treatment',
            ]);
            // created_at is timestamp-managed; force it old via a raw update.
            MedicalIdAccessEvent::where($event->getKeyName(), $event->getKey())
                ->update(['created_at' => $when]);
        }
    }

    public function test_archives_to_write_once_object_with_digest_and_purges_hot_table(): void
    {
        $ym = now()->subMonths(18)->format('Y-m');
        $this->seedOldEvents(3, $ym);

        $this->artisan('health-id:archive-audit-logs', ['--months' => 12])
            ->assertSuccessful();

        $disk        = Storage::disk('local');
        $archivePath = "audit-archive/{$ym}.jsonl";
        $digestPath  = "audit-archive/{$ym}.jsonl.sha256";

        // Archive object + integrity digest both written.
        $this->assertTrue($disk->exists($archivePath), 'archive file missing');
        $this->assertTrue($disk->exists($digestPath), 'sha256 digest sidecar missing');

        // Digest matches the archive content exactly (tamper-evident).
        $content  = $disk->get($archivePath);
        $expected = hash('sha256', $content);
        $this->assertSame($expected, trim($disk->get($digestPath)));

        // Three JSONL lines archived.
        $this->assertCount(3, array_filter(explode("\n", trim($content))));

        // Hot table purged after successful archive.
        $this->assertSame(0, MedicalIdAccessEvent::count());
    }

    public function test_dry_run_writes_nothing_and_keeps_rows(): void
    {
        $ym = now()->subMonths(18)->format('Y-m');
        $this->seedOldEvents(2, $ym);

        $this->artisan('health-id:archive-audit-logs', ['--months' => 12, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertFalse(Storage::disk('local')->exists("audit-archive/{$ym}.jsonl"));
        $this->assertSame(2, MedicalIdAccessEvent::count());
    }
}
