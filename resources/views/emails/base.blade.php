{{--
    Legacy adapter. Nothing in app/ renders this view any more, but it used to
    be the "generic" email template ($title, $body, $cta_label, $cta_url) and
    carried its own off-brand dark theme. It now maps those variables onto the
    one brand layout so any straggler keeps working and looks like the rest.
--}}
@php
    $email = \App\Mail\Support\EmailContent::make((string) ($subject ?? $title ?? 'OpesCare'), (string) ($title ?? 'OpesCare'))
        ->preheader((string) ($title ?? ''))
        ->eyebrow(__('mail.notification.eyebrow'))
        ->paragraphs(array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($body ?? '')) ?: []), fn ($l) => $l !== '')));

    if (isset($cta_label, $cta_url)) {
        $email->button((string) $cta_label, (string) $cta_url);
    }
@endphp
@include('emails.layout', ['email' => $email])
