<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RoomTypeController extends Controller
{
    /**
     * Get all room types for a specific hotel
     */
    public function getHotelRoomTypes($hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }
        
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Room types fetched successfully',
            'data' => $roomTypes
        ]);
    }

    /**
     * Get a specific room type
     */
    public function getRoomType($roomTypeId): JsonResponse
    {
        $roomType = RoomType::with(['hotel', 'rooms'])->find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Room type fetched successfully',
            'data' => $roomType
        ]);
    }

    /**
     * Create a new room type for a hotel
     */
    public function createRoomType(Request $request, $hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1|max:10',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:255'
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotelId,
            'name' => $request->name,
            'description' => $request->description,
            'price_per_night' => $request->price_per_night,
            'capacity' => $request->capacity,
            'amenities' => $request->amenities ?? []
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Room type created successfully',
            'data' => $roomType
        ], 201);
    }

    /**
     * Update an existing room type
     */
    public function updateRoomType(Request $request, $roomTypeId): JsonResponse
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'capacity' => 'sometimes|required|integer|min:1|max:10',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:255'
        ]);

        $roomType->update($request->only([
            'name', 'description', 'price_per_night', 'capacity', 'amenities'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Room type updated successfully',
            'data' => $roomType
        ]);
    }

    /**
     * Delete a room type
     */
    public function deleteRoomType($roomTypeId): JsonResponse
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            return response()->json(['error' => 'Room type not found'], 404);
        }

        // Check if there are any rooms of this type
        if ($roomType->rooms()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete room type. There are existing rooms of this type.'
            ], 400);
        }

        $roomType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room type deleted successfully'
        ]);
    }

    /**
     * Get all room types across all hotels
     */
    public function getAllRoomTypes(): JsonResponse
    {
        $roomTypes = RoomType::with(['hotel', 'rooms'])->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Room types fetched successfully',
            'data' => $roomTypes
        ]);
    }

    /**
     * Search room types by name or amenities
     */
    public function searchRoomTypes(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $roomTypes = RoomType::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('description', 'like', '%' . $request->q . '%')
            ->orWhereJsonContains('amenities', $request->q)
            ->with(['hotel', 'rooms'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Room types fetched successfully',
            'data' => $roomTypes
        ]);
    }
}
