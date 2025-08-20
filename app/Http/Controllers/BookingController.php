<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function createBooking(Request $request): JsonResponse
    {
        $request->validate([
            'hotel_id' => 'required|integer|exists:hotels,id',
            'room_id' => 'required|integer|exists:rooms,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'total_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $room = Room::find($request->room_id);
            
            // Check if room is available
            if (!$room->isAvailable($request->check_in, $request->check_out)) {
                return response()->json(['error' => 'Room is not available for selected dates'], 400);
            }

            // Check if user has sufficient balance
            $user = $request->user();
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'USD']
            );

            if ($wallet->balance < $request->total_amount) {
                return response()->json(['error' => 'Insufficient balance in wallet'], 400);
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'hotel_id' => $request->hotel_id,
                'room_id' => $request->room_id,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'total_amount' => $request->total_amount,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            // Deduct amount from wallet
            $wallet->decrement('balance', $request->total_amount);

            // Create transaction record
            $wallet->transactions()->create([
                'type' => 'booking_payment',
                'amount' => $request->total_amount,
                'description' => "Hotel booking #{$booking->id}",
                'reference_id' => "BOOKING_{$booking->id}",
                'status' => 'completed',
            ]);

            // Update payment status
            $booking->update(['payment_status' => 'paid']);

            DB::commit();

            return response()->json([
                'booking' => $booking->load(['hotel', 'room', 'room.roomType']),
                'message' => 'Booking created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Booking creation failed: ' . $e->getMessage()], 500);
        }
    }

    public function getBooking($bookingId): JsonResponse
    {
        $booking = Booking::with(['hotel', 'room', 'room.roomType'])->find($bookingId);
        
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        
        // Check if user owns this booking
        if ($booking->user_id != request()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($booking);
    }

    public function getUserBookings(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['hotel', 'room', 'room.roomType'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    public function cancelBooking($bookingId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $booking = Booking::find($bookingId);
            
            if (!$booking) {
                return response()->json(['error' => 'Booking not found'], 404);
            }
            
            // Check if user owns this booking
            if ($booking->user_id != request()->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($booking->status === 'cancelled') {
                return response()->json(['error' => 'Booking is already cancelled'], 400);
            }

            // Update booking status
            $booking->update(['status' => 'cancelled']);

            // Refund amount to wallet
            $wallet = Wallet::where('user_id', $booking->user_id)->first();
            if ($wallet) {
                $wallet->increment('balance', $booking->total_amount);
                
                // Create refund transaction
                $wallet->transactions()->create([
                    'type' => 'credit',
                    'amount' => $booking->total_amount,
                    'description' => "Refund for cancelled booking #{$booking->id}",
                    'reference' => "REFUND_{$booking->id}",
                    'status' => 'completed',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Booking cancelled successfully',
                'refund_amount' => $booking->total_amount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Booking cancellation failed: ' . $e->getMessage()], 500);
        }
    }

    public function getHotelBookings($hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);
        
        if (!$hotel) {
            return response()->json(['error' => 'Hotel not found'], 404);
        }

        $bookings = $hotel->bookings()
            ->with(['user', 'room', 'room.roomType'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }
}
