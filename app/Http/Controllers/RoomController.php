<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /**
     * Create a single room for a specific room type
     */
    public function createRoom(Request $request, $roomTypeId): JsonResponse
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        $request->validate([
            'room_number' => 'required|string|max:50',
            'floor' => 'required|integer|min:1|max:100',
            'status' => 'sometimes|string|in:available,occupied,maintenance,reserved',
        ]);

        // Check if room number already exists in the hotel
        $existingRoom = Room::where('hotel_id', $roomType->hotel_id)
            ->where('room_number', $request->room_number)
            ->first();

        if ($existingRoom) {
            return response()->json(['error' => 'Room number already exists in this hotel'], 400);
        }

        $room = Room::create([
            'hotel_id' => $roomType->hotel_id,
            'room_type_id' => $roomTypeId,
            'room_number' => $request->room_number,
            'floor' => $request->floor,
            'status' => $request->status ?? 'available',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Room created successfully',
            'data' => $room->load(['hotel', 'roomType'])
        ], 201);
    }

    /**
     * Bulk create multiple rooms for a specific room type
     */
    public function bulkCreateRooms(Request $request, $roomTypeId): JsonResponse
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        $request->validate([
            'rooms' => 'required|array|min:1|max:50',
            'rooms.*.room_number' => 'required|string|max:50',
            'rooms.*.floor' => 'required|integer|min:1|max:100',
            'rooms.*.status' => 'sometimes|string|in:available,occupied,maintenance,reserved',
            'start_floor' => 'sometimes|integer|min:1|max:100',
            'room_count' => 'sometimes|integer|min:1|max:100',
            'floor_range' => 'sometimes|array',
            'floor_range.start' => 'required_with:floor_range|integer|min:1|max:100',
            'floor_range.end' => 'required_with:floor_range|integer|min:1|max:100',
        ]);

        $createdRooms = [];
        $errors = [];

        // Method 1: Create rooms from provided array
        if ($request->has('rooms')) {
            foreach ($request->rooms as $roomData) {
                try {
                    // Check if room number already exists
                    $existingRoom = Room::where('hotel_id', $roomType->hotel_id)
                        ->where('room_number', $roomData['room_number'])
                        ->first();

                    if ($existingRoom) {
                        $errors[] = "Room number {$roomData['room_number']} already exists";
                        continue;
                    }

                    $room = Room::create([
                        'hotel_id' => $roomType->hotel_id,
                        'room_type_id' => $roomTypeId,
                        'room_number' => $roomData['room_number'],
                        'floor' => $roomData['floor'],
                        'status' => $roomData['status'] ?? 'available',
                    ]);

                    $createdRooms[] = $room;
                } catch (\Exception $e) {
                    $errors[] = "Failed to create room {$roomData['room_number']}: " . $e->getMessage();
                }
            }
        }
        // Method 2: Auto-generate rooms with sequential numbering
        elseif ($request->has('room_count') && $request->has('start_floor')) {
            $roomCount = $request->room_count;
            $startFloor = $request->start_floor;
            $startNumber = 1;

            // Find the highest room number for this room type
            $highestRoom = Room::where('room_type_id', $roomTypeId)
                ->orderByRaw('CAST(room_number AS UNSIGNED) DESC')
                ->first();

            if ($highestRoom && is_numeric($highestRoom->room_number)) {
                $startNumber = (int)$highestRoom->room_number + 1;
            }

            for ($i = 0; $i < $roomCount; $i++) {
                $roomNumber = $startNumber + $i;
                $floor = $startFloor + floor($i / 10); // 10 rooms per floor

                try {
                    $room = Room::create([
                        'hotel_id' => $roomType->hotel_id,
                        'room_type_id' => $roomTypeId,
                        'room_number' => (string)$roomNumber,
                        'floor' => $floor,
                        'status' => 'available',
                    ]);

                    $createdRooms[] = $room;
                } catch (\Exception $e) {
                    $errors[] = "Failed to create room {$roomNumber}: " . $e->getMessage();
                }
            }
        }
        // Method 3: Create rooms for a floor range
        elseif ($request->has('floor_range')) {
            $startFloor = $request->floor_range['start'];
            $endFloor = $request->floor_range['end'];
            $roomsPerFloor = $request->get('rooms_per_floor', 10);

            for ($floor = $startFloor; $floor <= $endFloor; $floor++) {
                for ($roomNum = 1; $roomNum <= $roomsPerFloor; $roomNum++) {
                    $roomNumber = $floor . str_pad($roomNum, 2, '0', STR_PAD_LEFT);

                    try {
                        // Check if room number already exists
                        $existingRoom = Room::where('hotel_id', $roomType->hotel_id)
                            ->where('room_number', $roomNumber)
                            ->first();

                        if ($existingRoom) {
                            continue; // Skip if room already exists
                        }

                        $room = Room::create([
                            'hotel_id' => $roomType->hotel_id,
                            'room_type_id' => $roomTypeId,
                            'room_number' => $roomNumber,
                            'floor' => $floor,
                            'status' => 'available',
                        ]);

                        $createdRooms[] = $room;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to create room {$roomNumber}: " . $e->getMessage();
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($createdRooms) . ' rooms created successfully',
            'data' => [
                'created_rooms' => $createdRooms,
                'total_created' => count($createdRooms),
                'errors' => $errors
            ]
        ], 201);
    }

    /**
     * Get all rooms for a specific room type
     */
    public function getRoomsByType($roomTypeId): JsonResponse
    {
        $roomType = RoomType::with(['hotel'])->find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        $rooms = Room::where('room_type_id', $roomTypeId)
            ->with(['hotel', 'roomType'])
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Rooms fetched successfully',
            'data' => [
                'room_type' => $roomType,
                'rooms' => $rooms,
                'total_rooms' => $rooms->count()
            ]
        ]);
    }

    /**
     * Get a specific room
     */
    public function getRoom($roomId): JsonResponse
    {
        $room = Room::with(['hotel', 'roomType', 'bookings'])->find($roomId);
        
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Room fetched successfully',
            'data' => $room
        ]);
    }

    /**
     * Update a room
     */
    public function updateRoom(Request $request, $roomId): JsonResponse
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        $request->validate([
            'room_number' => 'sometimes|required|string|max:50',
            'floor' => 'sometimes|required|integer|min:1|max:100',
            'status' => 'sometimes|required|string|in:available,occupied,maintenance,reserved',
            'room_type_id' => 'sometimes|required|integer|exists:room_types,id'
        ]);

        // Check if room number already exists (if being changed)
        if ($request->has('room_number') && $request->room_number !== $room->room_number) {
            $existingRoom = Room::where('hotel_id', $room->hotel_id)
                ->where('room_number', $request->room_number)
                ->where('id', '!=', $roomId)
                ->first();

            if ($existingRoom) {
                return response()->json(['error' => 'Room number already exists in this hotel'], 400);
            }
        }

        $room->update($request->only(['room_number', 'floor', 'status', 'room_type_id']));

        return response()->json([
            'status' => 'success',
            'message' => 'Room updated successfully',
            'data' => $room->load(['hotel', 'roomType'])
        ]);
    }

    /**
     * Delete a room
     */
    public function deleteRoom($roomId): JsonResponse
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        // Check if room has active bookings
        $activeBookings = $room->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_out', '>', now())
            ->count();

        if ($activeBookings > 0) {
            return response()->json([
                'error' => 'Cannot delete room. There are active bookings for this room.'
            ], 400);
        }

        $room->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room deleted successfully'
        ]);
    }

    /**
     * Get room statistics for a room type
     */
    public function getRoomTypeStats($roomTypeId): JsonResponse
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        $stats = Room::where('room_type_id', $roomTypeId)
            ->selectRaw('
                COUNT(*) as total_rooms,
                COUNT(CASE WHEN status = "available" THEN 1 END) as available_rooms,
                COUNT(CASE WHEN status = "occupied" THEN 1 END) as occupied_rooms,
                COUNT(CASE WHEN status = "maintenance" THEN 1 END) as maintenance_rooms,
                COUNT(CASE WHEN status = "reserved" THEN 1 END) as reserved_rooms
            ')
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Room statistics fetched successfully',
            'data' => [
                'room_type' => $roomType,
                'statistics' => $stats
            ]
        ]);
    }
}
