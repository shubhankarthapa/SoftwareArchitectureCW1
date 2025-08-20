<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BookingService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.booking_service.url', 'http://localhost:8003');
    }

    public function createBooking($data)
    {
        $response = Http::post("{$this->baseUrl}/api/bookings", $data);
        
        if ($response->successful()) {
            // Clear relevant caches
            Cache::forget("available_rooms_{$data['hotel_id']}_{$data['check_in']}_{$data['check_out']}");
            Cache::forget("user_bookings_{$data['user_id']}");
            return $response->json();
        }
        
        throw new \Exception('Booking creation failed: ' . $response->body());
    }

    public function getBooking($bookingId)
    {
        $cacheKey = "booking_{$bookingId}";
        
        return Cache::remember($cacheKey, 300, function () use ($bookingId) {
            $response = Http::get("{$this->baseUrl}/api/bookings/{$bookingId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }

    public function getUserBookings($userId)
    {
        $cacheKey = "user_bookings_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $response = Http::get("{$this->baseUrl}/api/users/{$userId}/bookings");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }

    public function cancelBooking($bookingId)
    {
        $response = Http::delete("{$this->baseUrl}/api/bookings/{$bookingId}");
        
        if ($response->successful()) {
            // Clear relevant caches
            Cache::forget("booking_{$bookingId}");
            return $response->json();
        }
        
        throw new \Exception('Booking cancellation failed: ' . $response->body());
    }

    public function getHotelBookings($hotelId)
    {
        $cacheKey = "hotel_bookings_{$hotelId}";
        
        return Cache::remember($cacheKey, 300, function () use ($hotelId) {
            $response = Http::get("{$this->baseUrl}/api/hotels/{$hotelId}/bookings");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }
}
