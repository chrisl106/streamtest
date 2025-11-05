<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'key',
        'is_live',
        'record',
        'recorded_path',
        'viewer_count',
        'stream_url',
        'chat_enabled',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'record' => 'boolean',
            'viewer_count' => 'integer',
            'chat_enabled' => 'boolean',
        ];
    }

    /**
     * Get the site this live stream belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Get RTMP ingest URL
     */
    public function getRtmpUrlAttribute(): string
    {
        $site = $this->site;
        return "rtmp://{$site->slug}.example.com/live/{$this->key}";
    }

    /**
     * Get HLS stream URL
     */
    public function getHlsUrlAttribute(): string
    {
        if (!$this->is_live) {
            return '';
        }

        $site = $this->site;
        return "https://{$site->slug}.example.com/hls/{$this->key}.m3u8";
    }

    /**
     * Get recorded video URL if available
     */
    public function getRecordedUrlAttribute(): string
    {
        if (!$this->recorded_path) {
            return '';
        }

        return \Illuminate\Support\Facades\Storage::disk('minio')->url($this->recorded_path);
    }

    /**
     * Check if stream is currently active
     */
    public function isActive(): bool
    {
        return $this->is_live && $this->updated_at->diffInMinutes() < 5;
    }

    /**
     * Start the live stream
     */
    public function startStream(): void
    {
        $this->update([
            'is_live' => true,
            'viewer_count' => 0,
        ]);
    }

    /**
     * End the live stream
     */
    public function endStream(): void
    {
        $this->update([
            'is_live' => false,
        ]);
    }

    /**
     * Update viewer count
     */
    public function updateViewerCount(int $count): void
    {
        $this->update(['viewer_count' => $count]);
    }

    /**
     * Scope for active streams
     */
    public function scopeActive($query)
    {
        return $query->where('is_live', true)
                    ->where('updated_at', '>', now()->subMinutes(5));
    }
}
