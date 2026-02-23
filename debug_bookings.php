<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;

$b = Booking::find(1);
if ($b) {
    echo "ID: " . $b->id . "\n";
    echo "Raw Date: " . $b->getAttributes()['date'] . "\n";
    echo "Casted Date: " . $b->date->toDateString() . "\n";
    echo "Start Time: " . $b->start_time . "\n";
    echo "End Time: " . $b->end_time . "\n";
} else {
    echo "No booking found with ID 1\n";
}

$all = Booking::where('date', '>=', '2026-02-21')->get();
echo "\nAll Bookings from Feb 21:\n";
foreach ($all as $item) {
    echo "ID: {$item->id} | Date: {$item->date->toDateString()} | Start: {$item->start_time} | End: {$item->end_time}\n";
}
