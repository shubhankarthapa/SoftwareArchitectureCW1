# Logging Integration with Second Laravel App

This document explains how the logging integration works with your second Laravel app.

## Overview

The system now automatically sends logs to your second Laravel app at `http://127.0.0.1:8001/api/logs` whenever certain events occur in the hotel booking system.

## Configuration

The logging service is configured in `config/services.php`:

```php
'logs_service' => [
    'url' => env('LOGS_SERVICE_URL', 'http://127.0.0.1:8001/api/logs'),
    'cache_enabled' => env('LOGS_CACHE_ENABLED', true),
    'cache_ttl' => env('LOGS_CACHE_TTL', 300), // 5 minutes in seconds
    'cache_driver' => env('LOGS_CACHE_DRIVER', 'database'), // database, file, array
],
```

You can override these settings by setting environment variables in your `.env` file:

```env
LOGS_SERVICE_URL=http://127.0.0.1:8001/api/logs
LOGS_CACHE_ENABLED=true
LOGS_CACHE_TTL=300
LOGS_CACHE_DRIVER=database
```

## Logged Events

### Booking Events
- **Booking Created**: When a new hotel booking is created
- **Booking Cancelled**: When a booking is cancelled (includes refund transaction)

### Transaction Events
- **Deposit**: When money is deposited into a wallet
- **Withdrawal**: When money is withdrawn from a wallet
- **Transfer Out**: When money is transferred from one user to another
- **Transfer In**: When money is received from another user
- **Booking Payment**: When payment is made for a hotel booking
- **Refund**: When a refund is processed for a cancelled booking

### Room Events
- **Room Created**: When a new room is created (single or bulk)
- **Room Updated**: When room details are updated
- **Room Deleted**: When a room is deleted

### Hotel Events
- **Hotel Created**: When a new hotel is added

## Log Data Structure

Each log entry sent to your second Laravel app follows this structure:

```json
{
    "application_name": "Hotel Booking Service",
    "level": "info",
    "message": "Booking created",
    "context": {
        "booking_id": 1,
        "hotel_id": 1,
        "room_id": 1,
        "check_in": "2024-01-15",
        "check_out": "2024-01-17",
        "total_amount": "150.00",
        "status": "confirmed",
        "payment_status": "paid"
    },
    "source": "BookingController",
    "user_id": "1",
    "session_id": "abc123",
    "ip_address": "127.0.0.1",
    "user_agent": "Mozilla/5.0..."
}
```

## Caching System

The logging system includes a comprehensive caching mechanism to improve performance and reduce API calls to your second Laravel app.

### Cache Features

- **Automatic Caching**: All log fetch operations are automatically cached
- **Smart Cache Keys**: Cache keys are generated based on filters to ensure unique caching per query
- **Automatic Invalidation**: Cache is automatically invalidated when new logs are created
- **Configurable TTL**: Cache time-to-live is configurable (default: 5 minutes)
- **Cache Management**: Full cache management through API endpoints

### Cache Configuration

```env
# Enable/disable caching
LOGS_CACHE_ENABLED=true

# Cache time-to-live in seconds (default: 300 = 5 minutes)
LOGS_CACHE_TTL=300

# Cache driver (database, file, array)
LOGS_CACHE_DRIVER=database
```

### Database Cache Setup

To use database caching, make sure you have the cache table created:

```bash
php artisan cache:table
php artisan migrate
```

This will create a `cache` table in your database to store cached data.

### Cache Behavior

1. **First Request**: Data is fetched from the second Laravel app and cached in database
2. **Subsequent Requests**: Data is served from database cache (much faster)
3. **New Log Creation**: Cache version is incremented, invalidating all cached data
4. **Cache Expiry**: Cache expires after the configured TTL
5. **Database Storage**: Cache data is stored in the `cache` table in your database

### Cache Management

You can manage the cache through API endpoints:

- **View Cache Stats**: See how many items are cached
- **Clear All Cache**: Remove all cached log data
- **Invalidate Specific Cache**: Remove cache for specific filters
- **Force Refresh**: Bypass cache and fetch fresh data

## Error Handling

The logging service includes robust error handling:

- **Timeout Protection**: API calls timeout after 5 seconds (POST) and 10 seconds (GET)
- **Fallback Logging**: If the external API fails, errors are logged locally
- **Non-blocking**: Logging failures don't affect the main application flow
- **Cache Fallback**: If cache operations fail, the system continues to work
- **Database Cache**: Uses Laravel's database cache driver for reliable storage

## Usage Examples

### Manual Logging

You can also use the logging service manually in your controllers:

```php
use App\Services\LoggingService;

class YourController extends Controller
{
    protected $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    public function someAction(Request $request)
    {
        // Your business logic here
        
        // Log custom event
        $this->loggingService->sendLog('info', 'Custom action performed', [
            'action' => 'some_action',
            'data' => $request->all()
        ], 'YourController', $request->user()->id);
    }
}
```

### Logging Different Levels

```php
// Info level
$this->loggingService->sendLog('info', 'User logged in', [], 'AuthController', $userId);

// Warning level
$this->loggingService->sendLog('warning', 'Low wallet balance', [
    'balance' => $balance
], 'WalletController', $userId);

// Error level
$this->loggingService->sendLog('error', 'Payment failed', [
    'error' => $exception->getMessage()
], 'PaymentController', $userId);
```

## Testing

To test the logging integration:

1. Make sure your second Laravel app is running on `http://127.0.0.1:8001`
2. Create a booking or perform a transaction
3. Check your second app's logs table for the new entries

## Troubleshooting

If logs are not appearing in your second app:

1. Check that the second Laravel app is running
2. Verify the API endpoint is accessible: `http://127.0.0.1:8001/api/logs`
3. Check the Laravel logs in `storage/logs/laravel.log` for any errors
4. Ensure the logs table exists in your second app's database

## Log Fetching API

The system also provides endpoints to fetch logs from your second Laravel app:

### Available Endpoints

#### Public Endpoints (No Authentication Required)

1. **Get All Logs**
   ```
   GET /api/logs
   ```
   - Query Parameters: `application_name`, `level`, `user_id`, `source`, `date_from`, `date_to`

2. **Get Logs with Pagination**
   ```
   GET /api/logs/paginated
   ```
   - Query Parameters: `page`, `per_page`, `application_name`, `level`, `user_id`, `source`, `date_from`, `date_to`

3. **Get Logs Statistics**
   ```
   GET /api/logs/stats
   ```
   - Query Parameters: `application_name`, `user_id`, `source`, `date_from`, `date_to`

4. **Get Logs by Application**
   ```
   GET /api/logs/application/{applicationName}
   ```
   - Query Parameters: `level`, `user_id`, `source`, `date_from`, `date_to`, `page`, `per_page`

5. **Get Logs by Level**
   ```
   GET /api/logs/level/{level}
   ```
   - Valid levels: `info`, `warning`, `error`, `debug`
   - Query Parameters: `application_name`, `user_id`, `source`, `date_from`, `date_to`, `page`, `per_page`

6. **Get Logs by User ID**
   ```
   GET /api/logs/user/{userId}
   ```
   - Query Parameters: `application_name`, `level`, `source`, `date_from`, `date_to`, `page`, `per_page`

#### Protected Endpoints (Authentication Required)

7. **Get My Logs**
   ```
   GET /api/my-logs
   ```
   - Returns logs for the authenticated user
   - Query Parameters: `application_name`, `level`, `source`, `date_from`, `date_to`, `page`, `per_page`

#### Cache Management Endpoints

8. **Get Cache Statistics**
   ```
   GET /api/logs/cache/stats
   ```
   - Returns cache statistics and information

9. **Clear All Cache**
   ```
   POST /api/logs/cache/clear
   ```
   - Clears all logs cache

10. **Invalidate Specific Cache**
    ```
    POST /api/logs/cache/invalidate
    ```
    - Body Parameters: `application_name`, `level`, `user_id`, `source`, `date_from`, `date_to`
    - Invalidates cache for specific filters

11. **Force Refresh Logs**
    ```
    POST /api/logs/refresh
    ```
    - Body Parameters: `application_name`, `level`, `user_id`, `source`, `date_from`, `date_to`, `page`, `per_page`
    - Bypasses cache and fetches fresh data

### Example API Calls

```bash
# Get all logs
curl -X GET "http://localhost:8000/api/logs"

# Get logs with pagination
curl -X GET "http://localhost:8000/api/logs/paginated?page=1&per_page=10"

# Get logs by application
curl -X GET "http://localhost:8000/api/logs/application/Hotel%20Booking%20Service"

# Get logs by level
curl -X GET "http://localhost:8000/api/logs/level/info"

# Get logs by user
curl -X GET "http://localhost:8000/api/logs/user/1"

# Get logs with filters
curl -X GET "http://localhost:8000/api/logs?level=error&date_from=2024-01-01&date_to=2024-01-31"

# Get my logs (requires authentication)
curl -X GET "http://localhost:8000/api/my-logs" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Cache management examples
curl -X GET "http://localhost:8000/api/logs/cache/stats"

curl -X POST "http://localhost:8000/api/logs/cache/clear"

curl -X POST "http://localhost:8000/api/logs/cache/invalidate" \
  -H "Content-Type: application/json" \
  -d '{"level": "error", "application_name": "Hotel Booking Service"}'

curl -X POST "http://localhost:8000/api/logs/refresh" \
  -H "Content-Type: application/json" \
  -d '{"level": "info", "page": 1, "per_page": 10}'
```

### Response Format

All log fetching endpoints return responses in this format:

```json
{
    "status": "success",
    "message": "Logs fetched successfully",
    "data": {
        "logs": [
            {
                "id": 1,
                "application_name": "Hotel Booking Service",
                "level": "info",
                "message": "Booking created",
                "context": {
                    "booking_id": 1,
                    "hotel_id": 1,
                    "room_id": 1
                },
                "source": "BookingController",
                "user_id": "1",
                "session_id": "abc123",
                "ip_address": "127.0.0.1",
                "user_agent": "Mozilla/5.0...",
                "created_at": "2024-01-15T10:30:00Z",
                "updated_at": "2024-01-15T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7
        }
    }
}
```

### Error Response Format

```json
{
    "status": "error",
    "message": "Failed to fetch logs",
    "details": "Connection timeout"
}
```

## Performance Benefits

With caching enabled, you'll experience:

- **Faster Response Times**: Cached responses are served instantly
- **Reduced API Calls**: Fewer requests to your second Laravel app
- **Better Scalability**: Reduced load on your logging service
- **Improved User Experience**: Faster log viewing and filtering

### Cache Performance Example

```
First Request (No Cache):
- API Call to second app: ~200ms
- Total Response Time: ~250ms

Subsequent Requests (With Cache):
- Cache Lookup: ~5ms
- Total Response Time: ~10ms

Performance Improvement: 25x faster!
```

## Security Notes

- The logging API is public as requested
- Sensitive data should be filtered out in the context array
- Consider implementing rate limiting on your second app's logs endpoint
- The service includes IP address and user agent logging for audit purposes
- Log fetching endpoints are also public for easy access
- Cache data is stored securely in your database using Laravel's database cache driver
- No external dependencies like Redis required
