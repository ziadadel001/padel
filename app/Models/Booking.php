<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_name',
        'mobile',
        'date',
        'start_time',
        'end_time',
        'hours',
        'discount_id',
        'hour_price',
        'discount_amount',
        'total_price',
        'user_id',
        'notes',
        'is_recurring',
        'recurring_group_id',
    ];

    protected $casts = [
        'date'               => 'date',
        'hour_price'         => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'total_price'        => 'decimal:2',
        'hours'              => 'decimal:1',
        'is_recurring'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Check whether a proposed time slot overlaps with existing bookings.
     * Optionally exclude a booking ID (for update scenarios).
     */
    public static function isSlotAvailable(
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        // We need to check for overlaps considering midnight crossing.
        // A booking from 23:00 to 01:00 crosses midnight.

        $proposedStart = \Illuminate\Support\Carbon::parse("$date $startTime");
        $proposedEnd   = \Illuminate\Support\Carbon::parse("$date $endTime");

        // If end time is before or same as start time, it crosses midnight
        if ($proposedEnd->lessThanOrEqualTo($proposedStart)) {
            $proposedEnd->addDay();
        }

        $query = static::where(function ($q) use ($proposedStart, $proposedEnd) {
            // A booking overlaps if:
            // existing.start < proposed.end AND existing.end > proposed.start

            // However, since we store date, start_time, end_time separately, 
            // and we might have cross-midnight bookings, we need to be careful.

            // Simplest robust way: check bookings from yesterday and today.
            $startDate = $proposedStart->copy()->subDay()->toDateString();
            $endDate   = $proposedEnd->copy()->toDateString();

            $q->whereBetween('date', [$startDate, $endDate]);
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingBookings = $query->get();

        foreach ($existingBookings as $booking) {
            $bStart = \Illuminate\Support\Carbon::parse($booking->date->toDateString() . ' ' . $booking->start_time);
            $bEnd   = \Illuminate\Support\Carbon::parse($booking->date->toDateString() . ' ' . $booking->end_time);

            if ($bEnd->lessThanOrEqualTo($bStart)) {
                $bEnd->addDay();
            }

            if ($bStart->lessThan($proposedEnd) && $bEnd->greaterThan($proposedStart)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get booked time ranges for a given date (for schedule display).
     * Now includes bookings that started yesterday but end today.
     */
    public static function getBookedSlotsForDate(string $date): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        $targetDate = \Illuminate\Support\Carbon::parse($date);
        $yesterday = $targetDate->copy()->subDay()->toDateString();

        return static::whereIn('date', [$yesterday, $date])
            ->get(['id', 'customer_name', 'start_time', 'end_time', 'hours', 'total_price', 'date', 'is_recurring'])
            ->filter(function ($booking) use ($date) {
                $bDateStr = \Illuminate\Support\Carbon::parse($booking->date)->toDateString();
                $bStart = \Illuminate\Support\Carbon::parse($bDateStr . ' ' . $booking->start_time);
                $bEnd   = \Illuminate\Support\Carbon::parse($bDateStr . ' ' . $booking->end_time);

                if ($bEnd->lessThanOrEqualTo($bStart)) {
                    $bEnd->addDay();
                }

                $dayStart = \Illuminate\Support\Carbon::parse($date . ' 00:00:00');
                $dayEnd   = \Illuminate\Support\Carbon::parse($date . ' 23:59:59');

                // Overlaps with the target day
                return $bStart->lessThanOrEqualTo($dayEnd) && $bEnd->greaterThan($dayStart);
            })
            ->values();
    }

    protected static function booted()
    {
        static::saving(function ($booking) {
            // Auto-assign user_id if not set (for admin panel bookings)
            if (!$booking->user_id && auth()->check()) {
                $booking->user_id = auth()->id();
            }

            // Fallback for hour_price if not provided by form
            if (!$booking->hour_price) {
                $booking->hour_price = \App\Models\Setting::get('hour_price', 0);
            }

            // Auto-sync or create CRM Customer strictly attached to this booking
            if ($booking->customer_name || $booking->mobile) {
                $customerQuery = \App\Models\Customer::query();

                if ($booking->mobile) {
                    $customerQuery->where('mobile', $booking->mobile);
                } else {
                    $customerQuery->where('name', $booking->customer_name);
                }

                $customer = $customerQuery->first();

                if (!$customer) {
                    $customer = \App\Models\Customer::create([
                        'name' => $booking->customer_name ?? 'Unknown',
                        'mobile' => $booking->mobile,
                    ]);
                } else {
                    // Update CRM name if the booking introduces a new name mapping for the same mobile
                    if ($booking->customer_name && $customer->name !== $booking->customer_name) {
                        $customer->update(['name' => $booking->customer_name]);
                    }
                }

                $booking->customer_id = $customer->id;
            }
        });

        static::created(function ($booking) {
            self::broadcastActivity(
                action: 'created',
                title: "New Booking Created",
                message: "By " . (auth()->user()?->name ?? 'System') . " for " . $booking->customer_name . " on " . $booking->date->format('d M Y'),
                status: 'success',
                icon: 'heroicon-o-calendar-days',
                record: $booking
            );
        });

        static::updated(function ($booking) {
            self::broadcastActivity(
                action: 'updated',
                title: "Booking Updated",
                message: "By " . (auth()->user()?->name ?? 'System') . " for " . $booking->customer_name,
                status: 'info',
                icon: 'heroicon-o-pencil-square',
                record: $booking
            );
        });

        static::deleted(function ($booking) {
            self::broadcastActivity(
                action: 'deleted',
                title: "Booking Deleted",
                message: "By " . (auth()->user()?->name ?? 'System') . " for " . $booking->customer_name,
                status: 'danger',
                icon: 'heroicon-o-trash',
                record: $booking
            );
        });
    }

    protected static function broadcastActivity(string $action, string $title, string $message, string $status, string $icon, $record = null)
    {
        // 1. Create Persistent Activity Log
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => 'Booking',
            'model_id' => $record?->id,
            'message' => $message,
        ]);

        // 2. Broadcast Database Notification to ALL users (Sync)
        $users = \App\Models\User::all();

        $oldQueue = config('queue.default');
        config(['queue.default' => 'sync']);

        \Filament\Notifications\Notification::make()
            ->title($title)
            ->body($message)
            ->icon($icon)
            ->color($status)
            ->sendToDatabase($users);

        config(['queue.default' => $oldQueue]);
    }
}
