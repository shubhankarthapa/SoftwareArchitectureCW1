<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class LoggingService
{
    protected $logsApiUrl;
    protected $applicationName;
    protected $cacheEnabled;
    protected $cacheTtl;
    protected $cacheDriver;

    public function __construct()
    {
        $this->logsApiUrl = config('services.logs_service.url', 'http://127.0.0.1:8001/api/logs');
        $this->applicationName =  'Hotel Booking Service';
        $this->cacheEnabled = config('services.logs_service.cache_enabled', true);
        $this->cacheTtl = config('services.logs_service.cache_ttl', 300); // 5 minutes default
        $this->cacheDriver = config('services.logs_service.cache_driver', 'database');
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
                // Invalidate cache when new log is created
                $this->invalidateLogsCache();
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
        // Generate cache key based on filters
        $cacheKey = $this->generateCacheKey('logs', $filters);
        
        // Check cache first if enabled
        if ($this->cacheEnabled) {
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult !== null) {
                Log::info('Logs fetched from cache', ['cache_key' => $cacheKey]);
                return $cachedResult;
            }
        }

        try {
            $queryParams = [];
            
            // Add filters as query parameters
            if (!empty($filters)) {
                $queryParams = array_merge($queryParams, $filters);
            }

            $response = Http::timeout(10)->get($this->logsApiUrl, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $result = [
                    'success' => true,
                    'data' => $data,
                    'status' => $response->status()
                ];
                
                // Cache the result if caching is enabled
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $result, $this->cacheTtl);
                    Log::info('Logs cached successfully', ['cache_key' => $cacheKey, 'ttl' => $this->cacheTtl]);
                }
                
                return $result;
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

    /**
     * Generate cache key based on operation and filters
     */
    protected function generateCacheKey(string $operation, array $filters = []): string
    {
        // Sort filters to ensure consistent cache keys
        ksort($filters);
        
        // Create a hash of the filters for the cache key
        $filtersHash = md5(serialize($filters));
        
        // Include cache version for proper invalidation
        $version = $this->getCacheVersion();
        
        return "logs_service:{$operation}:v{$version}:" . $filtersHash;
    }

    /**
     * Invalidate all logs cache
     */
    public function invalidateLogsCache(): void
    {
        if (!$this->cacheEnabled) {
            return;
        }

        try {
            // For database cache, we need to use a different approach
            // Since we can't easily get all keys with database cache,
            // we'll use a cache tag approach or clear specific known patterns
            
            // Clear cache using database cache driver
            $this->clearCacheByPattern('logs_service:*');
            
            Log::info('Logs cache invalidated using database cache');
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate logs cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate specific cache by filters
     */
    public function invalidateCacheByFilters(array $filters = []): void
    {
        if (!$this->cacheEnabled) {
            return;
        }

        try {
            $cacheKey = $this->generateCacheKey('logs', $filters);
            Cache::forget($cacheKey);
            Log::info('Specific logs cache invalidated', ['cache_key' => $cacheKey]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate specific logs cache', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
        }
    }

    /**
     * Clear all logs cache manually
     */
    public function clearAllLogsCache(): void
    {
        $this->invalidateLogsCache();
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (!$this->cacheEnabled) {
            return ['cache_enabled' => false];
        }

        try {
            // For database cache, we can't easily get all keys
            // Instead, we'll return basic cache information
            return [
                'cache_enabled' => true,
                'cache_driver' => $this->cacheDriver,
                'cache_ttl' => $this->cacheTtl,
                'cache_prefix' => 'logs_service:',
                'cache_version' => $this->getCacheVersion(),
                'note' => 'Database cache driver - individual key count not available'
            ];
        } catch (\Exception $e) {
            return [
                'cache_enabled' => true,
                'error' => 'Failed to get cache stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enable or disable caching
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }

    /**
     * Clear cache by pattern (for database cache driver)
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        try {
            // For database cache, we need to use a different approach
            // Since database cache doesn't support pattern matching like Redis,
            // we'll use a more targeted approach
            
            // We can use Cache::flush() to clear all cache, but that's too aggressive
            // Instead, we'll use a cache tag system or maintain a list of known keys
            
            // For now, we'll use a simple approach with a cache versioning system
            $this->incrementCacheVersion();
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Increment cache version to invalidate all logs cache
     */
    protected function incrementCacheVersion(): void
    {
        try {
            $versionKey = 'logs_service:cache_version';
            $currentVersion = Cache::get($versionKey, 0);
            Cache::put($versionKey, $currentVersion + 1, 86400); // 24 hours
            
            Log::info('Cache version incremented', ['new_version' => $currentVersion + 1]);
        } catch (\Exception $e) {
            Log::warning('Failed to increment cache version', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get current cache version
     */
    protected function getCacheVersion(): int
    {
        try {
            $versionKey = 'logs_service:cache_version';
            return Cache::get($versionKey, 0);
        } catch (\Exception $e) {
            Log::warning('Failed to get cache version', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
