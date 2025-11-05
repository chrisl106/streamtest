<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'source_type',
        'storage_path',
        'hls_url',
        'thumbnail',
        'allow_download',
        'series_id',
        'views_count',
        'total_minutes_watched',
        'duration',
        'file_size',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'allow_download' => 'boolean',
            'views_count' => 'integer',
            'total_minutes_watched' => 'integer',
            'duration' => 'integer',
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the category this video belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the series this video belongs to
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get all views for this video
     */
    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * Get the site this video belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->thumbnail) {
            return asset('images/default-thumbnail.jpg');
        }

        return Storage::disk('minio')->url($this->thumbnail);
    }

    /**
     * Get HLS stream URL
     */
    public function getStreamUrlAttribute(): string
    {
        if (!$this->hls_url) {
            return '';
        }

        return Storage::disk('minio')->url($this->hls_url);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '00:00';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 MB';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for downloadable videos
     */
    public function scopeDownloadable($query)
    {
        return $query->where('allow_download', true);
    }

    /**
     * Scope for videos in a series
     */
    public function scopeInSeries($query, $seriesId)
    {
        return $query->where('series_id', $seriesId);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Add watch time
     */
    public function addWatchTime(int $seconds): void
    {
        $this->increment('total_minutes_watched', floor($seconds / 60));
    }
}
