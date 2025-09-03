<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\Hotel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomTypeService
{
    /**
     * Create a new room type for a hotel
     */
    public function createRoomType(int $hotelId, array $data): RoomType
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            throw new \Exception('Hotel not found');
        }

        // Validate that room type name is unique within the hotel
        $existingRoomType = RoomType::where('hotel_id', $hotelId)
            ->where('name', $data['name'])
            ->first();

        if ($existingRoomType) {
            throw ValidationException::withMessages([
                'name' => ['A room type with this name already exists for this hotel.']
            ]);
        }

        return DB::transaction(function () use ($hotelId, $data) {
            return RoomType::create([
                'hotel_id' => $hotelId,
                'name' => $data['name'],
                'description' => $data['description'],
                'price_per_night' => $data['price_per_night'],
                'capacity' => $data['capacity'],
                'amenities' => $data['amenities'] ?? []
            ]);
        });
    }

    /**
     * Update an existing room type
     */
    public function updateRoomType(int $roomTypeId, array $data): RoomType
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            throw new \Exception('Room type not found');
        }

        // If name is being updated, check for uniqueness within the hotel
        if (isset($data['name']) && $data['name'] !== $roomType->name) {
            $existingRoomType = RoomType::where('hotel_id', $roomType->hotel_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $roomTypeId)
                ->first();

            if ($existingRoomType) {
                throw ValidationException::withMessages([
                    'name' => ['A room type with this name already exists for this hotel.']
                ]);
            }
        }

        return DB::transaction(function () use ($roomType, $data) {
            $roomType->update($data);
            return $roomType->fresh();
        });
    }

    /**
     * Delete a room type
     */
    public function deleteRoomType(int $roomTypeId): bool
    {
        $roomType = RoomType::find($roomTypeId);
        
        if (!$roomType) {
            throw new \Exception('Room type not found');
        }

        // Check if there are any rooms of this type
        if ($roomType->rooms()->count() > 0) {
            throw new \Exception('Cannot delete room type. There are existing rooms of this type.');
        }

        return DB::transaction(function () use ($roomType) {
            return $roomType->delete();
        });
    }

    /**
     * Get room types for a specific hotel with optional filtering
     */
    public function getHotelRoomTypes(int $hotelId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = RoomType::where('hotel_id', $hotelId);

        // Apply filters
        if (isset($filters['min_price'])) {
            $query->where('price_per_night', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price_per_night', '<=', $filters['max_price']);
        }

        if (isset($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }

        if (isset($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        return $query->with(['hotel', 'rooms'])->get();
    }

    /**
     * Search room types across all hotels
     */
    public function searchRoomTypes(string $searchTerm, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = RoomType::where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%')
              ->orWhereJsonContains('amenities', $searchTerm);
        });

        // Apply additional filters
        if (isset($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price_per_night', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price_per_night', '<=', $filters['max_price']);
        }

        if (isset($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }

        return $query->with(['hotel', 'rooms'])->get();
    }

    /**
     * Get room type statistics for a hotel
     */
    public function getRoomTypeStats(int $hotelId): array
    {
        $roomTypes = RoomType::where('hotel_id', $hotelId)->with('rooms')->get();

        $stats = [
            'total_room_types' => $roomTypes->count(),
            'total_rooms' => $roomTypes->sum(function ($roomType) {
                return $roomType->rooms->count();
            }),
            'price_range' => [
                'min' => $roomTypes->min('price_per_night'),
                'max' => $roomTypes->max('price_per_night'),
                'average' => $roomTypes->avg('price_per_night')
            ],
            'capacity_range' => [
                'min' => $roomTypes->min('capacity'),
                'max' => $roomTypes->max('capacity')
            ],
            'room_types_by_capacity' => $roomTypes->groupBy('capacity')->map->count()
        ];

        return $stats;
    }

    /**
     * Initialize default room types for a hotel
     */
    public function initializeDefaultRoomTypes(int $hotelId): array
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            throw new \Exception('Hotel not found');
        }

        $defaultRoomTypes = [
            [
                'name' => 'Standard Room',
                'description' => 'Comfortable standard room with basic amenities',
                'price_per_night' => 100.00,
                'capacity' => 2,
                'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Private Bathroom']
            ],
            [
                'name' => 'Deluxe Room',
                'description' => 'Spacious deluxe room with premium amenities',
                'price_per_night' => 150.00,
                'capacity' => 2,
                'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Private Bathroom', 'Mini Bar', 'Room Service']
            ],
            [
                'name' => 'Suite',
                'description' => 'Luxurious suite with separate living area',
                'price_per_night' => 250.00,
                'capacity' => 4,
                'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Private Bathroom', 'Mini Bar', 'Room Service', 'Balcony', 'Jacuzzi']
            ]
        ];

        $createdRoomTypes = [];

        foreach ($defaultRoomTypes as $roomTypeData) {
            $roomType = $this->createRoomType($hotelId, $roomTypeData);
            $createdRoomTypes[] = $roomType;
        }

        return $createdRoomTypes;
    }
}
