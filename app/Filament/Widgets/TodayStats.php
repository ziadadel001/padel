<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TodayStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        $bookingsCount = Booking::whereDate('date', $today)->count();
        $bookedHours = Booking::whereDate('date', $today)->sum('hours');
        $expenses = Expense::whereDate('date', $today)->sum('amount');

        // Admins see revenue/expenses, staff see counts
        $isAdmin = auth()->user()?->hasRole('admin');

        $stats = [
            Stat::make("Today's Bookings", $bookingsCount)
                ->description('Total slots reserved')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->url(url('/admin/schedule')),

            Stat::make('Court Usage', $bookedHours . 'h')
                ->description('Total hours occupied')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];

        if ($isAdmin) {
            $stats[] = Stat::make("Today's Expenses", 'EGP ' . number_format($expenses, 2))
                ->description('Cash outflow today')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('danger');
        }

        return $stats;
    }
}
