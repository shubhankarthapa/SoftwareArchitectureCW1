<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get all registered users (paginated)
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = User::query();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $allowedSortFields = ['name', 'email', 'created_at', 'email_verified_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * Get a specific user by ID
     */
    public function getUser($userId): JsonResponse
    {
        $user = User::with(['bookings', 'wallet'])->find($userId);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User fetched successfully',
            'data' => $user
        ]);
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
            'users_with_bookings' => User::has('bookings')->count(),
            'users_with_wallets' => User::has('wallet')->count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'User statistics fetched successfully',
            'data' => $stats
        ]);
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $users = User::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('email', 'like', '%' . $request->q . '%')
            ->limit(20)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'status' => 'success',
            'message' => 'Users found successfully',
            'data' => [
                'users' => $users,
                'total_found' => $users->count()
            ]
        ]);
    }

    /**
     * Update user information (admin only)
     */
    public function updateUser(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $userId,
            'email_verified_at' => 'sometimes|nullable|date',
        ]);

        $user->update($request->only(['name', 'email', 'email_verified_at']));

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser($userId): JsonResponse
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if user has active bookings
        $activeBookings = $user->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_out', '>', now())
            ->count();

        if ($activeBookings > 0) {
            return response()->json([
                'error' => 'Cannot delete user. There are active bookings for this user.'
            ], 400);
        }

        // Delete user's wallet and transactions first
        if ($user->wallet) {
            $user->wallet->transactions()->delete();
            $user->wallet->delete();
        }

        // Delete user's bookings
        $user->bookings()->delete();

        // Delete user
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get users by registration date range
     */
    public function getUsersByDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $users = User::whereBetween('created_at', [
            $request->start_date,
            $request->end_date
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'data' => [
                'users' => $users,
                'total_users' => $users->count(),
                'date_range' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]
            ]
        ]);
    }
}
