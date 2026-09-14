<?php

namespace App\Mail;

use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one email design every OpesCare message is built on.
 *
 * A subclass describes WHAT it says (compose() returns an EmailContent); this
 * class decides HOW it is sent: the brand HTML layout, a plain-text
 * alternative rendered from the same data (so every message is multipart),
 * the subject taken from that same content, and the recipient's language.
 *
 * compose() runs inside the mailable's locale — Mailable::send() and
 * Mailable::render() both wrap the build in withLocale($this->locale) — so
 * every __() call in a subclass resolves in the language the RECIPIENT reads,
 * not the one the sending request happened to be in. When no locale was set
 * explicitly, it is resolved from the first "to" address just before the
 * build (RecipientLocale::forEmail).
 */
abstract class BrandedMailable extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailContent $composed = null;

    private ?string $composedLocale = null;

    /** Describe the message. Called inside the recipient's locale. */
    abstract protected function compose(): EmailContent;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->composed()->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.layout',
            text: 'emails.layout-text',
            with: ['email' => $this->composed()],
        );
    }

    public function send($mailer)
    {
        $this->resolveLocale();

        return parent::send($mailer);
    }

    public function render()
    {
        $this->resolveLocale();

        return parent::render();
    }

    /** The composed content, built once per locale (envelope() and content() both need it). */
    protected function composed(): EmailContent
    {
        $locale = app()->getLocale();

        if ($this->composed === null || $this->composedLocale !== $locale) {
            $this->composed       = $this->compose();
            $this->composedLocale = $locale;
        }

        return $this->composed;
    }

    protected function resolveLocale(): void
    {
        if ($this->locale !== null && $this->locale !== '') {
            return;
        }

        $this->locale = RecipientLocale::forEmail($this->to[0]['address'] ?? null);
    }
}
