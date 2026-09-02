<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntegrationClient extends Model
{
    use HasUuids;
    use HasFactory;

    protected $fillable = [
        'client_id', 'client_secret', 'client_secret_argon', 'secret_upgraded_at',
        'facility_id', 'scopes', 'status', 'environment',
        'name', 'description', 'contact_email', 'created_by',
        'approved_at', 'approved_by', 'last_used_at', 'request_count',
        'api_plan_key',
    ];

    protected $casts = [
        'scopes'       => 'array',
        'approved_at'  => 'datetime',
        'last_used_at' => 'datetime',
        'request_count'=> 'integer',
    ];

    public function webhookSubscriptions()
    {
        return $this->hasMany(WebhookSubscription::class, 'client_id', 'client_id');
    }

    public function sdkTokens()
    {
        return $this->hasMany(SdkToken::class, 'client_id', 'client_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** The metered/billed plan this client is on (defaults to sandbox). */
    public function apiPlan(): ?ApiPlan
    {
        return ApiPlan::forKey($this->api_plan_key ?: 'sandbox');
    }

    /** Requests this client has made in the current billing month. */
    public function monthlyUsageCount(): int
    {
        return ApiUsageLog::where('integration_client_id', $this->client_id)
            ->where('logged_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending' || is_null($this->approved_at);
    }

    public static function availableScopes(): array
    {
        return [
            'health_id:verify'       => 'Health ID Verification',
            'patient:read'           => 'Read Patient Summary',
            'encounter:push'         => 'Push Encounter Data',
            'lab:push'               => 'Push Lab Results',
            'prescription:push'      => 'Push Prescriptions',
            'document:verify'        => 'Document Verification',
            'facility:sync'          => 'Facility Data Sync',
            'inventory:read'         => 'Read Inventory (Pharmacy/Blood)',
            'appointment:sync'       => 'Appointment Sync',
            'insurance:eligibility'  => 'Insurance Eligibility Check',
            'webhook:manage'         => 'Manage Webhook Subscriptions',
            'public_health:report'   => 'Public Health Reporting',
        ];
    }
}
