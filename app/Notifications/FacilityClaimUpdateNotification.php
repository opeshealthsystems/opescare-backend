<?php

namespace App\Notifications;

use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

/**
 * Tells a claimant what happened to their facility claim (readiness OB-8):
 * `filed`, `approved` or `rejected`. Database (the in-app bell) and mail.
 *
 * Nothing secret is in it: never the SMS code, never the document. The
 * activation link on approval is a signed URL that only works for the
 * claimant's own signed-in account, so mailing it hands nothing to anyone
 * who intercepts it.
 */
class FacilityClaimUpdateNotification extends Notification
{
    public const FILED    = 'filed';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public function __construct(
        public readonly string $event,
        public readonly string $claimId,
        public readonly string $facilityName,
        public readonly ?string $activationUrl = null,
        public readonly ?string $notes = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string,mixed> */
    public function toDatabase(object $notifiable): array
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);
        $prev   = App::getLocale();
        App::setLocale($locale);

        try {
            return [
                'type'          => 'facility_claim_' . $this->event,
                'title'         => __('caremap_claim.notify.' . $this->event . '_title'),
                'message'       => __('caremap_claim.notify.' . $this->event . '_body', ['facility' => $this->facilityName]),
                'severity'      => $this->event === self::REJECTED ? 'medium' : 'info',
                'claim_id'      => $this->claimId,
                'facility_name' => $this->facilityName,
                'action_url'    => $this->activationUrl ?? route('portals.listing.claims'),
            ];
        } finally {
            App::setLocale($prev);
        }
    }

    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);
        $url    = $this->activationUrl ?? route('portals.listing.claims');

        return ComposedMail::inLocale($locale, function () use ($url) {
            $subject = __('caremap_claim.notify.' . $this->event . '_title');

            $content = EmailContent::make($subject, $subject)
                ->preheader(__('caremap_claim.notify.' . $this->event . '_body', ['facility' => $this->facilityName]))
                ->paragraph(__('caremap_claim.notify.' . $this->event . '_body', ['facility' => $this->facilityName]))
                ->fact(__('caremap_claim.col_facility'), $this->facilityName);

            if ($this->event === self::REJECTED && $this->notes) {
                $content->fact(__('caremap_claim.col_notes'), $this->notes);
            }

            if ($this->event === self::APPROVED) {
                $content->paragraph(__('caremap_claim.notify.approved_mfa'));
            }

            return $content
                ->button(
                    $this->event === self::APPROVED ? __('caremap_claim.btn_activate') : __('caremap_claim.my_claims_title'),
                    $url,
                    withLinkFallback: true
                )
                ->signature(__('team.mail.salutation'));
        })->forNotifiable($notifiable, $this);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
