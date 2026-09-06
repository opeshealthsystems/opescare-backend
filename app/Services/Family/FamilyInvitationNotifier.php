<?php

namespace App\Services\Family;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use App\Support\PatientPush;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * In-app bell entries for the invitation round-trip.
 *
 * The mail notification (FamilyInviteNotification) only reaches a dependent
 * who has a users row AND a working mailbox — an elder with a Health ID and
 * nothing else could never learn they were invited. The app's feed reads
 * Laravel's `notifications` table for rows addressed to the PATIENT (morph
 * alias `patient`) and to the patient's linked USER, so one row here is enough
 * for the bell to light up whether or not a login exists yet. Payload shape is
 * the one every notification class writes: type · title · message · severity
 * · action_url · action_label (see DemoPatientStorySeeder::seedNotifications).
 *
 * Best-effort: the link is the record of truth; a bell that fails to ring is
 * logged, never surfaced.
 */
final class FamilyInvitationNotifier
{
    public const TYPE_INVITED  = 'family_invitation';
    public const TYPE_ACCEPTED = 'family_invitation_accepted';
    public const TYPE_DECLINED = 'family_invitation_declined';

    /** Tell the invited patient a guardian wants to be linked to their record. */
    public static function invited(FamilyLink $link, Patient $dependent): void
    {
        $guardianName = $link->guardianUser?->name ?: __('family_api.someone');

        self::write($dependent, self::TYPE_INVITED, [
            'title'          => __('family_api.notification_invite_title', ['guardian' => $guardianName]),
            'message'        => __('family_api.notification_invite_message', [
                'guardian'     => $guardianName,
                'relationship' => __('family_api.relationship.' . $link->relationship),
                'access'       => __('family_api.access.' . $link->access_level),
            ]),
            'family_link_id' => $link->id,
            'guardian_name'  => $guardianName,
            'relationship'   => $link->relationship,
            'access_level'   => $link->access_level,
            'expires_at'     => $link->invite_expires_at?->toIso8601String(),
            'action_url'     => '/family',
            'action_label'   => __('family_api.notification_invite_action'),
        ]);

        // And the phone: the same "may this person open my record?" decision a
        // facility's consent request is, so it rides the `consent` channel and
        // its chime. PatientPush never throws and is a no-op when push is off.
        $locale = PatientPush::localeFor($dependent);

        PatientPush::send(
            $dependent,
            self::TYPE_INVITED,
            __('push.family_invitation.title', [], $locale),
            __('push.family_invitation.body', ['guardian' => $guardianName], $locale),
            [
                'family_link_id' => $link->id,
                'guardian_name'  => $guardianName,
                'action_url'     => '/family',
            ],
        );
    }

    /** Tell the guardian (their user account) how the dependent answered. */
    public static function answered(FamilyLink $link, Patient $dependent, bool $accepted): void
    {
        $guardian = $link->guardianUser;
        if (! $guardian) {
            return;
        }

        $name = trim($dependent->first_name . ' ' . mb_substr((string) $dependent->last_name, 0, 1) . '.');
        $key  = $accepted ? 'accepted' : 'declined';

        self::write($guardian, $accepted ? self::TYPE_ACCEPTED : self::TYPE_DECLINED, [
            'title'          => __("family_api.notification_{$key}_title", ['name' => $name]),
            'message'        => __("family_api.notification_{$key}_message", ['name' => $name]),
            'family_link_id' => $link->id,
            'action_url'     => '/family',
            'action_label'   => __('family_api.notification_answer_action'),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function write(Patient|User $notifiable, string $type, array $data): void
    {
        try {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => $type,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id'   => $notifiable->getKey(),
                'data'            => json_encode(['type' => $type, 'severity' => 'normal'] + $data, JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Family notification was not written.', [
                'type'  => $type,
                'to'    => $notifiable->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
