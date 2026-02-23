<?php

namespace App\Filament\Resources;

use App\Exports\BookingsExport;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Bookings';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make('Booking Details')
                        ->schema([
                            Forms\Components\Grid::make([
                                'default' => 1,
                                'sm' => 2,
                            ])
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->label('Find Returning Customer (Auto-fill)')
                                        ->relationship('customer', 'name')
                                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name}" . ($record->mobile ? " - {$record->mobile}" : ""))
                                        ->searchable(['name', 'mobile'])
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                            if ($state) {
                                                $customer = \App\Models\Customer::find($state);
                                                if ($customer) {
                                                    $set('customer_name', $customer->name);
                                                    $set('mobile', $customer->mobile);
                                                }
                                            }
                                        })
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 2,
                                        ])
                                        ->hint('Select a past player to instantly fill their details.'),

                                    Forms\Components\TextInput::make('customer_name')
                                        ->label('Customer Name')
                                        ->prefixIcon('heroicon-m-user')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('mobile')
                                        ->label('Mobile Number')
                                        ->prefixIcon('heroicon-m-phone')
                                        ->tel()
                                        ->nullable()
                                        ->columnSpan(1),
                                ]),

                            Forms\Components\Grid::make([
                                'default' => 1,
                                'md' => 3,
                            ])
                                ->schema([
                                    Forms\Components\DatePicker::make('date')
                                        ->label('Booking Date')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->minDate(fn(): ?Carbon => auth()->user()?->hasRole('admin') ? null : today())
                                        ->required()
                                        ->default(fn() => request()->query('date', today()))
                                        ->live()
                                        ->columnSpan([
                                            'default' => 1,
                                            'md' => 1,
                                        ]),

                                    Forms\Components\Select::make('start_time')
                                        ->label('Start Time')
                                        ->prefixIcon('heroicon-m-clock')
                                        ->required()
                                        ->default(fn() => request()->query('start_time'))
                                        ->options(function (Get $get, ?Booking $record) {
                                            $date = $get('date');
                                            if (! $date) return [];

                                            $slots = [];
                                            for ($h = 0; $h < 24; $h++) {
                                                $slots[] = sprintf('%02d:00', $h);
                                                $slots[] = sprintf('%02d:30', $h);
                                            }

                                            $options = [];
                                            $isAdmin = auth()->user()?->hasRole('admin');
                                            foreach ($slots as $slot) {
                                                // Check if the specific 30-minute slot is available
                                                $startTimeStr = $slot . ':00';
                                                $slotEndTimeStr   = Carbon::parse($slot)->addMinutes(30)->format('H:i:s');

                                                $isAvailable = Booking::isSlotAvailable(
                                                    $date,
                                                    $startTimeStr,
                                                    $slotEndTimeStr,
                                                    $record?->id
                                                );

                                                $proposedStart = \Illuminate\Support\Carbon::parse($date . ' ' . $startTimeStr);
                                                $isPast = $proposedStart->isPast();

                                                // Only block past if it's not the record's current time AND user is not admin
                                                $isCurrentTime = false;
                                                if ($record && $record->start_time === $startTimeStr) {
                                                    $recordDateStr = \Illuminate\Support\Carbon::parse($record->date)->format('Y-m-d');
                                                    if ($recordDateStr === $date) {
                                                        $isCurrentTime = true;
                                                    }
                                                }

                                                $label = Carbon::parse($slot)->format('g:i A');
                                                if ($isPast && !$isCurrentTime && !$isAdmin) {
                                                    // Hide passed times for non-admins
                                                    continue;
                                                } elseif ($isAvailable) {
                                                    $options[$startTimeStr] = $label;
                                                } else {
                                                    $options[$startTimeStr] = $label . ' (Booked)';
                                                }
                                            }
                                            return $options;
                                        })
                                        ->disableOptionWhen(fn(string $value, string $label): bool => str_contains($label, 'Booked'))
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculate($get, $set);
                                        })
                                        ->columnSpan([
                                            'default' => 1,
                                            'md' => 1,
                                        ]),

                                    Forms\Components\Select::make('hours')
                                        ->label('Duration')
                                        ->prefixIcon('heroicon-m-arrow-path-rounded-square')
                                        ->required()
                                        ->options(function (Get $get, ?Booking $record) {
                                            $date = $get('date');
                                            $startTime = $get('start_time');

                                            $allOptions = [
                                                '0.5' => '30 Minutes',
                                                '1.0' => '1 Hour',
                                                '1.5' => '1.5 Hours',
                                                '2.0' => '2 Hours',
                                                '2.5' => '2.5 Hours',
                                                '3.0' => '3 Hours',
                                                '4.0' => '4 Hours',
                                            ];

                                            if (! $date || ! $startTime) {
                                                return $allOptions;
                                            }

                                            // Find the next booking on this day
                                            $proposedStart = Carbon::parse("$date $startTime");

                                            $nextBooking = Booking::where('date', $date)
                                                ->where('start_time', '>', $startTime)
                                                ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                ->orderBy('start_time')
                                                ->first();

                                            if (! $nextBooking) {
                                                return $allOptions;
                                            }

                                            $nextStart = Carbon::parse("$date $nextBooking->start_time");
                                            $gapMinutes = $proposedStart->diffInMinutes($nextStart);
                                            $gapHours = $gapMinutes / 60;

                                            return collect($allOptions)
                                                ->filter(fn($label, $value) => (float)$value <= $gapHours)
                                                ->toArray();
                                        })
                                        ->default('1.0')
                                        ->live()
                                        ->rules([
                                            fn(Get $get, ?Booking $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                $date = $get('date');
                                                $startTime = $get('start_time');
                                                if (!$date || !$startTime) return;

                                                $proposedStart = Carbon::parse("$date $startTime");
                                                $proposedEnd = $proposedStart->copy()->addMinutes((float)$value * 60);

                                                $isAvailable = Booking::isSlotAvailable(
                                                    $date,
                                                    $startTime,
                                                    $proposedEnd->format('H:i:s'),
                                                    $record?->id
                                                );

                                                if (!$isAvailable) {
                                                    $fail("This duration overlaps with an existing booking.");
                                                }
                                            },
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculate($get, $set);
                                        })
                                        ->columnSpan([
                                            'default' => 1,
                                            'md' => 1,
                                        ]),
                                ]),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->hintIcon('heroicon-m-chat-bubble-bottom-center-text')
                                ->rows(3)
                                ->nullable()
                                ->columnSpanFull(),

                            Forms\Components\Toggle::make('is_recurring')
                                ->label('Fixed Booking (Repeats for 1 Month)')
                                ->helperText('When enabled, this booking will automatically repeat every week for the next 3 weeks (4 sessions total).')
                                ->default(false)
                                ->columnSpanFull()
                                ->live()
                                ->inline(false),
                        ])->grow(),

                    Forms\Components\Section::make('Payment Summary')
                        ->schema([
                            Forms\Components\Select::make('discount_id')
                                ->label('Discount Code')
                                ->prefixIcon('heroicon-m-ticket')
                                ->relationship('discount', 'name', fn($query) => $query->active())
                                ->getOptionLabelFromRecordUsing(fn(Discount $record) => $record->label)
                                ->nullable()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::recalculate($get, $set);
                                }),

                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('summary_price')
                                        ->label('Final Amount')
                                        ->content(function (Get $get) {
                                            $price = $get('total_price') ?? 0;
                                            return new \Illuminate\Support\HtmlString('<div class="text-4xl font-black text-emerald-600 dark:text-emerald-400">EGP ' . number_format((float)$price, 2) . '</div>');
                                        }),

                                    Forms\Components\Placeholder::make('summary_details')
                                        ->label('Breakdown')
                                        ->content(function (Get $get) {
                                            $hours = (float)($get('hours') ?? 0);
                                            $hourPrice = (float)($get('hour_price') ?? 0);
                                            $discount = (float)($get('discount_amount') ?? 0);
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="text-sm border-t border-gray-100 dark:border-gray-800 pt-2 mt-2 space-y-1">
                                                    <div class="flex justify-between"><span>Base (' . $hours . 'h):</span> <span>' . number_format($hours * $hourPrice, 2) . '</span></div>
                                                    <div class="flex justify-between text-red-500 font-medium"><span>Discount:</span> <span>- ' . number_format($discount, 2) . '</span></div>
                                                </div>
                                            ');
                                        }),
                                ])->compact(),

                            Forms\Components\Hidden::make('hour_price'),
                            Forms\Components\Hidden::make('discount_amount'),
                            Forms\Components\Hidden::make('total_price'),
                            Forms\Components\Hidden::make('end_time'),
                        ])->columnSpan(1),
                ])->from('lg'),
            ])->columns(1);
    }

    protected static function recalculate(Get $get, Set $set): void
    {
        $startTime  = $get('start_time');
        $hours      = (float) $get('hours');
        $discountId = $get('discount_id');
        $hourPrice  = (float) Setting::get('hour_price', 0);

        if (! $startTime || ! $hours) {
            return;
        }

        // Calculate end time
        try {
            $parsedStart = Carbon::parse($startTime);
            $parsedEnd   = $parsedStart->copy()->addMinutes($hours * 60);

            // end_time column is just HH:MM:SS
            $endTime = $parsedEnd->format('H:i:s');
        } catch (\Exception $e) {
            return;
        }

        $subtotal       = $hours * $hourPrice;
        $discountAmount = 0;

        if ($discountId) {
            $discount       = Discount::find($discountId);
            $discountAmount = $discount ? $discount->calculateAmount($subtotal) : 0;
        }

        $totalPrice = max(0, $subtotal - $discountAmount);

        $set('end_time', $endTime);
        $set('hour_price', $hourPrice);
        $set('discount_amount', $discountAmount);
        $set('total_price', $totalPrice);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('From')
                    ->icon('heroicon-m-clock')
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('To')
                    ->icon('heroicon-m-clock')
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Hours')
                    ->suffix('h')
                    ->alignCenter()
                    ->color('info')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('discount.name')
                    ->label('Discount')
                    ->default('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('EGP')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn() => auth()->user()?->hasRole('admin')),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Fixed')
                    ->boolean()
                    ->trueIcon('heroicon-s-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->alignCenter()
                    ->tooltip(fn($record) => $record->is_recurring ? 'Fixed monthly booking (4 weeks)' : null),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Today')
                    ->query(fn(Builder $query) => $query->whereDate('date', today()))
                    ->toggle(),

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
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(collect(range(date('Y') - 2, date('Y') + 1))->mapWithKeys(fn($y) => [$y => $y]))
                            ->default(date('Y')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['month'], fn($q, $v) => $q->whereMonth('date', $v))
                            ->when($data['year'], fn($q, $v) => $q->whereYear('date', $v));
                    }),

                Filter::make('has_discount')
                    ->label('Has Discount')
                    ->query(fn(Builder $query) => $query->whereNotNull('discount_id'))
                    ->toggle(),

                SelectFilter::make('discount_id')
                    ->label('Specific Discount')
                    ->relationship('discount', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('hours')
                    ->label('Duration')
                    ->options([
                        '0.5' => '30 Minutes',
                        '1.0' => '1 Hour',
                        '1.5' => '1.5 Hours',
                        '2.0' => '2 Hours',
                        '2.5' => '2.5 Hours',
                        '3.0' => '3 Hours',
                        '4.0' => '4 Hours',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->action(function () {
                        abort_if(!auth()->user()?->hasRole('admin'), 403, 'Unauthorized.');
                        return Excel::download(new BookingsExport, 'bookings-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ])
            ->emptyStateHeading('No bookings found')
            ->emptyStateDescription('Start by creating a new booking from the schedule or the button below.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Cancel Booking')
                    ->modalDescription(fn(Booking $record) => $record->is_recurring
                        ? 'This is a fixed booking. How would you like to cancel it?'
                        : 'Are you sure you want to cancel this booking?')
                    ->form(fn(Booking $record) => $record->is_recurring ? [
                        Forms\Components\Radio::make('cancel_mode')
                            ->label('Cancellation Options')
                            ->options([
                                'single' => 'Just this session (' . $record->date->format('d M Y') . ')',
                                'future' => 'This and all upcoming sessions in this group',
                                'all'    => 'Cancel the entire fixed month group',
                            ])
                            ->default('single')
                            ->required(),
                    ] : [])
                    ->action(function (Booking $record, array $data): void {
                        if (!$record->is_recurring || !isset($data['cancel_mode'])) {
                            $record->delete();
                            return;
                        }

                        $groupId = $record->recurring_group_id;
                        $recordDate = $record->date;

                        switch ($data['cancel_mode']) {
                            case 'all':
                                Booking::where('recurring_group_id', $groupId)->delete();
                                break;
                            case 'future':
                                Booking::where('recurring_group_id', $groupId)
                                    ->where('date', '>=', $recordDate)
                                    ->delete();
                                break;
                            case 'single':
                            default:
                                $record->delete();
                                break;
                        }

                        Notification::make()
                            ->title('Success')
                            ->body('The selected bookings have been cancelled.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->color('primary')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            abort_if(!auth()->user()?->hasRole('admin'), 403, 'Unauthorized.');
                            $query = Booking::query()->whereIn('id', $records->pluck('id'));
                            return Excel::download(new BookingsExport($query), 'selected-bookings-' . now()->format('Y-m-d') . '.xlsx');
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            BookingResource\Widgets\BookingsChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit'   => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
