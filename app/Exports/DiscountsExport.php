<?php

namespace App\Exports;

use App\Models\Discount;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DiscountsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return Discount::query()->orderBy('name');
    }

    public function headings(): array
    {
        return [
            '#',
            'Name',
            'Type',
            'Value',
            'Active',
            'Created At',
        ];
    }

    public function map($discount): array
    {
        return [
            $discount->id,
            $discount->name,
            ucfirst($discount->type),
            $discount->type === 'percentage' ? "{$discount->value}%" : "EGP {$discount->value}",
            $discount->is_active ? 'Yes' : 'No',
            $discount->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
