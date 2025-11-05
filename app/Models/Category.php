<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'layout',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /**
     * Get all videos in this category
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the site this category belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Scope for ordering categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Get layout options
     */
    public static function getLayoutOptions(): array
    {
        return [
            'grid' => 'Grid',
            'carousel' => 'Carousel',
        ];
    }
}
