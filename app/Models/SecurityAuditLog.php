<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'audit_type', 'vendor_name', 'audit_date', 'scope',
        'findings_count', 'critical_count', 'high_count', 'medium_count', 'low_count',
        'status', 'report_url', 'next_assessment', 'notes',
    ];

    protected $casts = [
        'audit_date'      => 'date',
        'next_assessment' => 'date',
    ];

    /** Append-only audit trail — no updates permitted. Create a new entry to correct. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('SecurityAuditLog is an append-only record. Create a new entry to make corrections.');
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException('SecurityAuditLog is append-only and cannot be saved after creation.');
        }
        return parent::save($options);
    }
}
