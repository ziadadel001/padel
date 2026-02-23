<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return Expense::query()
            ->with('user')
            ->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Title',
            'Amount (EGP)',
            'Date',
            'Notes',
            'Added By',
            'Created At',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->title,
            number_format($expense->amount, 2),
            $expense->date->format('Y-m-d'),
            $expense->notes ?? '—',
            $expense->user ? $expense->user->name : '—',
            $expense->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
