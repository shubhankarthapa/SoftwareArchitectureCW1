<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LoggingService
{
    protected $logsApiUrl;
    protected $applicationName;

    public function __construct()
    {
        $this->logsApiUrl = config('services.logs_service.url', 'http://127.0.0.1:8001/api/logs');
        $this->applicationName =  'Hotel Booking Service';
    }

    /**
     * Send log to the second Laravel app
     */
    public function sendLog(string $level, string $message, array $context = [], ?string $source = null, ?string $userId = null): bool
    {
        try {
            $logData = [
                'application_name' => $this->applicationName,
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'source' => $source,
                'user_id' => $userId,
                'session_id' => session()->getId(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            $response = Http::timeout(5)->post($this->logsApiUrl, $logData);

            if ($response->successful()) {
                return true;
            } else {
                Log::warning('Failed to send log to external service', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'log_data' => $logData
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending log to external service', [
                'error' => $e->getMessage(),
                'log_data' => $logData ?? null
            ]);
            return false;
        }
    }

    /**
     * Log booking events
     */
    public function logBooking(string $action, array $bookingData, ?string $userId = null): bool
    {
        $message = "Booking {$action}";
        $context = [
            'booking_id' => $bookingData['id'] ?? null,
            'hotel_id' => $bookingData['hotel_id'] ?? null,
            'room_id' => $bookingData['room_id'] ?? null,
            'check_in' => $bookingData['check_in'] ?? null,
            'check_out' => $bookingData['check_out'] ?? null,
            'total_amount' => $bookingData['total_amount'] ?? null,
            'status' => $bookingData['status'] ?? null,
            'payment_status' => $bookingData['payment_status'] ?? null,
        ];

        return $this->sendLog('info', $message, $context, 'BookingController', $userId);
    }

    /**
     * Log transaction events
     */
    public function logTransaction(string $action, array $transactionData, ?string $userId = null): bool
    {
        $message = "Transaction {$action}";
        $context = [
            'transaction_id' => $transactionData['id'] ?? null,
            'wallet_id' => $transactionData['wallet_id'] ?? null,
            'type' => $transactionData['type'] ?? null,
            'amount' => $transactionData['amount'] ?? null,
            'currency' => $transactionData['currency'] ?? null,
            'description' => $transactionData['description'] ?? null,
            'reference_id' => $transactionData['reference_id'] ?? null,
            'status' => $transactionData['status'] ?? null,
        ];

        return $this->sendLog('info', $message, $context, 'WalletController', $userId);
    }

    /**
     * Log room events
     */
    public function logRoom(string $action, array $roomData, ?string $userId = null): bool
    {
        $message = "Room {$action}";
        $context = [
            'room_id' => $roomData['id'] ?? null,
            'hotel_id' => $roomData['hotel_id'] ?? null,
            'room_type_id' => $roomData['room_type_id'] ?? null,
            'room_number' => $roomData['room_number'] ?? null,
            'price_per_night' => $roomData['price_per_night'] ?? null,
            'status' => $roomData['status'] ?? null,
        ];

        return $this->sendLog('info', $message, $context, 'RoomController', $userId);
    }

    /**
     * Log hotel events
     */
    public function logHotel(string $action, array $hotelData, ?string $userId = null): bool
    {
        $message = "Hotel {$action}";
        $context = [
            'hotel_id' => $hotelData['id'] ?? null,
            'name' => $hotelData['name'] ?? null,
            'address' => $hotelData['address'] ?? null,
            'rating' => $hotelData['rating'] ?? null,
            'price_range' => $hotelData['price_range'] ?? null,
        ];

        return $this->sendLog('info', $message, $context, 'HotelController', $userId);
    }

    /**
     * Log error events
     */
    public function logError(string $message, array $context = [], ?string $source = null, ?string $userId = null): bool
    {
        return $this->sendLog('error', $message, $context, $source, $userId);
    }

    /**
     * Log warning events
     */
    public function logWarning(string $message, array $context = [], ?string $source = null, ?string $userId = null): bool
    {
        return $this->sendLog('warning', $message, $context, $source, $userId);
    }

    /**
     * Fetch logs from the second Laravel app
     */
    public function fetchLogs(array $filters = []): array
    {
        try {
            $queryParams = [];
            
            // Add filters as query parameters
            if (!empty($filters)) {
                $queryParams = array_merge($queryParams, $filters);
            }

            $response = Http::timeout(10)->get($this->logsApiUrl, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'status' => $response->status()
                ];
            } else {
                Log::warning('Failed to fetch logs from external service', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'filters' => $filters
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to fetch logs',
                    'status' => $response->status(),
                    'message' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while fetching logs from external service', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'error' => 'Exception occurred while fetching logs',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch logs with pagination
     */
    public function fetchLogsPaginated(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $filters['page'] = $page;
        $filters['per_page'] = $perPage;
        
        return $this->fetchLogs($filters);
    }

    /**
     * Fetch logs by application name
     */
    public function fetchLogsByApplication(string $applicationName, array $additionalFilters = []): array
    {
        $filters = array_merge($additionalFilters, [
            'application_name' => $applicationName
        ]);
        
        return $this->fetchLogs($filters);
    }

    /**
     * Fetch logs by level
     */
    public function fetchLogsByLevel(string $level, array $additionalFilters = []): array
    {
        $filters = array_merge($additionalFilters, [
            'level' => $level
        ]);
        
        return $this->fetchLogs($filters);
    }

    /**
     * Fetch logs by user ID
     */
    public function fetchLogsByUser(string $userId, array $additionalFilters = []): array
    {
        $filters = array_merge($additionalFilters, [
            'user_id' => $userId
        ]);
        
        return $this->fetchLogs($filters);
    }
}
