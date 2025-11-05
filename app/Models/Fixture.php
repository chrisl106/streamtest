<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Fixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_team',
        'away_team',
        'date',
        'league',
        'status',
        'home_score',
        'away_score',
        'venue',
        'api_fixture_id',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    /**
     * Get the site this fixture belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Scope for upcoming fixtures
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>', now())
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('date', 'asc');
    }

    /**
     * Scope for fixtures within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for rugby fixtures
     */
    public function scopeRugby($query)
    {
        return $query->where('league', 'like', '%rugby%');
    }

    /**
     * Get formatted match time
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->date->format('H:i');
    }

    /**
     * Get formatted match date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('M j, Y');
    }

    /**
     * Get countdown to match
     */
    public function getCountdownAttribute(): string
    {
        $now = now();
        $matchTime = $this->date;

        if ($matchTime->isPast()) {
            return 'Started';
        }

        $diff = $now->diff($matchTime);

        if ($diff->days > 0) {
            return $diff->days . ' days';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minutes';
        } else {
            return 'Soon';
        }
    }

    /**
     * Get match result
     */
    public function getResultAttribute(): ?string
    {
        if ($this->status !== 'finished' || is_null($this->home_score) || is_null($this->away_score)) {
            return null;
        }

        return "{$this->home_score} - {$this->away_score}";
    }

    /**
     * Check if match is live
     */
    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    /**
     * Check if match is finished
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'live' => 'green',
            'finished' => 'gray',
            'cancelled' => 'red',
            default => 'blue',
        };
    }
}
