<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RequireAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If no user is authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user is banned
        if ($user->isBanned()) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been banned.');
        }

        // Check Patreon pledge status with caching
        $cacheKey = "patreon_check_{$user->id}";
        $isPaid = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return $user->isPatreonPaid();
        });

        if (!$isPaid) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Active Patreon membership required.');
        }

        // Track user session for device limits
        $this->trackUserSession($request, $user);

        return $next($request);
    }

    /**
     * Track user session for device/IP monitoring
     */
    private function trackUserSession(Request $request, $user): void
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Check for multiple IPs in short time (potential security issue)
        $recentIps = Cache::remember("user_ips_{$user->id}", now()->addMinutes(5), function () use ($user) {
            return \App\Models\Session::where('user_id', $user->id)
                ->where('created_at', '>', now()->subMinutes(5))
                ->distinct('ip_address')
                ->pluck('ip_address')
                ->toArray();
        });

        if (count($recentIps) > 1 && !in_array($ip, $recentIps)) {
            // Log potential security issue
            \Illuminate\Support\Facades\Log::warning("Multiple IP addresses detected for user {$user->id}", [
                'user_id' => $user->id,
                'ips' => array_merge($recentIps, [$ip]),
                'user_agent' => $userAgent,
            ]);
        }

        // Update or create session
        \App\Models\Session::updateOrCreate(
            [
                'user_id' => $user->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ],
            [
                'is_active' => true,
                'last_activity' => now(),
                'tenant_id' => $user->tenant_id,
            ]
        );

        // Check device limit (max 2 active sessions)
        $activeSessions = \App\Models\Session::where('user_id', $user->id)
            ->active()
            ->count();

        if ($activeSessions > 2) {
            Auth::logout();
            throw new \Illuminate\Auth\AuthenticationException('Maximum device limit exceeded.');
        }
    }
}
