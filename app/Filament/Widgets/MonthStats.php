<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MonthStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    protected function getStats(): array
    {
        $now = Carbon::now();

        $bookingsCount = Booking::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->count();

        $revenue = Booking::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('total_price');

        $expenses = Expense::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $profit = $revenue - $expenses;

        return [
            Stat::make('Monthly Bookings', $bookingsCount)
                ->description('Sessions this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Monthly Revenue', 'EGP ' . number_format($revenue, 2))
                ->description('Total income')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Monthly Expenses', 'EGP ' . number_format($expenses, 2))
                ->description('Total costs')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Net Profit', 'EGP ' . number_format($profit, 2))
                ->description('After expenses')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color($profit >= 0 ? 'success' : 'danger'),
        ];
    }
}
