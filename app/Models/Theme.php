<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'css_variables',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'css_variables' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get all sites using this theme
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Scope for default theme
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get CSS variables as CSS string
     */
    public function getCssVariablesStringAttribute(): string
    {
        $css = '';
        foreach ($this->css_variables as $key => $value) {
            $css .= "--{$key}: {$value}; ";
        }
        return trim($css);
    }
}
