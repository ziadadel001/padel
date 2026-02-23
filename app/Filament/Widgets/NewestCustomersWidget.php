<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NewestCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->latest()
                    ->limit(3)
            )
            ->heading('Newest 3 Customers')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer Name')
                    ->weight('bold')
                    ->icon('heroicon-o-user-plus'),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Phone Number'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined At')
                    ->dateTime('d M Y, g:i A')
                    ->color('gray')
                    ->alignRight(),
            ])
            ->paginated(false);
    }
}
