<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Illuminate\Database\Eloquent\Builder;

class BookingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected ?Builder $query = null) {}

    public function query()
    {
        if ($this->query) {
            return $this->query->with(['discount', 'user']);
        }

        return Booking::query()
            ->with(['discount', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time');
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer Name',
            'Mobile',
            'Date',
            'Start Time',
            'End Time',
            'Hours',
            'Discount',
            'Hour Price (EGP)',
            'Discount Amount (EGP)',
            'Total Price (EGP)',
            'Created By',
            'Fixed (Recurring)',
            'Notes',
            'Created At',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->id,
            $booking->customer_name,
            $booking->mobile ?? '—',
            $booking->date->format('Y-m-d'),
            substr($booking->start_time, 0, 5),
            substr($booking->end_time, 0, 5),
            $booking->hours,
            $booking->discount ? $booking->discount->name : '—',
            number_format($booking->hour_price, 2),
            number_format($booking->discount_amount, 2),
            number_format($booking->total_price, 2),
            $booking->user ? $booking->user->name : '—',
            $booking->is_recurring ? 'Yes' : 'No',
            $booking->notes ?? '—',
            $booking->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
