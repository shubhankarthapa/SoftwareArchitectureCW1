<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RoomTypeController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Hotel initialization (public)
Route::post('/hotels/i/initialize', [HotelController::class, 'initializeHotels']);

// Hotel routes (temporarily public for testing)
Route::get('/hotels', [HotelController::class, 'getAllHotels']);
Route::get('/hotels/search', [HotelController::class, 'searchHotels']);
Route::get('/hotels/{hotelId}', [HotelController::class, 'getHotel']);
Route::get('/hotels/{hotelId}/rooms', [HotelController::class, 'getHotelRooms']);
Route::get('/hotels/{hotelId}/available-rooms', [HotelController::class, 'getAvailableRooms']);
Route::get('/hotels/{hotelId}/room-type-stats', [HotelController::class, 'getHotelRoomTypeStats']);

// Room Type routes (public for now)
Route::get('/room-types', [RoomTypeController::class, 'getAllRoomTypes']);
Route::get('/room-types/search', [RoomTypeController::class, 'searchRoomTypes']);
Route::get('/room-types/{roomTypeId}', [RoomTypeController::class, 'getRoomType']);
Route::get('/hotels/{hotelId}/room-types', [RoomTypeController::class, 'getHotelRoomTypes']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Booking routes
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::get('/bookings/{bookingId}', [BookingController::class, 'getBooking']);
    Route::get('/user/bookings', [BookingController::class, 'getUserBookings']);
    Route::delete('/bookings/{bookingId}', [BookingController::class, 'cancelBooking']);
    Route::get('/hotels/{hotelId}/bookings', [BookingController::class, 'getHotelBookings']);
    
    // Wallet routes
    Route::get('/wallet/balance', [WalletController::class, 'getBalance']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
    
    // Room Type management routes (protected)
    Route::post('/hotels/{hotelId}/room-types', [RoomTypeController::class, 'createRoomType']);
    Route::put('/room-types/{roomTypeId}', [RoomTypeController::class, 'updateRoomType']);
    Route::delete('/room-types/{roomTypeId}', [RoomTypeController::class, 'deleteRoomType']);
});
