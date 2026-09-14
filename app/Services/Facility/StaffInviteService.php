<?php

namespace App\Services\Facility;

use App\Models\Facility;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Notifications\FacilityStaffInviteNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * The ONE way a staff invite is minted and mailed.
 *
 * FacilityStaffController (the team page) and LeadConversionService (a
 * platform admin turning a lead into a tenant) both come through here, so
 * the token, the row, the mail and its wording are the same whichever desk
 * issued the invite. The allow-list is NOT applied here — each caller
 * validates the role against the list that is right for its actor
 * (INVITABLE_ROLES for a facility admin, plus PLATFORM_ONLY_INVITABLE_ROLES
 * for a platform admin converting a lead) BEFORE calling issue().
 */
class StaffInviteService
{
    /**
     * Create the invite row. The raw token is returned ONCE and never stored;
     * the row keeps its sha256.
     *
     * @return array{invite: FacilityStaffInvite, rawToken: string}
     */
    public function issue(string $facilityId, Role $role, string $email, ?string $name, ?string $inviterId): array
    {
        $rawToken = FacilityStaffInvite::generateToken();

        $invite = FacilityStaffInvite::create([
            'facility_id' => $facilityId,
            'role_id'     => $role->id,
            'email'       => $email,
            'name'        => $name,
            'token_hash'  => FacilityStaffInvite::hashToken($rawToken),
            'invited_by'  => $inviterId,
            'expires_at'  => now()->addDays(FacilityStaffInvite::TTL_DAYS),
        ]);

        return ['invite' => $invite, 'rawToken' => $rawToken];
    }

    /**
     * Send the invitation. The truth about what "sent" means is in
     * FacilityStaffController's docblock: the most this can report is that
     * the mailer accepted the message. Never throws — a dead mail server
     * must not cost the facility its invite; the link is shown either way.
     *
     * @return array{ok: bool, message: string}
     */
    public function mail(FacilityStaffInvite $invite, string $rawToken, ?string $inviterName): array
    {
        try {
            $facilityName = Facility::query()->whereKey($invite->facility_id)->value('name')
                ?? Facility::withoutGlobalScopes()->whereKey($invite->facility_id)->value('name')
                ?? '';

            $roleName = $invite->role?->name ?? Role::whereKey($invite->role_id)->value('name');

            Notification::route('mail', $invite->email)->notify(
                new FacilityStaffInviteNotification(
                    invite:       $invite,
                    rawToken:     $rawToken,
                    facilityName: (string) $facilityName,
                    roleLabel:    self::roleLabel($roleName),
                    inviterName:  $inviterName,
                )
            );

            Log::info('facility_staff_invite_mail_accepted', [
                'invite_id' => $invite->id,
                'mailer'    => config('mail.default'),
            ]);

            return ['ok' => true, 'message' => __('team.invite_mail_accepted', ['email' => $invite->email])];
        } catch (\Throwable $e) {
            Log::warning('facility_staff_invite_mail_failed', [
                'invite_id' => $invite->id,
                'mailer'    => config('mail.default'),
                'error'     => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => __('team.invite_mail_failed', ['email' => $invite->email])];
        }
    }

    /** Bilingual label for a role name, falling back to the raw name. */
    public static function roleLabel(?string $roleName): string
    {
        if ($roleName === null || $roleName === '') {
            return '';
        }

        $label = __('team.roles.' . $roleName);

        return is_string($label) && $label !== 'team.roles.' . $roleName ? $label : $roleName;
    }
}
