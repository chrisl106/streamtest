<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'seconds_watched',
        'completed',
        'ip_address',
        'user_agent',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'seconds_watched' => 'integer',
            'completed' => 'boolean',
        ];
    }

    /**
     * Get the video this view belongs to
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the user who watched this video
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site this view belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Scope for completed views
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope for views in date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get watch time in minutes
     */
    public function getWatchTimeMinutesAttribute(): float
    {
        return round($this->seconds_watched / 60, 2);
    }
}
