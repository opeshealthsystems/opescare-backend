<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'OpesCare Update' }}</title>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 24px;
            background-color: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        .logo {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            color: #10b981;
            letter-spacing: 0.05em;
            margin-bottom: 24px;
        }
        .logo span {
            color: #38bdf8;
        }
        .header {
            border-bottom: 1px solid #334155;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0;
        }
        .content {
            font-size: 16px;
            line-height: 1.6;
            color: #cbd5e1;
            margin-bottom: 32px;
        }
        .btn-container {
            text-align: center;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease-in-out;
        }
        .footer {
            border-top: 1px solid #334155;
            padding-top: 16px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }
        .warning-notice {
            background-color: #312e81;
            border-left: 4px solid #6366f1;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #e0e7ff;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            OPES<span>CARE</span>
        </div>
        <div class="header">
            <h1 class="title">{{ $title }}</h1>
        </div>
        <div class="content">
            <p>{{ $body }}</p>
        </div>
        @if(isset($cta_label) && isset($cta_url))
            <div class="btn-container">
                <a href="{{ $cta_url }}" class="btn">{{ $cta_label }}</a>
            </div>
        @endif
        <div class="footer">
            <p>{{ __('public.emails.footer_automated', [], app()->getLocale()) ?: 'This is an automated operational transmission from OpesCare Platform.' }}</p>
            <p>{{ __('public.emails.footer_privacy', [], app()->getLocale()) ?: 'To protect your privacy, this external communication does not contain detailed medical history or diagnostic values. Log in to your secure portal to review detailed clinical records.' }}</p>
            <p>{{ __('public.emails.footer_copyright', ['year' => date('Y')], app()->getLocale()) ?: '© ' . date('Y') . ' OpesCare. All rights reserved.' }}</p>
        </div>
    </div>
</body>
</html>
