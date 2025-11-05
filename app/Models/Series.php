<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Series extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'season_number',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'season_number' => 'integer',
        ];
    }

    /**
     * Get all videos in this series
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the site this series belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Get videos by season
     */
    public function getVideosBySeasonAttribute(): array
    {
        $videos = $this->videos;
        $seasons = [];

        foreach ($videos as $video) {
            $season = $video->season_number ?: 1;
            if (!isset($seasons[$season])) {
                $seasons[$season] = [];
            }
            $seasons[$season][] = $video;
        }

        return $seasons;
    }

    /**
     * Get total episodes count
     */
    public function getTotalEpisodesAttribute(): int
    {
        return $this->videos()->count();
    }
}
