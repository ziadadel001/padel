<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'notes',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getFavoriteCourtTimeAttribute()
    {
        $mostFrequent = $this->bookings()
            ->select('start_time', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('start_time')
            ->orderBy('total', 'desc')
            ->first();

        return $mostFrequent ? \Carbon\Carbon::parse($mostFrequent->start_time)->format('g:i A') : 'N/A';
    }
}
