<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OpesCare Verification Code</title>
    <style>
        body { background-color:#0f172a;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;margin:0;padding:0; }
        .container { max-width:600px;margin:40px auto;padding:24px;background-color:#1e293b;border-radius:12px;border:1px solid #334155;box-shadow:0 10px 15px -3px rgba(0,0,0,.3); }
        .logo { text-align:center;font-size:24px;font-weight:800;color:#10b981;letter-spacing:.05em;margin-bottom:24px; }
        .logo span { color:#38bdf8; }
        .header { border-bottom:1px solid #334155;padding-bottom:16px;margin-bottom:24px; }
        .title { font-size:20px;font-weight:700;color:#f1f5f9;margin:0; }
        .body-text { font-size:15px;line-height:1.6;color:#cbd5e1;margin-bottom:28px; }
        .code-box { text-align:center;margin:28px 0; }
        .code-inner { display:inline-block;background:#0F2744;border:1px solid #1e3a5f;border-radius:12px;padding:20px 48px; }
        .code-label { margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#64748b; }
        .code-value { margin:0;font-size:44px;font-weight:800;letter-spacing:.28em;color:#10B981;font-family:'Courier New',monospace; }
        .expiry { text-align:center;font-size:13px;color:#94A3B8;margin:0 0 8px; }
        .ignore { text-align:center;font-size:12px;color:#64748b;margin:0 0 28px; }
        .footer { border-top:1px solid #334155;padding-top:16px;font-size:12px;color:#64748b;text-align:center;line-height:1.5; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">OPES<span>CARE</span></div>
    <div class="header">
        <h1 class="title">Two-Factor Verification</h1>
    </div>
    <p class="body-text">
        Use the one-time code below to complete your sign-in to the OpesCare platform.
        The code is valid for <strong style="color:#f1f5f9;">{{ $expiryMinutes }} minutes</strong>.
    </p>
    <div class="code-box">
        <div class="code-inner">
            <p class="code-label">Verification Code</p>
            <p class="code-value">{{ $code }}</p>
        </div>
    </div>
    <p class="expiry">Expires in <strong style="color:#f1f5f9;">{{ $expiryMinutes }} minutes</strong></p>
    <p class="ignore">If you did not attempt to sign in, you can safely ignore this email.</p>
    <div class="footer">
        <p>{{ __('public.emails.footer_automated', [], app()->getLocale()) ?: 'This is an automated operational transmission from OpesCare Platform.' }}</p>
        <p>{{ __('public.emails.footer_copyright', ['year' => date('Y')], app()->getLocale()) ?: '© ' . date('Y') . ' OpesCare. All rights reserved.' }}</p>
    </div>
</div>
</body>
</html>
