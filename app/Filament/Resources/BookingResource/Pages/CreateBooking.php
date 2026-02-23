<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Ensure end_time is calculated
        if (isset($data['start_time']) && isset($data['hours'])) {
            $data['end_time'] = Carbon::createFromFormat('H:i:s', $data['start_time'])
                ->addMinutes((float) $data['hours'] * 60)
                ->format('H:i:s');
        }

        // If this is a recurring booking, assign a group ID now so handleRecordCreation can use it
        if (!empty($data['is_recurring'])) {
            $data['recurring_group_id'] = (string) Str::uuid();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $proposedStart = Carbon::parse($data['date'] . ' ' . $data['start_time']);

        // Prevent booking in the past
        if ($proposedStart->isPast() && ! auth()->user()?->hasRole('admin')) {
            Notification::make()
                ->title('Invalid Time')
                ->body('You cannot book an appointment in the past.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validate no overlap before creating
        if (! Booking::isSlotAvailable($data['date'], $data['start_time'], $data['end_time'])) {
            Notification::make()
                ->title('Time Slot Unavailable')
                ->body('This time slot overlaps with an existing booking. Please choose a different time.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Create the base booking
        $booking = parent::handleRecordCreation($data);

        // If recurring, generate the next 3 weekly occurrences (4 weeks total / 1 month)
        if (!empty($data['is_recurring']) && !empty($data['recurring_group_id'])) {
            $this->generateRecurringOccurrences($booking, $data);
        }

        return $booking;
    }

    protected function generateRecurringOccurrences(Booking $baseBooking, array $data): void
    {
        $created = 0;
        $skipped = 0;
        $baseDate = Carbon::parse($data['date']);

        // Generate weeks 2 through 4 (the base booking is week 1)
        for ($week = 1; $week <= 3; $week++) {
            $nextDate = $baseDate->copy()->addWeeks($week);
            $dateStr  = $nextDate->toDateString();

            // Skip if this slot conflicts with an existing booking
            if (!Booking::isSlotAvailable($dateStr, $data['start_time'], $data['end_time'])) {
                $skipped++;
                continue;
            }

            Booking::create([
                'customer_id'        => $baseBooking->customer_id,
                'customer_name'      => $data['customer_name'],
                'mobile'             => $data['mobile'] ?? null,
                'date'               => $dateStr,
                'start_time'         => $data['start_time'],
                'end_time'           => $data['end_time'],
                'hours'              => $data['hours'],
                'discount_id'        => $data['discount_id'] ?? null,
                'hour_price'         => $data['hour_price'],
                'discount_amount'    => $data['discount_amount'],
                'total_price'        => $data['total_price'],
                'user_id'            => $data['user_id'],
                'notes'              => $data['notes'] ?? null,
                'is_recurring'       => true,
                'recurring_group_id' => $data['recurring_group_id'],
            ]);

            $created++;
        }

        // Update the base booking with the group ID (it was already set in mutate, just confirm)
        $baseBooking->update([
            'recurring_group_id' => $data['recurring_group_id'],
            'is_recurring'       => true,
        ]);

        $message = "Fixed booking created! Generated {$created} weekly occurrences.";
        if ($skipped > 0) {
            $message .= " {$skipped} week(s) were skipped due to existing conflicts.";
        }

        Notification::make()
            ->title('Fixed Booking Active')
            ->body($message)
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
