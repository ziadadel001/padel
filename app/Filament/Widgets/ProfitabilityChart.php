<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;

class ProfitabilityChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue vs Expenses (All Time)';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getData(): array
    {
        $totalRevenue = Booking::sum('total_price');
        $totalExpenses = Expense::sum('amount');

        // Calculate net profit. If negative, cap visual revenue portion to 0.
        $netProfit = max(0, $totalRevenue - $totalExpenses);

        return [
            'datasets' => [
                [
                    'label' => 'Profitability breakdown',
                    'data' => [$netProfit, $totalExpenses],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)', // emerald-500 for Net Profit
                        'rgba(239, 68, 68, 0.8)',  // red-500 for Expenses
                    ],
                    'borderColor' => [
                        '#10b981',
                        '#ef4444',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Net Profit', 'Expenses'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
