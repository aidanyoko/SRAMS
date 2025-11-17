<?php

namespace Database\Seeders;

use App\Models\RoomSeat;
use App\Models\StudyRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudyRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['room_number' => 1, 'name' => 'Room 1', 'seat_capacity' => 6],
            ['room_number' => 2, 'name' => 'Room 2', 'seat_capacity' => 8],
            ['room_number' => 3, 'name' => 'Room 3', 'seat_capacity' => 10],
        ];

        foreach ($rooms as $roomData) {
            $room = StudyRoom::updateOrCreate(
                ['room_number' => $roomData['room_number']],
                [
                    'name' => $roomData['name'],
                    'slug' => Str::slug($roomData['name']),
                    'seat_capacity' => $roomData['seat_capacity'],
                ]
            );

            for ($seatNumber = 1; $seatNumber <= $room->seat_capacity; $seatNumber++) {
                RoomSeat::updateOrCreate(
                    [
                        'study_room_id' => $room->id,
                        'seat_number' => $seatNumber,
                    ],
                    [
                        'status' => RoomSeat::STATUS_AVAILABLE,
                        'reserved_by_user_id' => null,
                        'reserved_by_email' => null,
                        'reserved_at' => null,
                    ]
                );
            }
        }
    }
}
