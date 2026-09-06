<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message handed to Expo — and, later, what Expo said became of it.
 *
 * Written by ExpoPushService at send time (status `pending` with a ticket id,
 * or `error` straight away when Expo refused the message in the ticket);
 * resolved by `push:check-receipts`. Never carries the notification's copy:
 * `type` is the data.type, and `error_details` is Expo's own diagnostic.
 *
 * @property string      $id
 * @property string|null $patient_id
 * @property string|null $push_device_token_id
 * @property string|null $platform
 * @property string|null $type
 * @property string|null $ticket_id
 * @property string      $status
 * @property string|null $error_code
 * @property array|null  $error_details
 * @property \Illuminate\Support\Carbon      $sent_at
 * @property \Illuminate\Support\Carbon|null $receipt_checked_at
 */
class PushDelivery extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_OK = 'ok';

    public const STATUS_ERROR = 'error';

    /** Expo's "this handset uninstalled the app / revoked notifications" — the row's token is retired. */
    public const ERROR_DEVICE_NOT_REGISTERED = 'DeviceNotRegistered';

    /** The whole request to Expo failed (network, non-2xx); no ticket was ever issued. */
    public const ERROR_TRANSPORT = 'TransportError';

    /** The ticket aged past Expo's receipt window without a receipt ever appearing. */
    public const ERROR_RECEIPT_UNAVAILABLE = 'ReceiptUnavailable';

    protected $fillable = [
        'patient_id',
        'push_device_token_id',
        'platform',
        'type',
        'ticket_id',
        'status',
        'error_code',
        'error_details',
        'sent_at',
        'receipt_checked_at',
    ];

    protected $casts = [
        'error_details'      => 'array',
        'sent_at'            => 'datetime',
        'receipt_checked_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(PushDeviceToken::class, 'push_device_token_id');
    }

    /** Rows Expo issued a ticket for and has not yet reported on. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)->whereNotNull('ticket_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
