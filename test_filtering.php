<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;
use Illuminate\Support\Carbon;

// Clear potential test data
Booking::where('customer_name', 'Test User')->delete();

$date = '2026-03-01';

// 1. Create a booking at 5:00 PM for 2 hours
Booking::create([
    'customer_name' => 'Test User',
    'date' => $date,
    'start_time' => '17:00:00',
    'end_time' => '19:00:00',
    'hours' => 2.0,
    'mobile' => '1234567890',
    'hour_price' => 200,
    'total_price' => 400,
    'user_id' => 1,
]);

echo "Created booking at 5:00 PM for 2 hours.\n";

function getAvailableDurations($date, $startTime)
{
    $allOptions = [
        '0.5' => '30 Minutes',
        '1.0' => '1 Hour',
        '1.5' => '1.5 Hours',
        '2.0' => '2 Hours',
        '2.5' => '2.5 Hours',
        '3.0' => '3 Hours',
        '4.0' => '4 Hours',
    ];

    $proposedStart = Carbon::parse("$date $startTime");
    $nextBooking = Booking::where('date', $date)
        ->where('start_time', '>', $startTime)
        ->orderBy('start_time')
        ->first();

    if (!$nextBooking) {
        return array_keys($allOptions);
    }

    $nextStart = Carbon::parse("$date $nextBooking->start_time");
    $gapMinutes = $proposedStart->diffInMinutes($nextStart);
    $gapHours = $gapMinutes / 60;

    $filtered = array_filter(array_keys($allOptions), fn($v) => (float)$v <= $gapHours);
    return array_values($filtered);
}

echo "\nChecking 4:30 PM start:\n";
print_r(getAvailableDurations($date, '16:30:00'));

echo "\nChecking 4:00 PM start:\n";
print_r(getAvailableDurations($date, '16:00:00'));

echo "\nChecking 7:00 PM start (exactly after the booking):\n";
print_r(getAvailableDurations($date, '19:00:00'));

// Cleanup
Booking::where('customer_name', 'Test User')->delete();
