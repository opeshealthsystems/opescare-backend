{{--
    OpesCare transactional email — the one HTML layout every message uses.

    Fed by App\Mail\Support\EmailContent ($email). Table-based, 600px, every
    colour inline and guarded with !important so Gmail / Apple Mail dark mode
    cannot invert the brand block. Tokens mirror public/assets/finder.css:
    brand #0F4C81, brand-ink #0B3A66, wash #E9F1F8, ink-soft #334155,
    muted #64748B, line #E2E8F0, warn #B54708. System font stack, no webfonts,
    no icons (Lucide is JS), no emoji.
--}}
@php
    /** @var \App\Mail\Support\EmailContent $email */
    $locale     = str_replace('_', '-', app()->getLocale());
    $logoUrl    = asset('brand/opescare-email-logo.png');
    $homeUrl    = rtrim((string) config('app.url'), '/');
    $supportEmail = trim((string) config('opescare.support_email')); // never the from-address: that is a no-reply bin
    $isUrgent   = $email->tone === 'urgent';
    $font       = "system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
    $mono       = "'JetBrains Mono', 'Fira Code', Consolas, 'Courier New', monospace";
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="{{ $locale }}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="x-apple-disable-message-reformatting" />
<meta name="color-scheme" content="light only" />
<meta name="supported-color-schemes" content="light only" />
<title>{{ $email->title }}</title>
<!--[if mso]>
<xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
<style>table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; } .code { font-family: Consolas, 'Courier New', monospace !important; }</style>
<![endif]-->
<style>
:root { color-scheme: light only; supported-color-schemes: light only; }
html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #F1F5F9 !important; }
body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
table { border-spacing: 0; }
img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
a { color: #0F4C81; }
/* Dark-mode guards: the brand block, the button and the code panel keep their colours. */
[data-ogsc] .brand-bar, [data-ogsb] .brand-bar, .brand-bar { background-color: #0F4C81 !important; }
[data-ogsc] .brand-name, .brand-name { color: #FFFFFF !important; }
[data-ogsc] .btn a, .btn a { background-color: #0F4C81 !important; color: #FFFFFF !important; }
[data-ogsc] .card, .card { background-color: #FFFFFF !important; }
[data-ogsc] .code-panel, .code-panel { background-color: #E9F1F8 !important; }
@media (prefers-color-scheme: dark) {
  .brand-bar { background-color: #0F4C81 !important; }
  .card { background-color: #FFFFFF !important; }
  .body-text, .title { color: #334155 !important; }
  .title { color: #0B3A66 !important; }
}
@media only screen and (max-width: 620px) {
  .container { width: 100% !important; max-width: 100% !important; }
  .pad { padding-left: 20px !important; padding-right: 20px !important; }
  .title { font-size: 22px !important; line-height: 30px !important; }
  .kind { display: none !important; }
  .btn, .btn table, .btn a { width: 100% !important; display: block !important; box-sizing: border-box !important; text-align: center !important; }
  .code { font-size: 30px !important; letter-spacing: 6px !important; }
  .fact-label, .fact-value { display: block !important; width: 100% !important; padding-left: 0 !important; }
  .fact-label { padding-bottom: 2px !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#F1F5F9;">
{{-- Preheader: the preview line, hidden in the body. --}}
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:#F1F5F9;">{{ $email->preheader !== '' ? $email->preheader : $email->title }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F1F5F9;">
<tr>
<td align="center" style="padding:32px 16px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td><![endif]-->
<table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;margin:0 auto;">

{{-- ── Brand bar ─────────────────────────────────────────────────────── --}}
<tr>
<td class="brand-bar pad" bgcolor="#0F4C81" style="background-color:#0F4C81;padding:22px 32px;border-radius:12px 12px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td width="44" valign="middle" style="width:44px;">
<a href="{{ $homeUrl }}" style="text-decoration:none;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td bgcolor="#FFFFFF" style="background-color:#FFFFFF;border-radius:10px;padding:5px;line-height:0;">
<img src="{{ $logoUrl }}" width="34" height="34" alt="" style="display:block;width:34px;height:34px;border:0;" />
</td></tr></table>
</a>
</td>
<td valign="middle" style="padding-left:14px;">
<a href="{{ $homeUrl }}" class="brand-name" style="font-family:{{ $font }};font-size:21px;font-weight:700;letter-spacing:-0.01em;color:#FFFFFF;text-decoration:none;line-height:24px;">OpesCare</a>
<div style="font-family:{{ $font }};font-size:12px;line-height:16px;color:#C9DBEA;letter-spacing:0.02em;">{{ __('mail.chrome.tagline') }}</div>
</td>
<td class="kind" valign="middle" align="right" style="font-family:{{ $font }};font-size:11px;line-height:16px;color:#C9DBEA;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap;">{{ __('mail.chrome.kind') }}</td>
</tr>
</table>
</td>
</tr>

{{-- ── Card ──────────────────────────────────────────────────────────── --}}
<tr>
<td class="card pad" bgcolor="#FFFFFF" style="background-color:#FFFFFF;padding:36px 40px 32px 40px;border:1px solid #E2E8F0;border-top:0;border-radius:0 0 12px 12px;">

@if($email->eyebrow)
<div style="font-family:{{ $font }};font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $isUrgent ? '#B54708' : '#0F4C81' }};padding-bottom:10px;">
@if($isUrgent)<span style="display:inline-block;width:8px;height:8px;border-radius:4px;background-color:#B54708;vertical-align:middle;margin-right:8px;"></span>@endif{{ $email->eyebrow }}
</div>
@endif

<h1 class="title" style="margin:0 0 18px 0;font-family:{{ $font }};font-size:26px;line-height:34px;font-weight:700;color:#0B3A66;letter-spacing:-0.01em;">{{ $email->title }}</h1>

@if($email->greeting)
<p class="body-text" style="margin:0 0 14px 0;font-family:{{ $font }};font-size:16px;line-height:24px;color:#334155;">{{ $email->greeting }}</p>
@endif

@foreach($email->paragraphs as $p)
<p class="body-text" style="margin:0 0 14px 0;font-family:{{ $font }};font-size:16px;line-height:24px;color:#334155;">{{ $p }}</p>
@endforeach

@if($email->code)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 22px 0;">
<tr>
<td class="code-panel" align="center" bgcolor="#E9F1F8" style="background-color:#E9F1F8;border:1px solid #C9DBEA;border-radius:10px;padding:22px 20px;">
<div style="font-family:{{ $font }};font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#0F4C81;padding-bottom:8px;">{{ $email->codeLabel }}</div>
<div class="code" style="font-family:{{ $mono }};font-size:38px;line-height:46px;font-weight:700;letter-spacing:10px;color:#0B3A66;padding-left:10px;">{{ $email->code }}</div>
<div style="font-family:{{ $font }};font-size:14px;line-height:20px;color:#334155;padding-top:8px;">{{ $email->codeExpiry }}</div>
</td>
</tr>
</table>
@foreach($email->afterCode as $p)
<p class="body-text" style="margin:0 0 14px 0;font-family:{{ $font }};font-size:16px;line-height:24px;color:#0B3A66;font-weight:600;">{{ $p }}</p>
@endforeach
@endif

@if($email->facts !== [])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px 0;border-top:1px solid #E2E8F0;">
@foreach($email->facts as $fact)
<tr>
<td class="fact-label" width="38%" valign="top" style="width:38%;padding:11px 12px 11px 0;border-bottom:1px solid #E2E8F0;font-family:{{ $font }};font-size:13px;line-height:20px;font-weight:600;color:#64748B;">{{ $fact['label'] }}</td>
<td class="fact-value" valign="top" style="padding:11px 0;border-bottom:1px solid #E2E8F0;font-family:{{ $font }};font-size:16px;line-height:20px;color:#0B3A66;font-weight:600;">{{ $fact['value'] }}</td>
</tr>
@endforeach
</table>
@endif

@if($email->button)
<table role="presentation" class="btn" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 22px 0;">
<tr>
<td align="left">
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $email->button['url'] }}" style="height:48px;v-text-anchor:middle;width:280px;" arcsize="17%" strokecolor="#0F4C81" fillcolor="#0F4C81">
<w:anchorlock/>
<center style="color:#FFFFFF;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">{{ $email->button['label'] }}</center>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td bgcolor="#0F4C81" style="background-color:#0F4C81;border-radius:8px;">
<a href="{{ $email->button['url'] }}" target="_blank" rel="noopener" style="display:inline-block;padding:14px 28px;font-family:{{ $font }};font-size:16px;line-height:20px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:8px;background-color:#0F4C81;">{{ $email->button['label'] }}</a>
</td></tr></table>
<!--<![endif]-->
</td>
</tr>
</table>
@endif

@if($email->panel)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 22px 0;">
<tr>
<td bgcolor="#F7FAFC" style="background-color:#F7FAFC;border-left:4px solid {{ $isUrgent ? '#B54708' : '#0F4C81' }};border-radius:0 8px 8px 0;padding:16px 20px;">
<div style="font-family:{{ $font }};font-size:13px;line-height:18px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#0B3A66;padding-bottom:6px;">{{ $email->panel['title'] }}</div>
@foreach($email->panel['lines'] as $line)
<p style="margin:0 0 8px 0;font-family:{{ $font }};font-size:15px;line-height:22px;color:#334155;">{{ $line }}</p>
@endforeach
</td>
</tr>
</table>
@endif

@foreach($email->footnotes as $note)
<p style="margin:0 0 10px 0;font-family:{{ $font }};font-size:14px;line-height:21px;color:#64748B;">{{ $note }}</p>
@endforeach

@if($email->button && $email->showLinkFallback)
<p style="margin:12px 0 0 0;font-family:{{ $font }};font-size:13px;line-height:19px;color:#64748B;">{{ __('mail.chrome.link_fallback') }}<br /><a href="{{ $email->button['url'] }}" style="color:#0F4C81;word-break:break-all;">{{ $email->button['url'] }}</a></p>
@endif

@if($email->signature)
<p style="margin:22px 0 0 0;font-family:{{ $font }};font-size:16px;line-height:24px;color:#334155;">{{ $email->signature }}</p>
@endif

</td>
</tr>

{{-- ── Footer ────────────────────────────────────────────────────────── --}}
<tr>
<td class="pad" align="center" style="padding:24px 32px 8px 32px;font-family:{{ $font }};font-size:12px;line-height:18px;color:#64748B;">
<p style="margin:0 0 8px 0;">{{ $supportEmail !== '' ? __('mail.chrome.no_reply', ['email' => $supportEmail]) : __('mail.chrome.no_reply_plain') }}</p>
<p style="margin:0 0 8px 0;">{{ __('mail.chrome.privacy') }}</p>
@if($email->preferencesUrl)
<p style="margin:0 0 8px 0;"><a href="{{ $email->preferencesUrl }}" style="color:#0F4C81;">{{ __('mail.chrome.preferences') }}</a></p>
@endif
<p style="margin:0;">{{ __('mail.chrome.legal') }}<br />{{ __('mail.chrome.copyright', ['year' => date('Y')]) }}</p>
</td>
</tr>

</table>
<!--[if mso]></td></tr></table><![endif]-->
</td>
</tr>
</table>
</body>
</html>
