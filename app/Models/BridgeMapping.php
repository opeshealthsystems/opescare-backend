<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeMapping — Bridge Agent
 *
 * Field-level mapping rule for a BridgeConnector.
 * Maps a source field (from the external system) to a target field
 * in OpesCare's internal data model.
 */
class BridgeMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_connector_id',
        'source_field',
        'target_field',
        'transform',         // none|trim|date_format|lookup
        'transform_params',
        'is_required',
    ];

    protected $casts = [
        'transform_params' => 'array',
        'is_required'      => 'boolean',
    ];

    public function bridgeConnector(): BelongsTo
    {
        return $this->belongsTo(BridgeConnector::class);
    }

    public function scopeForConnector($query, string $connectorId)
    {
        return $query->where('bridge_connector_id', $connectorId);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
