<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PatreonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class PatreonAuthController extends Controller
{
    protected $patreonService;

    public function __construct(PatreonService $patreonService)
    {
        $this->patreonService = $patreonService;
    }

    /**
     * Redirect to Patreon OAuth
     */
    public function redirect()
    {
        return Socialite::driver('patreon')
            ->scopes(['identity', 'campaigns', 'campaigns.members'])
            ->redirect();
    }

    /**
     * Handle Patreon OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $patreonUser = Socialite::driver('patreon')->user();

            // Check if user has active paid membership
            $isPaidMember = $this->patreonService->isPaidMember($patreonUser);

            if (!$isPaidMember) {
                return redirect()->route('login')->withErrors([
                    'patreon' => 'Active paid Patreon membership required (≥$2/month).'
                ]);
            }

            // Get pledge amount
            $pledgeCents = $this->patreonService->getPledgeAmount($patreonUser);

            // Find or create user
            $user = User::updateOrCreate(
                ['patreon_id' => $patreonUser->id],
                [
                    'name' => $patreonUser->name ?? $patreonUser->nickname,
                    'email' => $patreonUser->email,
                    'patreon_token' => $patreonUser->token,
                    'pledge_cents' => $pledgeCents,
                    'is_admin' => false, // Set manually for admins
                ]
            );

            // Cache the Patreon check for 5 minutes
            Cache::put("patreon_check_{$user->id}", true, now()->addMinutes(5));

            Auth::login($user, true);

            // Redirect based on user type and context
            if ($request->session()->has('url.intended')) {
                return redirect($request->session()->get('url.intended'));
            }

            // Check if user has completed onboarding
            if (empty($user->category_prefs)) {
                return redirect()->route('onboarding');
            }

            return redirect()->route('home');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Patreon OAuth error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'patreon' => 'Failed to authenticate with Patreon. Please try again.'
            ]);
        }
    }

    /**
     * Show login page
     */
    public function showLoginForm()
    {
        // If user is already authenticated and has valid Patreon membership
        if (Auth::check()) {
            $user = Auth::user();

            // Check Patreon status with caching
            $isPaid = Cache::remember("patreon_check_{$user->id}", now()->addMinutes(5), function () use ($user) {
                return $this->patreonService->isPaidMemberFromToken($user->patreon_token);
            });

            if ($isPaid) {
                return redirect()->route('home');
            }

            // Logout invalid users
            Auth::logout();
        }

        return Inertia::render('Auth/Login', [
            'patreonUrl' => route('auth.patreon'),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Check Patreon membership status (AJAX endpoint)
     */
    public function checkMembership(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['valid' => false]);
        }

        $isPaid = Cache::remember("patreon_check_{$user->id}", now()->addMinutes(5), function () use ($user) {
            return $this->patreonService->isPaidMemberFromToken($user->patreon_token);
        });

        if (!$isPaid) {
            Auth::logout();
        }

        return response()->json([
            'valid' => $isPaid,
            'pledge_cents' => $user->pledge_cents,
        ]);
    }
}
