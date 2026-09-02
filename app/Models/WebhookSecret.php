<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookSecret — Connect Suite / Webhooks
 *
 * HMAC signing secret for a WebhookEndpoint.
 * The raw secret is shown to the developer ONCE at creation time;
 * only the hash is stored here.
 *
 * Security: NEVER store plaintext secrets. NEVER log secret values.
 */
class WebhookSecret extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_endpoint_id',
        'secret_hash',       // HMAC secret, hashed
        'algorithm',         // sha256|sha512
        'is_active',
        'rotated_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'rotated_at' => 'datetime',
    ];

    protected $hidden = ['secret_hash']; // never expose in API responses

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function rotate(string $newSecretHash): self
    {
        $this->update(['is_active' => false, 'rotated_at' => now()]);
        return static::create([
            'webhook_endpoint_id' => $this->webhook_endpoint_id,
            'secret_hash'         => $newSecretHash,
            'algorithm'           => $this->algorithm,
            'is_active'           => true,
        ]);
    }
}
