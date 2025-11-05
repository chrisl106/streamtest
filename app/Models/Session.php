<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'location',
        'is_active',
        'last_activity',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Get the user this session belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site this session belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('last_activity', '>', now()->subMinutes(30));
    }

    /**
     * Scope for sessions by IP
     */
    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope for sessions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Update last activity
     */
    public function updateActivity(): void
    {
        $this->update([
            'last_activity' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate session
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get device type from user agent
     */
    public function getDeviceTypeAttribute(): string
    {
        $userAgent = strtolower($this->user_agent);

        if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Get browser from user agent
     */
    public function getBrowserAttribute(): string
    {
        $userAgent = strtolower($this->user_agent);

        if (strpos($userAgent, 'chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'edge') !== false) {
            return 'Edge';
        } else {
            return 'Unknown';
        }
    }
}
