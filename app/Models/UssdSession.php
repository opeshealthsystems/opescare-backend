<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UssdSession extends Model {
    use HasUuids;

    protected $fillable = [
        'session_id','phone_number','service_code','patient_id',
        'current_menu','menu_data','initiated_at','last_active_at',
    ];

    protected $casts = [
        'menu_data'      => 'array',
        'initiated_at'   => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Patient::class);
    }
}
