<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminIdleTimeout
{
    public const SESSION_KEY = 'admin.last_activity_at';

    public const TIMEOUT_SECONDS = 60;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if ($guard->check()) {
            $now = now()->getTimestamp();
            $lastActivity = $request->session()->get(self::SESSION_KEY);

            if (is_numeric($lastActivity) && ($now - (int) $lastActivity) >= self::TIMEOUT_SECONDS) {
                $guard->logout();
                $request->session()->forget(self::SESSION_KEY);
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException(
                    'Unauthenticated.',
                    [Filament::getAuthGuard()],
                    Filament::getLoginUrl(),
                );
            }

            $request->session()->put(self::SESSION_KEY, $now);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
