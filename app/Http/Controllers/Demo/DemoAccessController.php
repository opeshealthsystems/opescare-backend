<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class DemoAccessController extends Controller
{
    public function index()
    {
        if (!config('demo.enabled')) {
            abort(404);
        }
        return redirect()->route('demo.public');
    }

    public function publicDemo()
    {
        if (!config('demo.enabled') || !config('demo.public_enabled')) {
            abort(404);
        }
        return view('demo.public');
    }

    public function internalDemo()
    {
        if (!config('demo.enabled') || !config('demo.internal_enabled')) {
            abort(404);
        }
        return view('demo.internal');
    }

    public function loginAs(Request $request)
    {
        if (!config('demo.enabled')) {
            abort(403, 'Demo mode disabled.');
        }

        // IP allowlist check — empty means any IP is allowed
        $allowedIps = array_filter(array_map('trim', explode(',', config('demo.allowed_ips', ''))));
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'Access denied from your IP address.');
        }

        $request->validate([
            'role' => 'required|string',
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->where('is_demo', true)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Demo account not found. Please run the demo reset command.']);
        }

        Auth::login($user);

        // Store session expiry info
        $mode = $request->input('mode', 'public');
        $lifetime = config("demo.session.{$mode}_lifetime_minutes", 30);
        
        session([
            'demo_mode_type' => $mode, 
            'demo_session_expires_at' => now()->addMinutes($lifetime),
            'demo_role' => $request->role
        ]);

        Log::channel('single')->info('demo_login_completed', [
            'demo_role' => $request->role,
            'demo_user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $this->sanitizeUserAgent($request->userAgent()),
            'session_expires_at' => now()->addMinutes($lifetime)
        ]);

        $url = app(DashboardProfileService::class)->landingUrlForUser($user);

        return redirect($url);
    }

    private function sanitizeUserAgent(?string $ua): string
    {
        if ($ua === null) {
            return 'unknown';
        }
        // Strip newlines (log injection prevention) and truncate to 255 chars
        $ua = str_replace(["\n", "\r"], '', $ua);
        return substr($ua, 0, 255);
    }
}
