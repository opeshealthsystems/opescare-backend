<?php

namespace App\Notifications;

use App\Models\FacilityStaffInvite;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Notifications\Notification;

/**
 * The invitation email for a facility staff invite.
 *
 * Deliberately NOT `implements ShouldQueue`, which is the one place this class
 * departs from PasswordResetLinkNotification. That notification is queued to
 * keep POST /forgot-password from becoming an enumeration oracle with a
 * stopwatch — there is no such concern here, and there is a concern pulling the
 * other way: the issuing page has to tell the truth about what happened to the
 * mail. Handing the send to a queue means the controller learns nothing, so the
 * only honest thing it could say is "queued", which is what "the email was
 * sent" already means to nobody. Sending inline lets FacilityStaffController
 * catch the transport failure and say "email could not be sent, share the link
 * yourself" instead of implying a delivery that never happened.
 *
 * Even then the strongest claim available is that the local MTA accepted the
 * message. opescare.cloud publishes a nullMX and the host relays direct to MX
 * from a VPS address, so a bounce has nowhere to land and a silent spam-folder
 * filing is the normal case. THE LINK REMAINS THE CHANNEL OF RECORD. This mail
 * is a convenience; the copyable link on /facility/team is the mechanism.
 *
 * Contains no patient data — facility, role, inviter, expiry, link. That is the
 * whole payload, and it is what an invitee needs to decide whether the mail is
 * genuine.
 */
class FacilityStaffInviteNotification extends Notification
{
    public function __construct(
        public readonly FacilityStaffInvite $invite,
        public readonly string $rawToken,
        public readonly string $facilityName,
        public readonly string $roleLabel,
        public readonly ?string $inviterName,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): ComposedMail
    {
        // The raw token exists in this object and in the mail body only; the row
        // keeps a sha256 of it. Same contract as the password-reset link.
        $url = route('invite.accept', $this->rawToken);

        // An invitee has no account yet, so there is no stored preference to
        // read: the language is the one the inviter chose (->locale()) or the
        // one the issuing page was in.
        $locale = RecipientLocale::for($notifiable, $this->locale);

        return ComposedMail::inLocale($locale, function () use ($url, $locale) {
            $content = EmailContent::make(
                __('team.mail.subject', ['facility' => $this->facilityName]),
                __('team.mail.subject', ['facility' => $this->facilityName]),
            )
                ->preheader(__('mail.staff_invite.preheader', ['facility' => $this->facilityName]))
                ->eyebrow(__('mail.staff_invite.eyebrow'))
                ->greeting(__('team.mail.greeting'))
                ->paragraph(__('team.mail.intro', [
                    'facility' => $this->facilityName,
                    'role'     => $this->roleLabel,
                ]))
                ->fact(__('mail.staff_invite.facility'), $this->facilityName)
                ->fact(__('mail.staff_invite.role'), $this->roleLabel);

            if ($this->inviterName !== null && $this->inviterName !== '') {
                $content->paragraph(__('team.mail.invited_by', ['name' => $this->inviterName]));
            }

            $expires = $this->invite->expires_at?->copy()->locale($locale)->isoFormat('LLL') ?? '';

            return $content
                ->button(__('team.mail.action'), $url, withLinkFallback: true)
                ->footnote(__('team.mail.expiry', ['date' => $expires]))
                ->footnote(__('team.mail.ignore'))
                ->signature(__('team.mail.salutation'));
        })->forNotifiable($notifiable, $this);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        // Never the token. This array is what the database channel would persist.
        return ['invite_id' => $this->invite->id];
    }
}
