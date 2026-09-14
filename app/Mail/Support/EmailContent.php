<?php

namespace App\Mail\Support;

/**
 * Everything one OpesCare email says, as plain data.
 *
 * One object feeds BOTH parts of every message: the HTML layout
 * (resources/views/emails/layout.blade.php) and the plain-text alternative
 * (resources/views/emails/layout-text.blade.php) read the same fields, so the
 * text part can never drift from the designed one. It holds strings and
 * arrays only — no closures, no models — so a queued Mailable serialises it
 * without ceremony.
 *
 * Build it INSIDE the recipient's locale (see ComposedMail::inLocale and
 * BrandedMailable::compose): every string passed in is expected to already be
 * translated. The object itself never calls __().
 */
final class EmailContent
{
    /** Subject line. Must never carry a Health ID, a diagnosis, a result or a medication. */
    public string $subject = '';

    /** Hidden preview line shown by mail clients next to the subject. */
    public string $preheader = '';

    /** Small label above the title (e.g. "Security notice"). */
    public ?string $eyebrow = null;

    /** 'default' or 'urgent' — urgent paints the eyebrow in the warning colour. */
    public string $tone = 'default';

    /** Page title (H1). */
    public string $title = '';

    /** "Hello Amina," */
    public ?string $greeting = null;

    /** Body paragraphs, in order. Plain text; the layout escapes them. @var list<string> */
    public array $paragraphs = [];

    /** Large one-time code (OTP / reset code). */
    public ?string $code = null;

    /** Label above the code ("Verification code"). */
    public ?string $codeLabel = null;

    /** Sentence under the code ("Valid for 10 minutes"). */
    public ?string $codeExpiry = null;

    /** Paragraphs rendered directly under the code panel (the "never share this" line). @var list<string> */
    public array $afterCode = [];

    /** Label/value rows shown as a small table. @var list<array{label:string,value:string}> */
    public array $facts = [];

    /** Primary call to action. @var array{label:string,url:string}|null */
    public ?array $button = null;

    /** "What this means / what to do" panel. @var array{title:string,lines:list<string>}|null */
    public ?array $panel = null;

    /** Smaller lines after the button (expiry, "ignore this if…"). @var list<string> */
    public array $footnotes = [];

    /** When true the button URL is repeated as text ("If the button does not work…"). */
    public bool $showLinkFallback = false;

    /** Sign-off line ("The OpesCare team"). */
    public ?string $signature = null;

    /** Link to the recipient's notification preferences, when one exists for them. */
    public ?string $preferencesUrl = null;

    public static function make(string $subject, string $title): self
    {
        $c          = new self();
        $c->subject = $subject;
        $c->title   = $title;

        return $c;
    }

    public function preheader(string $text): self
    {
        $this->preheader = $text;

        return $this;
    }

    public function eyebrow(string $text, string $tone = 'default'): self
    {
        $this->eyebrow = $text;
        $this->tone    = $tone === 'urgent' ? 'urgent' : 'default';

        return $this;
    }

    public function greeting(string $text): self
    {
        $this->greeting = $text;

        return $this;
    }

    public function paragraph(?string $text): self
    {
        if ($text !== null && trim($text) !== '') {
            $this->paragraphs[] = $text;
        }

        return $this;
    }

    /** @param list<string> $texts */
    public function paragraphs(array $texts): self
    {
        foreach ($texts as $t) {
            $this->paragraph($t);
        }

        return $this;
    }

    public function code(string $code, string $label, string $expiry): self
    {
        $this->code       = $code;
        $this->codeLabel  = $label;
        $this->codeExpiry = $expiry;

        return $this;
    }

    public function afterCode(?string $text): self
    {
        if ($text !== null && trim($text) !== '') {
            $this->afterCode[] = $text;
        }

        return $this;
    }

    public function fact(string $label, ?string $value): self
    {
        if ($value !== null && trim($value) !== '') {
            $this->facts[] = ['label' => $label, 'value' => $value];
        }

        return $this;
    }

    public function button(string $label, string $url, bool $withLinkFallback = false): self
    {
        $this->button           = ['label' => $label, 'url' => $url];
        $this->showLinkFallback = $withLinkFallback;

        return $this;
    }

    /** @param list<string> $lines */
    public function panel(string $title, array $lines): self
    {
        $lines = array_values(array_filter($lines, fn ($l) => is_string($l) && trim($l) !== ''));

        if ($lines !== []) {
            $this->panel = ['title' => $title, 'lines' => $lines];
        }

        return $this;
    }

    public function footnote(?string $text): self
    {
        if ($text !== null && trim($text) !== '') {
            $this->footnotes[] = $text;
        }

        return $this;
    }

    public function signature(string $text): self
    {
        $this->signature = $text;

        return $this;
    }

    public function preferences(?string $url): self
    {
        $this->preferencesUrl = $url;

        return $this;
    }
}
