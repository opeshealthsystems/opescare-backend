@php
    /** @var \App\Mail\Support\EmailContent $email */
    $supportEmail = trim((string) config('opescare.support_email')); // never the from-address: that is a no-reply bin
    $rule = str_repeat('-', 48);
@endphp
OPESCARE — {!! __('mail.chrome.tagline') !!}
{{ $rule }}

{!! mb_strtoupper($email->eyebrow ? $email->eyebrow . ' — ' : '') !!}{!! $email->title !!}

@if($email->greeting)
{!! $email->greeting !!}

@endif
@foreach($email->paragraphs as $p)
{!! $p !!}

@endforeach
@if($email->code)
{!! $email->codeLabel !!}: {!! $email->code !!}
{!! $email->codeExpiry !!}

@foreach($email->afterCode as $p)
{!! $p !!}

@endforeach
@endif
@foreach($email->facts as $fact)
{!! $fact['label'] !!}: {!! $fact['value'] !!}
@endforeach
@if($email->facts !== [])

@endif
@if($email->button)
{!! $email->button['label'] !!}:
{!! $email->button['url'] !!}

@endif
@if($email->panel)
{!! mb_strtoupper($email->panel['title']) !!}
@foreach($email->panel['lines'] as $line)
- {!! $line !!}
@endforeach

@endif
@foreach($email->footnotes as $note)
{!! $note !!}

@endforeach
@if($email->signature)
{!! $email->signature !!}

@endif
{{ $rule }}
{!! $supportEmail !== '' ? __('mail.chrome.no_reply', ['email' => $supportEmail]) : __('mail.chrome.no_reply_plain') !!}
{!! __('mail.chrome.privacy') !!}
@if($email->preferencesUrl)
{!! __('mail.chrome.preferences') !!}: {!! $email->preferencesUrl !!}
@endif
{!! __('mail.chrome.legal') !!}
{!! __('mail.chrome.copyright', ['year' => date('Y')]) !!}
