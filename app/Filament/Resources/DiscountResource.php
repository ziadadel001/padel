<?php

namespace App\Filament\Resources;

use App\Exports\DiscountsExport;
use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Discount Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed'      => 'Fixed Amount',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'percentage' => 'info',
                        'fixed'      => 'warning',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn($state, Discount $record) => $record->type === 'percentage' ? "{$state}%" : "EGP {$state}")
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->action(function () {
                        return Excel::download(new DiscountsExport, 'discounts-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn(Discount $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(Discount $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Discount $record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn(Discount $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
