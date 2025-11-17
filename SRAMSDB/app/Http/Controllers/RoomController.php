<?php

namespace App\Http\Controllers;

use App\Models\RoomSeat;
use App\Models\StudyRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rooms = StudyRoom::with(['seats' => fn ($query) => $query->orderBy('seat_number')])
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'rooms' => $rooms->map(fn ($room) => $this->formatRoom($room, $request->user()?->id))->values(),
        ]);
    }

    public function reserve(Request $request, StudyRoom $room): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'seat_number' => ['required', 'integer', 'min:1', 'max:' . $room->seat_capacity],
        ]);

        $roomData = DB::transaction(function () use ($room, $validated, $user) {
            $seat = $room->seats()
                ->where('seat_number', $validated['seat_number'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$seat->isAvailable()) {
                throw ValidationException::withMessages([
                    'seat_number' => 'That seat is no longer available.',
                ]);
            }

            $seat->status = RoomSeat::STATUS_RESERVED;
            $seat->reserved_by_user_id = $user->id;
            $seat->reserved_by_email = $user->email;
            $seat->reserved_at = now();
            $seat->save();

            $room->load(['seats' => fn ($query) => $query->orderBy('seat_number')]);

            return $this->formatRoom($room, $user->id);
        });

        return response()->json([
            'room' => $roomData,
            'message' => sprintf('Seat S%s reserved successfully.', $validated['seat_number']),
        ]);
    }

    public function release(Request $request, StudyRoom $room): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'seat_number' => ['required', 'integer', 'min:1', 'max:' . $room->seat_capacity],
        ]);

        $roomData = DB::transaction(function () use ($room, $validated, $user) {
            $seat = $room->seats()
                ->where('seat_number', $validated['seat_number'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($seat->reserved_by_user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'seat_number' => 'You can only release seats you have reserved.',
                ]);
            }

            $seat->status = RoomSeat::STATUS_AVAILABLE;
            $seat->reserved_by_user_id = null;
            $seat->reserved_by_email = null;
            $seat->reserved_at = null;
            $seat->save();

            $room->load(['seats' => fn ($query) => $query->orderBy('seat_number')]);

            return $this->formatRoom($room, $user->id);
        });

        return response()->json([
            'room' => $roomData,
            'message' => 'Reservation released.',
        ]);
    }

    private function formatRoom(StudyRoom $room, ?int $currentUserId): array
    {
        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'name' => $room->name,
            'slug' => $room->slug,
            'seat_capacity' => $room->seat_capacity,
            'seats' => $room->seats
                ->sortBy('seat_number')
                ->values()
                ->map(fn ($seat) => $this->formatSeat($seat, $currentUserId))
                ->all(),
        ];
    }

    private function formatSeat(RoomSeat $seat, ?int $currentUserId): array
    {
        return [
            'id' => $seat->id,
            'seat_number' => $seat->seat_number,
            'status' => $seat->status,
            'reserved_by_email' => $seat->reserved_by_email,
            'reserved_at' => optional($seat->reserved_at)?->toIso8601String(),
            'is_owned_by_viewer' => $currentUserId !== null && $seat->reserved_by_user_id === $currentUserId,
        ];
    }
}
