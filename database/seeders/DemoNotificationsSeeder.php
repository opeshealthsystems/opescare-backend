<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds demo notification_events for the messaging/alerts dashboard.
 * Idempotent – inserts only if the table is empty.
 */
class DemoNotificationsSeeder extends Seeder
{
    private const FAC  = '00000000-0000-0000-0000-100000000001';
    private const PAT1 = '00000000-0000-0000-0000-300000000001';
    private const DOC  = '00000000-0000-0000-0000-200000000001';

    public function run(): void
    {
        if (DB::table('notification_events')->count() > 0) {
            return;
        }

        $events = [
            ['event_type' => 'appointment_reminder',
             'subject_type' => 'Patient', 'subject_id' => self::PAT1,
             'actor_type' => 'system', 'actor_id' => null,
             'facility_id' => self::FAC,
             'channel' => 'sms',
             'status' => 'delivered',
             'payload' => ['message' => 'Reminder: your appointment tomorrow at 10:00 AM.'],
             'created_at' => now()->subHours(18)],
            ['event_type' => 'lab_result_ready',
             'subject_type' => 'Patient', 'subject_id' => self::PAT1,
             'actor_type' => 'User', 'actor_id' => self::DOC,
             'facility_id' => self::FAC,
             'channel' => 'in_app',
             'status' => 'delivered',
             'payload' => ['message' => 'Your lab results are now available.'],
             'created_at' => now()->subHours(5)],
            ['event_type' => 'prescription_ready',
             'subject_type' => 'Patient', 'subject_id' => self::PAT1,
             'actor_type' => 'system', 'actor_id' => null,
             'facility_id' => self::FAC,
             'channel' => 'sms',
             'status' => 'pending',
             'payload' => ['message' => 'Your prescription is ready for collection at the pharmacy.'],
             'created_at' => now()->subMinutes(20)],
        ];

        foreach ($events as $e) {
            DB::table('notification_events')->insert([
                'uuid'                  => Str::uuid()->toString(),
                'event_type'            => $e['event_type'],
                'communication_type'    => $e['channel'],
                'actor_id'              => $e['actor_id'],
                'recipient_user_id'     => null,
                'recipient_type'        => 'patient',
                'related_resource_type' => $e['subject_type'],
                'related_resource_id'   => $e['subject_id'],
                'payload_json'          => json_encode($e['payload']),
                'priority'              => 'normal',
                'status'                => $e['status'],
                'requires_acknowledgement' => false,
                'acknowledgement_status' => 'not_required',
                'created_at'            => $e['created_at'],
                'updated_at'            => $e['created_at'],
            ]);
        }
    }
}
