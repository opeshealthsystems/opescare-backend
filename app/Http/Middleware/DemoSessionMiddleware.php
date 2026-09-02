<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('demo.enabled')) {
            // Block all demo routes if demo is disabled
            if ($request->is('demo-access*') || $request->is('api/demo*')) {
                abort(404);
            }
        }

        // Check if user is logged in as a demo user
        if (Auth::check() && Auth::user()->is_demo) {
            $expiresAt = session('demo_session_expires_at');
            
            if ($expiresAt && now()->greaterThan($expiresAt)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::channel('single')->info('demo_session_expired');

                return redirect()->route('login')->withErrors([
                    'session' => 'Your demo session has expired. Please log in again.',
                ]);
            }
        }

        return $next($request);
    }
}
