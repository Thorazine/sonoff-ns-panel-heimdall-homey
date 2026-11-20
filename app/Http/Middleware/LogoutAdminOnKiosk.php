<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutAdminOnKiosk
{
    /**
     * Automatically terminate any active Filament admin session when returning to the Kiosk UI.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if ($guard->check()) {
            $guard->logout();
            $request->session()->forget(EnforceAdminIdleTimeout::SESSION_KEY);
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
