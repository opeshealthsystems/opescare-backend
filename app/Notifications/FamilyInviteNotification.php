<?php
namespace App\Notifications;

use App\Models\FamilyLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Notifications\Notification;

class FamilyInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly FamilyLink $link,
        public readonly string $rawToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);

        return ComposedMail::inLocale($locale, function () {
            $guardian  = trim((string) ($this->link->guardianUser?->name ?? $this->link->guardianUser?->email ?? ''));
            $guardian  = $guardian !== '' ? $guardian : __('mail.family_invite.someone');
            $patient   = trim((string) ($this->link->dependentPatient?->first_name ?? ''));
            $acceptUrl = route('portals.patient.family.invite.accept', $this->rawToken);
            $ttlHours  = (int) config('family.invite_ttl_hours', 48);

            $relationship = (string) $this->link->relationship;
            $relationshipLabel = \Illuminate\Support\Facades\Lang::has("family.relationship.{$relationship}")
                ? __("family.relationship.{$relationship}")
                : ucfirst(str_replace('_', ' ', $relationship));

            return EmailContent::make(__('mail.family_invite.subject'), __('mail.family_invite.title'))
                ->preheader(__('mail.family_invite.preheader', ['guardian' => $guardian]))
                ->eyebrow(__('mail.family_invite.eyebrow'))
                ->greeting(__('mail.common.greeting'))
                ->paragraph($patient !== ''
                    ? __('mail.family_invite.intro', ['guardian' => $guardian, 'patient' => $patient])
                    : __('mail.family_invite.intro_self', ['guardian' => $guardian]))
                ->fact(__('mail.family_invite.fact_access'), $this->link->access_level === 'full'
                    ? __('mail.family_invite.access_full')
                    : __('mail.family_invite.access_read_only'))
                ->fact(__('mail.family_invite.fact_relationship'), $relationshipLabel)
                ->button(__('mail.family_invite.action'), $acceptUrl)
                ->footnote(__('mail.family_invite.expiry', ['hours' => $ttlHours]))
                ->footnote(__('mail.family_invite.ignore'))
                ->signature(__('mail.chrome.signature'));
        })->forNotifiable($notifiable, $this);
    }

    public function toArray(object $notifiable): array
    {
        return ['invite_token_hint' => substr($this->rawToken, 0, 8) . '...'];
    }
}
