<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = ['name', 'type', 'value', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Calculate the discount amount based on a subtotal.
     */
    public function calculateAmount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            return round($subtotal * ($this->value / 100), 2);
        }
        return min($this->value, $subtotal); // fixed, can't exceed subtotal
    }

    /**
     * Get a human-readable label for the discount.
     */
    public function getLabelAttribute(): string
    {
        if ($this->type === 'percentage') {
            return "{$this->name} ({$this->value}%)";
        }
        return "{$this->name} (- {$this->value})";
    }
}
