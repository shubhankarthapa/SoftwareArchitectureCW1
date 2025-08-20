<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class HotelService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.hotel_service.url', 'http://localhost:8002');
    }

    public function getAllHotels()
    {
        return Cache::remember('all_hotels', 300, function () {
            $response = Http::get("{$this->baseUrl}/api/hotels");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }

    public function getHotel($hotelId)
    {
        $cacheKey = "hotel_{$hotelId}";
        
        return Cache::remember($cacheKey, 300, function () use ($hotelId) {
            $response = Http::get("{$this->baseUrl}/api/hotels/{$hotelId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }

    public function getHotelRooms($hotelId)
    {
        $cacheKey = "hotel_rooms_{$hotelId}";
        
        return Cache::remember($cacheKey, 300, function () use ($hotelId) {
            $response = Http::get("{$this->baseUrl}/api/hotels/{$hotelId}/rooms");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }

    public function getAvailableRooms($hotelId, $checkIn, $checkOut)
    {
        $cacheKey = "available_rooms_{$hotelId}_{$checkIn}_{$checkOut}";
        
        return Cache::remember($cacheKey, 60, function () use ($hotelId, $checkIn, $checkOut) {
            $response = Http::get("{$this->baseUrl}/api/hotels/{$hotelId}/available-rooms", [
                'check_in' => $checkIn,
                'check_out' => $checkOut
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }

    public function searchHotels($query)
    {
        $cacheKey = "hotel_search_" . md5($query);
        
        return Cache::remember($cacheKey, 300, function () use ($query) {
            $response = Http::get("{$this->baseUrl}/api/hotels/search", ['q' => $query]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }
}
