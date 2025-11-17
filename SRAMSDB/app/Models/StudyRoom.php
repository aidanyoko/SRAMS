<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudyRoom extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'room_number',
        'name',
        'slug',
        'seat_capacity',
    ];

    public function seats()
    {
        return $this->hasMany(RoomSeat::class);
    }
}
