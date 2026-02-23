<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Filament\Resources\BookingResource;

class Schedule extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.schedule';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Daily Schedule';

    public ?string $selectedDate = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
        $this->form->fill(['date' => $this->selectedDate]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date')
                    ->label('Select Date')
                    ->default(today())
                    ->live(),
            ])
            ->statePath('data');
    }

    public function updatedData($value, $key): void
    {
        if ($key === 'date') {
            $this->selectedDate = $value;
        }
    }

    public function getBookings(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        if (! $this->selectedDate) {
            return collect();
        }

        // Ensure date is in Y-m-d format for the database
        $date = \Illuminate\Support\Carbon::parse($this->selectedDate)->toDateString();

        return Booking::getBookedSlotsForDate($date);
    }

    public function getTimeSlots(): array
    {
        if (! $this->selectedDate) {
            return [];
        }

        $slots   = [];
        $bookings = $this->getBookings();

        // 00:00 to 23:30 in 30-minute steps
        for ($hour = 0; $hour <= 23; $hour++) {
            foreach (['00', '30'] as $minute) {
                // Use a full datetime string for comparison to handle midnight crossings
                $timeStr = sprintf('%02d:%s:00', $hour, $minute);
                $currentSlotStart = \Illuminate\Support\Carbon::parse($this->selectedDate . ' ' . $timeStr);

                $label   = $currentSlotStart->format('g:i A');

                $booking = $bookings->first(function ($b) use ($currentSlotStart) {
                    $bStart = \Illuminate\Support\Carbon::parse($b->date->toDateString() . ' ' . $b->start_time);
                    $bEnd   = \Illuminate\Support\Carbon::parse($b->date->toDateString() . ' ' . $b->end_time);

                    if ($bEnd->lessThanOrEqualTo($bStart)) {
                        $bEnd->addDay();
                    }

                    return $currentSlotStart->greaterThanOrEqualTo($bStart) && $currentSlotStart->lessThan($bEnd);
                });

                $status = $booking ? 'booked' : 'free';

                // Check if this specific slot is the start of the booking OR 
                // if it's the first slot of the day that is covered by a booking from yesterday
                $isStart = false;
                if ($booking) {
                    $bStart = \Illuminate\Support\Carbon::parse($booking->date->toDateString() . ' ' . $booking->start_time);

                    // It's the start if it matches bStart, OR if this is 00:00 and bStart was before 00:00
                    $dayStart = \Illuminate\Support\Carbon::parse($this->selectedDate . ' 00:00:00');
                    if ($currentSlotStart->equalTo($bStart)) {
                        $isStart = true;
                    } elseif ($currentSlotStart->equalTo($dayStart) && $bStart->lessThan($dayStart)) {
                        $isStart = true;
                    }
                }

                if ($status === 'booked' && !$isStart) {
                    continue;
                }

                $startedYesterday = false;
                if ($booking && $isStart && $bStart->lessThan(\Illuminate\Support\Carbon::parse($this->selectedDate . ' 00:00:00'))) {
                    $startedYesterday = true;
                }

                $slots[] = [
                    'id'               => 'slot-' . md5($this->selectedDate . '-' . $label),
                    'hour'             => $label,
                    'status'           => $status,
                    'booking'          => $booking,
                    'isStart'          => $isStart,
                    'startedYesterday' => $startedYesterday,
                ];
            }
        }

        return $slots;
    }

    public function createBookingAction(): Action
    {
        return Action::make('createBooking')
            ->label('New Booking')
            ->model(Booking::class)
            ->mountUsing(function ($form, array $arguments) {
                $form->fill([
                    'date' => $arguments['date'] ?? today()->toDateString(),
                    'start_time' => $arguments['start_time'] ?? null,
                ]);
            })
            ->form(fn(Form $form) => BookingResource::form($form))
            ->slideOver()
            ->action(function (array $data) {
                $proposedStart = \Illuminate\Support\Carbon::parse($data['date'] . ' ' . $data['start_time']);
                if ($proposedStart->isPast() && ! auth()->user()?->hasRole('admin')) {
                    \Filament\Notifications\Notification::make()
                        ->title('Invalid Time')
                        ->body('You cannot book an appointment in the past.')
                        ->danger()
                        ->send();
                    return;
                }

                Booking::create($data);
                $this->dispatch('refreshSchedule');
            });
    }

    public function editBookingAction(): Action
    {
        return Action::make('editBooking')
            ->label('Edit Booking')
            ->model(Booking::class)
            ->record(fn(array $arguments) => Booking::find($arguments['booking']))
            ->mountUsing(function ($form, array $arguments) {
                $booking = Booking::find($arguments['booking']);
                if ($booking) {
                    $form->fill([
                        'customer_id'        => $booking->customer_id,
                        'customer_name'      => $booking->customer_name,
                        'mobile'             => $booking->mobile,
                        'date'               => $booking->date->toDateString(),
                        'start_time'         => $booking->start_time,
                        'hours'              => (string) $booking->hours,
                        'discount_id'        => $booking->discount_id,
                        'notes'              => $booking->notes,
                        'is_recurring'       => $booking->is_recurring,
                        // Hidden computed fields
                        'hour_price'         => $booking->hour_price,
                        'discount_amount'    => $booking->discount_amount,
                        'total_price'        => $booking->total_price,
                        'end_time'           => $booking->end_time,
                    ]);
                }
            })
            ->form(fn(Form $form) => BookingResource::form($form))
            ->slideOver()
            ->action(function (Booking $record, array $data) {
                $proposedStart = \Illuminate\Support\Carbon::parse($data['date'] . ' ' . $data['start_time']);
                $originalStart = \Illuminate\Support\Carbon::parse($record->date->toDateString() . ' ' . $record->start_time);

                if ($proposedStart->notEqualTo($originalStart) && $proposedStart->isPast() && ! auth()->user()?->hasRole('admin')) {
                    \Filament\Notifications\Notification::make()
                        ->title('Invalid Time')
                        ->body('You cannot reschedule a booking to a time in the past.')
                        ->danger()
                        ->send();
                    return;
                }

                $record->update($data);
                $this->dispatch('refreshSchedule');
            });
    }

    public function openCreateModal(string $date, string $startTime): void
    {
        $this->mountAction('createBooking', ['date' => $date, 'start_time' => $startTime]);
    }

    public function openEditModal(int $bookingId): void
    {
        $this->mountAction('editBooking', ['booking' => $bookingId]);
    }
}
