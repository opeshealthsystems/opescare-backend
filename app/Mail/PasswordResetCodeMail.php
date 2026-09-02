<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $recipientEmail,
        public readonly int $expiryMinutes = 15
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your OpesCare Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
            with: [
                'code'          => $this->code,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }
}
