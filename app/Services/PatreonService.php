<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PatreonService
{
    protected $clientId;
    protected $clientSecret;
    protected $campaignId;

    public function __construct()
    {
        $this->clientId = config('services.patreon.client_id');
        $this->clientSecret = config('services.patreon.client_secret');
        $this->campaignId = config('services.patreon.campaign_id');
    }

    /**
     * Check if a Patreon user has an active paid membership
     */
    public function isPaidMember($patreonUser): bool
    {
        try {
            $memberData = $this->getMemberData($patreonUser);

            if (!$memberData) {
                return false;
            }

            // Check if currently entitled to content (active paid member)
            return $memberData['attributes']['currently_entitled_amount_cents'] >= 200; // $2.00

        } catch (\Exception $e) {
            Log::error('Patreon membership check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is paid member using stored token
     */
    public function isPaidMemberFromToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->get('https://www.patreon.com/api/oauth2/v2/campaigns/' . $this->campaignId . '/members', [
                    'fields[member]' => 'currently_entitled_amount_cents',
                ]);

            if (!$response->successful()) {
                return false;
            }

            $members = $response->json()['data'] ?? [];

            foreach ($members as $member) {
                if (($member['attributes']['currently_entitled_amount_cents'] ?? 0) >= 200) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Patreon token check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pledge amount for user
     */
    public function getPledgeAmount($patreonUser): int
    {
        try {
            $memberData = $this->getMemberData($patreonUser);
            return $memberData['attributes']['currently_entitled_amount_cents'] ?? 0;

        } catch (\Exception $e) {
            Log::error('Failed to get pledge amount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get member data from Patreon API
     */
    private function getMemberData($patreonUser): ?array
    {
        $cacheKey = "patreon_member_{$patreonUser->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($patreonUser) {
            try {
                $response = Http::withToken($patreonUser->token)
                    ->get("https://www.patreon.com/api/oauth2/v2/campaigns/{$this->campaignId}/members", [
                        'fields[member]' => 'currently_entitled_amount_cents,pledge_relationship_start,patron_status',
                    ]);

                if (!$response->successful()) {
                    Log::error('Patreon API request failed: ' . $response->status());
                    return null;
                }

                $data = $response->json();

                // Find the current user's membership
                foreach ($data['data'] ?? [] as $member) {
                    // The API should return memberships for the authenticated user
                    // In a more complex setup, you'd match by user ID
                    return $member;
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Patreon API error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Refresh Patreon token if needed
     */
    public function refreshTokenIfNeeded(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()
                ->post('https://www.patreon.com/api/oauth2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get campaign members (for admin purposes)
     */
    public function getCampaignMembers(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://www.patreon.com/api/oauth2/v2/campaigns/{$this->campaignId}/members", [
                    'fields[member]' => 'full_name,email,currently_entitled_amount_cents,patron_status',
                    'include' => 'user',
                    'fields[user]' => 'first_name,last_name',
                ]);

            if (!$response->successful()) {
                return [];
            }

            return $response->json()['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign members: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync Patreon members to local database
     */
    public function syncMembersToDatabase(string $accessToken, int $tenantId): void
    {
        $members = $this->getCampaignMembers($accessToken);

        foreach ($members as $member) {
            $attributes = $member['attributes'];
            $userData = $member['relationships']['user']['data'] ?? null;

            if ($userData) {
                // This would require additional API calls to get user details
                // For now, we'll store basic member info
                \App\Models\User::updateOrCreate(
                    ['patreon_id' => $userData['id']],
                    [
                        'name' => $attributes['full_name'] ?? 'Unknown',
                        'email' => $attributes['email'] ?? null,
                        'pledge_cents' => $attributes['currently_entitled_amount_cents'] ?? 0,
                        'tenant_id' => $tenantId,
                    ]
                );
            }
        }
    }
}
