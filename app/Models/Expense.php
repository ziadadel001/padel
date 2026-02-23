<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'amount',
        'date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::created(function ($expense) {
            self::broadcastActivity(
                action: 'created',
                title: "New Expense Added",
                message: "By " . (auth()->user()?->name ?? 'System') . ": " . $expense->title . " (EGP " . number_format($expense->amount, 2) . ")",
                status: 'warning',
                icon: 'heroicon-o-banknotes',
                record: $expense
            );
        });

        static::updated(function ($expense) {
            self::broadcastActivity(
                action: 'updated',
                title: "Expense Updated",
                message: "By " . (auth()->user()?->name ?? 'System') . ": " . $expense->title,
                status: 'info',
                icon: 'heroicon-o-pencil-square',
                record: $expense
            );
        });

        static::deleted(function ($expense) {
            self::broadcastActivity(
                action: 'deleted',
                title: "Expense Deleted",
                message: "By " . (auth()->user()?->name ?? 'System') . ": " . $expense->title,
                status: 'danger',
                icon: 'heroicon-o-trash',
                record: $expense
            );
        });
    }

    protected static function broadcastActivity(string $action, string $title, string $message, string $status, string $icon, $record = null)
    {
        // 1. Create Persistent Activity Log
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => 'Expense',
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
