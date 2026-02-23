<?php

namespace App\Filament\Resources\BookingResource\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingsChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue (Last 7 Days)';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getData(): array
    {
        $today = now()->endOfDay();
        $lastWeek = now()->subDays(6)->startOfDay();

        $bookings = Booking::whereBetween('date', [$lastWeek, $today])
            ->selectRaw('DATE(date) as format_date, SUM(total_price) as sum_price')
            ->groupBy('format_date')
            ->orderBy('format_date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        // Fill missing days with 0
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');

            $dayData = $bookings->firstWhere('format_date', $date);
            $data[] = $dayData ? (float) $dayData->sum_price : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (EGP)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)', // Emerald 500
                    'borderColor' => '#10b981',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
