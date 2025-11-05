<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'theme_id',
        'main_color',
        'accent_color',
        'domain',
        'database_name',
        'minio_bucket',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the theme for this site
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get all users for this site
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Get all videos for this site
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'tenant_id');
    }

    /**
     * Get all categories for this site
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'tenant_id');
    }

    /**
     * Get all live streams for this site
     */
    public function liveStreams(): HasMany
    {
        return $this->hasMany(LiveStream::class, 'tenant_id');
    }

    /**
     * Scope for active sites
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full domain URL
     */
    public function getFullDomainAttribute(): string
    {
        return "https://{$this->slug}.example.com";
    }

    /**
     * Get theme CSS variables
     */
    public function getThemeVariablesAttribute(): array
    {
        $theme = $this->theme;
        return [
            '--main-color' => $this->main_color ?: ($theme->css_variables['main'] ?? '#007bff'),
            '--accent-color' => $this->accent_color ?: ($theme->css_variables['accent'] ?? '#6c757d'),
            '--background' => $theme->css_variables['background'] ?? '#ffffff',
            '--text' => $theme->css_variables['text'] ?? '#333333',
        ];
    }
}
