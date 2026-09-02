<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->requiresTwoFactor()
            && ! (bool) $request->session()->get('mfa.verified', false)
        ) {
            $request->session()->put('mfa.user_id', $user->id);
            $request->session()->put('mfa.setup_required', ! $user->hasTwoFactorEnabled());

            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
