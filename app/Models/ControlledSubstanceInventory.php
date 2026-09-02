<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledSubstanceInventory extends Model {
    use HasUuids, HasFactory;

    protected $fillable = [
        'facility_id','drug_code','drug_name','schedule','current_balance',
        'unit','last_reconciled_at','last_reconciled_by',
    ];

    protected $casts = [
        'current_balance'    => 'decimal:2',
        'last_reconciled_at' => 'datetime',
    ];

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function lastReconciledBy(): BelongsTo { return $this->belongsTo(User::class, 'last_reconciled_by'); }
}
