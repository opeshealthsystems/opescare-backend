<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="{{ $retryAfter > 0 && $retryAfter < 3600 ? min($retryAfter, 300) : 300 }}">
    <title>OpesCare — Scheduled Maintenance</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            max-width: 520px;
            width: 100%;
            padding: 48px 40px;
            text-align: center;
        }

        .icon {
            width: 72px;
            height: 72px;
            background: #fff7ed;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }

        .icon svg { width: 36px; height: 36px; }

        .badge {
            display: inline-block;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .message {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .times {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 28px;
            text-align: left;
        }

        .times .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #374151;
            padding: 4px 0;
        }

        .times .row:not(:last-child) {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .times .label { color: #9ca3af; font-weight: 500; }
        .times .value { font-weight: 600; }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .footer {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 28px;
        }

        .footer a { color: #6366f1; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        </div>

        <span class="badge">
            <span class="status-dot"></span>Maintenance in Progress
        </span>

        <h1>{{ $window->title ?? 'Scheduled Maintenance' }}</h1>

        <p class="message">
            {{ $window->message ?? 'OpesCare is currently undergoing scheduled maintenance to improve your experience. We\'ll be back online shortly.' }}
        </p>

        <div class="times">
            @if($window->starts_at)
            <div class="row">
                <span class="label">Started</span>
                <span class="value">{{ $window->starts_at->format('D, d M Y H:i') }} UTC</span>
            </div>
            @endif
            @if($window->ends_at)
            <div class="row">
                <span class="label">Expected end</span>
                <span class="value">{{ $window->ends_at->format('D, d M Y H:i') }} UTC</span>
            </div>
            <div class="row">
                <span class="label">Time remaining</span>
                <span class="value">~{{ $window->ends_at->diffForHumans(null, true) }}</span>
            </div>
            @else
            <div class="row">
                <span class="label">Duration</span>
                <span class="value">Until further notice</span>
            </div>
            @endif
        </div>

        @if($window->allow_emergency_access)
        <p class="message" style="font-size:13px; color:#6366f1;">
            Emergency patient access remains available during this window.
        </p>
        @endif

        <p class="footer">
            This page refreshes automatically every 5 minutes.<br>
            For urgent support contact <a href="mailto:support@opescare.com">support@opescare.com</a>
        </p>
    </div>
</body>
</html>
