<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Btree indexes for the hottest read paths, found by EXPLAIN at synthetic
 * national volume (see docs/certification/SCALE_READINESS.md).
 *
 * Non-transactional so each index builds CONCURRENTLY on a live database.
 * IF NOT EXISTS makes a half-finished run safe to re-run (an interrupted
 * CONCURRENTLY build leaves an INVALID index; drop it and re-run).
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /** name => "table (cols) [WHERE ...]" */
    private const INDEXES = [
        // Geo bounding-box prefilter for every finder.
        'care_facilities_lat_lng_idx'          => 'care_facilities (latitude, longitude)',
        'care_facilities_status_type_idx'      => 'care_facilities (listing_status, facility_type)',
        // Health ID resolve by alias had no index at all: every alias lookup
        // was a full scan of health_id_aliases.
        'health_id_aliases_value_status_idx'   => 'health_id_aliases (alias_value, status)',
        // Consent gate / grant lists: patient + facility + live status.
        'consent_grants_patient_fac_status_idx' => 'consent_grants (patient_id, facility_id, status, expires_at)',
        // Access log "who saw my record", newest first.
        'access_logs_patient_created_idx'      => 'access_logs (patient_id, created_at)',
        // Facility day sheet.
        'appointments_facility_scheduled_idx'  => 'appointments (facility_id, scheduled_at)',
        // Queue desk: a facility's live tickets.
        'queue_tickets_facility_status_idx'    => 'queue_tickets (facility_id, status, created_at)',
        // Bell: unread notifications for one user.
        'notifications_unread_idx'             => 'notifications (notifiable_type, notifiable_id, created_at) WHERE read_at IS NULL',
        'notification_events_recipient_idx'    => 'notification_events (recipient_user_id, created_at)',
        // Chart: a patient's encounters (no patient index existed).
        'encounters_patient_started_idx'       => 'encounters (patient_id, started_at)',
        'encounters_facility_idx'              => 'encounters (facility_id)',
        // Finder joins from stock to facility.
        'blood_availability_facility_idx'      => 'blood_availability (facility_id)',
        'psa_facility_idx'                     => 'pharmacy_stock_availability (facility_id)',
        'mps_last_reported_idx'                => 'medicine_pharmacy_stocks (last_reported_at)',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::INDEXES as $name => $def) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON {$def}");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }
};
