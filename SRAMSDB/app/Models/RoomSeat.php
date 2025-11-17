<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSeat extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_TAKEN = 'taken';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'study_room_id',
        'seat_number',
        'status',
        'reserved_by_user_id',
        'reserved_by_email',
        'reserved_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(StudyRoom::class, 'study_room_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
