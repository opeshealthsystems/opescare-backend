<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DemoNotificationsSeeder — realistic in-app notifications for the demo accounts,
 * so the notification bell + centre show real, categorised content in the demo.
 *
 * The read side (PatientNotificationController and the bell) keys strictly on
 * `recipient_user_id`, and treats a row as UNREAD when created_at > the
 * recipient's users.notifications_seen_at. So every row here is addressed to a
 * real demo user id (never null) and left naturally unread — we do NOT touch
 * users.notifications_seen_at.
 *
 * Column set mirrors App\Support\Notify (uuid, event_type, communication_type,
 * recipient_user_id, recipient_type, payload_json, priority, status,
 * requires_acknowledgement, …). We insert via the query builder rather than the
 * App\Models\NotificationEvent model on purpose: that model declares HasUuids,
 * but `notification_events.id` is a bigint sequence (nextval), so an Eloquent
 * save forces a UUID string into a bigint column and errors. Omitting `id` lets
 * the sequence assign it — the same approach the rest of this table uses.
 *
 * `communication_type` is the category the sound/badge layer keys off:
 * blood | pharmacy | appointment | account | general. A blood order and a
 * pharmacy order are included so those category/sound features are demonstrable.
 *
 * Idempotent: at the top we delete any prior rows this seeder created (scoped to
 * the demo recipients AND to our fixed event_type set), so re-running never piles
 * up. Safe to run repeatedly.
 */
class DemoNotificationsSeeder extends Seeder
{
    /** Every event_type this seeder owns — used for the idempotent cleanup. */
    private const EVENT_TYPES = [
        'blood_order_received',
        'pharmacy_order_received',
        'appointment_reminder',
        'appointment_confirmed',
        'low_stock_alert',
        'staff_joined',
        'health_tip',
        'lab_result_available',
        'prescription_issued',
    ];

    /** The patient logins that get the patient-side notifications below. */
    public const PATIENT_EMAILS = [
        'demo.patient@opescare.test',
        'demo.reviewer@opescare.test',
    ];

    public function run(): void
    {
        // Resolve the demo accounts by email; skip any that are missing so this
        // seeder is safe to run before/independently of the full demo estate.
        $emails = array_merge([
            'demo.receptionist@opescare.test',
            'demo.pharmacist@opescare.test',
            'demo.facility.admin@opescare.test',
        ], self::PATIENT_EMAILS);

        $userIdByEmail = [];
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $userIdByEmail[$email] = (string) $user->id;
            } else {
                $this->command?->warn("  skip {$email}: user not found");
            }
        }

        if (empty($userIdByEmail)) {
            $this->command?->warn('  no demo users found — nothing to seed. Run FinderDemoSeeder first.');

            return;
        }

        // Idempotency: clear only the rows this seeder is responsible for.
        DB::table('notification_events')
            ->whereIn('recipient_user_id', array_values($userIdByEmail))
            ->whereIn('event_type', self::EVENT_TYPES)
            ->delete();

        // Destinations come from route() rather than hand-typed paths: the
        // notification centre renders `url` as a real link, so a path that has
        // drifted seeds a 404 into every demo bell instead of failing here.
        // Second argument `false` keeps them relative — an absolute URL baked
        // from the seeding host would follow the row to production.
        $to = fn (string $name): string => route($name, [], false);

        // One flat list, staggered over the last ~2 days (most recent first) so
        // the ordering is visible in the centre. hoursAgo drives created_at.
        // [email, event_type, category, priority, requiresAck, hoursAgo, title, body, url]
        $events = [
            // Front desk / facility ops — blood request lands loudest (high +
            // requires acknowledgement) so the category + sound path is obvious.
            ['demo.receptionist@opescare.test', 'blood_order_received', 'blood', 'high', true, 2,
                'New blood request: O+ × 3',
                'Requested by Yaoundé General — urgent',
                $to('portals.staff.inventory.blood')],

            // Pharmacy reservation — the pharmacy category/sound demo.
            ['demo.pharmacist@opescare.test', 'pharmacy_order_received', 'pharmacy', 'high', false, 5,
                'New medicine reservation: Paracetamol × 2',
                'Reserved for pickup by a patient via the Finder.',
                $to('portals.pharmacy.stock')],

            // Pharmacy low-stock nudge — a plain general note.
            ['demo.pharmacist@opescare.test', 'low_stock_alert', 'general', 'normal', false, 9,
                'Low stock: Amoxicillin 500mg',
                '3 packs remaining — reorder soon to avoid a stock-out.',
                $to('portals.pharmacy.stock')],

            // Front desk appointment reminder.
            ['demo.receptionist@opescare.test', 'appointment_reminder', 'appointment', 'normal', false, 14,
                'Upcoming appointment at 14:00',
                'Aïssatou N. — General consultation, Room 3.',
                $to('portals.staff.appointments')],

            // Facility admin account notice.
            ['demo.facility.admin@opescare.test', 'staff_joined', 'account', 'normal', false, 20,
                'A new staff member joined your facility',
                'Dr. Amara Diallo now has access to the facility desk.',
                $to('portals.facility.team')],

        ];

        // Patient-side notifications — the same set for every demo patient
        // login, so each reviewer sees a populated bell. Dates are relative
        // to now, like the story they belong to (DEMO_DATA_BRIEF.md).
        $reviewDate = now()->addWeeks(3)->format('j M');
        foreach (self::PATIENT_EMAILS as $patientEmail) {
            // Each one points at the page that actually holds the thing it is
            // announcing. The health tip announces nothing openable, so it
            // carries no url and the centre renders it as plain text.
            $events[] = [$patientEmail, 'lab_result_available', 'general', 'high', false, 6,
                'Lab result available — Fasting Blood Glucose',
                '5.1 mmol/L, within range. Open Lab Results to see the full report.',
                $to('portals.patient.timeline')];
            $events[] = [$patientEmail, 'prescription_issued', 'pharmacy', 'normal', false, 26,
                'Prescription issued — Metformin, Amlodipine',
                'Your quarterly repeat is ready to collect. Show the prescription code at the pharmacy.',
                $to('portals.patient.prescriptions')];
            $events[] = [$patientEmail, 'appointment_confirmed', 'appointment', 'normal', false, 30,
                'Appointment confirmed',
                "Your quarterly review on {$reviewDate} at 09:30 is confirmed.",
                $to('portals.patient.appointments')];
            $events[] = [$patientEmail, 'health_tip', 'general', 'normal', false, 44,
                'Health tip: stay hydrated',
                'Drink water regularly, especially during the dry season.',
                ''];
        }

        $seeded = 0;
        foreach ($events as [$email, $eventType, $category, $priority, $requiresAck, $hoursAgo, $title, $body, $url]) {
            if (! isset($userIdByEmail[$email])) {
                continue; // recipient not present in this estate
            }

            $when = now()->subHours($hoursAgo);

            DB::table('notification_events')->insert([
                // `id` intentionally omitted — bigint sequence assigns it.
                'uuid'                     => (string) Str::uuid(),
                'event_type'               => $eventType,
                'communication_type'       => $category,
                'actor_id'                 => null,
                'recipient_user_id'        => $userIdByEmail[$email],
                'recipient_type'           => 'user',
                'related_resource_type'    => null,
                'related_resource_id'      => null,
                'payload_json'             => json_encode([
                    'title' => $title,
                    'body'  => $body,
                    'url'   => $url,
                ]),
                'priority'                 => $priority,
                'status'                   => 'routed',
                'requires_acknowledgement' => $requiresAck,
                'acknowledgement_status'   => $requiresAck ? 'pending' : 'not_required',
                'created_at'               => $when,
                'updated_at'               => $when,
            ]);

            $seeded++;
        }

        $this->command?->info("  seeded {$seeded} demo notification_events for " . count($userIdByEmail) . ' demo account(s)');
    }
}
