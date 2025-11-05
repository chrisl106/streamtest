<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'payload',
        'headers',
        'status',
        'response',
        'processed_at',
        'error_message',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the site this webhook belongs to
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'tenant_id');
    }

    /**
     * Scope for successful webhooks
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for webhooks by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recent webhooks
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>', now()->subDays($days));
    }

    /**
     * Mark as processed
     */
    public function markProcessed($response = null): void
    {
        $this->update([
            'status' => 'success',
            'response' => $response,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed($errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Check if webhook was processed successfully
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Get webhook type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'patreon_pledge_create' => 'Patreon Pledge Created',
            'patreon_pledge_update' => 'Patreon Pledge Updated',
            'patreon_pledge_delete' => 'Patreon Pledge Deleted',
            'stripe_payment_succeeded' => 'Stripe Payment Succeeded',
            'stripe_payment_failed' => 'Stripe Payment Failed',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
