<?php

namespace App\Filament\Resources;

use App\Exports\ExpensesExport;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    // public static function canViewAny(): bool
    // {
    //     return auth()->user()?->hasRole('admin') ?? false;
    // }

    // public static function canCreate(): bool
    // {
    //     return auth()->user()?->hasRole('admin') ?? false;
    // }

    // public static function canEdit($record): bool
    // {
    //     return auth()->user()?->hasRole('admin') ?? false;
    // }

    // public static function canDelete($record): bool
    // {
    //     return auth()->user()?->hasRole('admin') ?? false;
    // }

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Expense Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('EGP')
                            ->step(0.01)
                            ->minValue(0),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(today()),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EGP')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Added By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Specific Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['date'],
                            fn(Builder $query, $date) => $query->whereDate('date', $date)
                        );
                    }),
                Filter::make('month')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create(null, $m, 1)->format('F')])),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['month'], fn($q, $v) => $q->whereMonth('date', $v));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->action(function () {
                        return Excel::download(new ExpensesExport, 'expenses-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
