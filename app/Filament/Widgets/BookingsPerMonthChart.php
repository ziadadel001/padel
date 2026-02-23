<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class BookingsPerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Bookings Per Month (Current Year)';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getData(): array
    {
        $year = now()->year;

        $bookings = Booking::whereYear('date', $year)
            ->selectRaw('MONTH(date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $data = [];
        $labels = [];

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create()->month($i)->format('M');
            $monthData = $bookings->firstWhere('month', $i);
            $data[] = $monthData ? (int) $monthData->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Bookings',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)', // blue-500
                    'borderColor' => '#3b82f6',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
