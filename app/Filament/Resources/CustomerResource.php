<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Profiling')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mobile')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-calendar-days')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('bookings_sum_total_price')
                    ->label('Lifetime Value')
                    ->money('EGP')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn() => auth()->user()?->hasRole('admin')),

                Tables\Columns\TextColumn::make('favorite_court_time')
                    ->label('Favorite Time')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('quick_book')
                    ->label('Book Now')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->slideOver()
                    ->model(\App\Models\Booking::class)
                    ->form(fn(Form $form) => BookingResource::form($form->model(\App\Models\Booking::class)))
                    ->mountUsing(fn(Form $form, Customer $record) => $form->fill([
                        'customer_id' => $record->id,
                        'customer_name' => $record->name,
                        'mobile' => $record->mobile,
                    ]))
                    ->action(function (array $data) {
                        $data['user_id'] = auth()->id();

                        // Use the shared creation logic or manually create
                        \App\Models\Booking::create($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Booking Created')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Customers are automatically created when you save a booking.')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['bookings'])
            ->withCount('bookings')
            ->withSum('bookings', 'total_price');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
