<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of every status move a charge or an invoice made, and
 * what caused it.
 *
 * This is the answer to "the patient says they paid". Without it, a disputed
 * payment is one person's word against a status column that has already been
 * overwritten.
 *
 * ── What cannot be in here ──────────────────────────────────────────────────
 * Not a design intention — a database constraint. `note`, `provider_status_code`
 * and `failure_code` each carry a CHECK rejecting any run of six or more
 * digits, which is what a phone number looks like and what no legitimate status
 * code contains. There is no JSON column, no `payload`, no `raw_response`:
 * provider bodies carry the payer's `partyId`, and a column that could hold one
 * is a column that eventually does.
 *
 * ── Why there is no `updated_at` ────────────────────────────────────────────
 * A Postgres trigger rejects UPDATE and DELETE on this table outright. History
 * that can be corrected is not evidence.
 */
class MobilePaymentEvent extends Model
{
    use HasUuids;

    protected $table = 'mobile_payment_events';

    /** Written once, at insert. Nothing edits a row here — the DB forbids it. */
    public $timestamps = false;

    protected $fillable = [
        'charge_id',
        'invoice_id',
        'source',
        'from_status',
        'to_status',
        'provider_status_code',
        'failure_code',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function charge(): BelongsTo
    {
        return $this->belongsTo(MobilePaymentCharge::class, 'charge_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(MobileInvoice::class, 'invoice_id');
    }
}
