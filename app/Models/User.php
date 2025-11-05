<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'patreon_id',
        'patreon_token',
        'pledge_cents',
        'is_admin',
        'banned_at',
        'devices_count',
        'last_ip',
        'category_prefs',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'patreon_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
            'pledge_cents' => 'integer',
            'is_admin' => 'boolean',
            'devices_count' => 'integer',
            'category_prefs' => 'array',
        ];
    }

    /**
     * Check if user has active Patreon pledge
     */
    public function isPatreonPaid(): bool
    {
        return $this->pledge_cents >= 200; // $2.00 minimum
    }

    /**
     * Check if user is banned
     */
    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Get the site this user belongs to (for multi-tenant)
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Get user's video views
     */
    public function videoViews(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * Get user's sessions for device tracking
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Scope for active (non-banned) users
     */
    public function scopeActive($query)
    {
        return $query->whereNull('banned_at');
    }

    /**
     * Scope for paid Patreon users
     */
    public function scopePaid($query)
    {
        return $query->where('pledge_cents', '>=', 200);
    }
}
