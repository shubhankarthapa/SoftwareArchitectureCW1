<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use App\Services\RoomTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    protected $roomTypeService;

    public function __construct(RoomTypeService $roomTypeService)
    {
        $this->roomTypeService = $roomTypeService;
    }
    public function getAllHotels(): JsonResponse
    {
        $hotels = Hotel::with(['roomTypes', 'rooms'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Hotels fetched successfully',
            'data' => $hotels
        ]);
    }

    public function getHotel($hotelId): JsonResponse
    {
        $hotel = Hotel::with(['roomTypes', 'rooms'])->find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Hotel fetched successfully',
            'data' => $hotel
        ]);
    }

    public function getHotelRooms($hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }
        
        $rooms = $hotel->rooms()->with('roomType')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Hotel rooms fetched successfully',
            'data' => $rooms
        ]);
    }

    public function getAvailableRooms(Request $request, $hotelId): JsonResponse
    {
        $request->validate([
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }

        $availableRooms = $hotel->rooms()
            ->with('roomType')
            ->get()
            ->filter(function ($room) use ($request) {
                return $room->isAvailable($request->check_in, $request->check_out);
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'message' => 'Available rooms fetched successfully',
            'data' => $availableRooms
        ]);
    }

    public function searchHotels(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $hotels = Hotel::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('address', 'like', '%' . $request->q . '%')
            ->orWhere('description', 'like', '%' . $request->q . '%')
            ->with(['roomTypes', 'rooms'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Hotels fetched successfully',
            'data' => $hotels
        ]);
    }

    public function initializeHotels(): JsonResponse
    {
        // Create default hotels if they don't exist
        $hotels = [
            [
                'name' => 'Grand Hotel',
                'address' => '123 Main Street, City Center',
                'description' => 'Luxury hotel in the heart of the city',
                'rating' => 4.5,
                'price_range' => '$$$',
            ],
            [
                'name' => 'Seaside Resort',
                'address' => '456 Beach Road, Coastal Area',
                'description' => 'Beautiful beachfront resort with ocean views',
                'rating' => 4.8,
                'price_range' => '$$$$',
            ],
            [
                'name' => 'Business Inn',
                'address' => '789 Business District, Downtown',
                'description' => 'Modern business hotel with conference facilities',
                'rating' => 4.2,
                'price_range' => '$$',
            ],
        ];

        $createdHotels = [];
        foreach ($hotels as $hotelData) {
            $hotel = Hotel::firstOrCreate(
                ['name' => $hotelData['name']],
                $hotelData
            );
            
            // Initialize default room types for each hotel if they don't exist
            if ($hotel->roomTypes()->count() === 0) {
                $this->roomTypeService->initializeDefaultRoomTypes($hotel->id);
            }
            
            $createdHotels[] = $hotel->load('roomTypes');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Hotels initialized successfully with default room types',
            'data' => $createdHotels
        ]);
    }

    /**
     * Get room type statistics for a hotel
     */
    public function getHotelRoomTypeStats($hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }

        $stats = $this->roomTypeService->getRoomTypeStats($hotelId);

        return response()->json([
            'status' => 'success',
            'message' => 'Room type statistics fetched successfully',
            'data' => $stats
        ]);
    }

    public function addHotel(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'rating' => 'required|numeric|min:1|max:5',
            'price_range' => 'required|string|max:255',
        ]);

        $hotel = Hotel::create([
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
            'rating' => $request->rating,
            'price_range' => $request->price_range,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Hotel added successfully',
            'data' => $hotel
        ]);
    }
}
