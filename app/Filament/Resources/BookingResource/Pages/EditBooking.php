<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['start_time']) && isset($data['hours'])) {
            $data['end_time'] = Carbon::createFromFormat('H:i:s', $data['start_time'])
                ->addMinutes((float) $data['hours'] * 60)
                ->format('H:i:s');
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $proposedStart = Carbon::parse($data['date'] . ' ' . $data['start_time']);

        // Only get original if string structure exists
        $originalDate = is_string($record->getOriginal('date'))
            ? $record->getOriginal('date')
            : \Illuminate\Support\Carbon::parse($record->getOriginal('date'))->format('Y-m-d');

        $originalStart = Carbon::parse($originalDate . ' ' . $record->getOriginal('start_time'));

        // Prevent rescheduling to a past time, but allow editing existing past bookings
        if ($proposedStart->notEqualTo($originalStart) && $proposedStart->isPast() && ! auth()->user()?->hasRole('admin')) {
            Notification::make()
                ->title('Invalid Time')
                ->body('You cannot reschedule a booking to a time in the past.')
                ->danger()
                ->send();

            $this->halt();
        }


        if (! Booking::isSlotAvailable($data['date'], $data['start_time'], $data['end_time'], $record->id)) {
            Notification::make()
                ->title('Time Slot Unavailable')
                ->body('This time slot overlaps with an existing booking. Please choose a different time.')
                ->danger()
                ->send();

            $this->halt();
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
