<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PharmacyRoute extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'pharmacy_name', 'pharmacy_type',
        'contact_email', 'contact_phone', 'routing_method',
        'api_endpoint', 'api_key_encrypted', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
    protected $hidden = ['api_key_encrypted'];

    public function facility()      { return $this->belongsTo(Facility::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
}
