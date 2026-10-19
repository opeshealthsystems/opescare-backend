<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Support\PatientPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * One patient push, sent off the request path.
 *
 * PatientPush::send() makes a blocking HTTPS call to Expo (15 s timeout) and
 * writes push_deliveries rows. Doing that inside a clinician's "release
 * result" or "approve consent" request made every such click wait on a third
 * party. PatientPush::queue() dispatches this instead; behaviour is identical
 * (same preference gate, same payload, same delivery ledger) — only the
 * timing moves. Dispatched after commit so a rolled-back write never pushes.
 *
 * Not retried: Expo tickets are not idempotent, and a duplicated clinical
 * push is worse than a missed one (the in-app notification row is the record
 * of truth; the push is only a doorbell).
 */
class SendPatientPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public Patient $patient,
        public string $type,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
        $this->afterCommit();
        $this->onQueue('default');
    }

    public function handle(): void
    {
        PatientPush::send($this->patient, $this->type, $this->title, $this->body, $this->data);
    }
}
