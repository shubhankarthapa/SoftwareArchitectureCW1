# Logging Integration with Second Laravel App

This document explains how the logging integration works with your second Laravel app.

## Overview

The system now automatically sends logs to your second Laravel app at `http://127.0.0.1:8001/api/logs` whenever certain events occur in the hotel booking system.

## Configuration

The logging service is configured in `config/services.php`:

```php
'logs_service' => [
    'url' => env('LOGS_SERVICE_URL', 'http://127.0.0.1:8001/api/logs'),
],
```

You can override the URL by setting the `LOGS_SERVICE_URL` environment variable in your `.env` file.

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

## Error Handling

The logging service includes robust error handling:

- **Timeout Protection**: API calls timeout after 5 seconds
- **Fallback Logging**: If the external API fails, errors are logged locally
- **Non-blocking**: Logging failures don't affect the main application flow

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

## Security Notes

- The logging API is public as requested
- Sensitive data should be filtered out in the context array
- Consider implementing rate limiting on your second app's logs endpoint
- The service includes IP address and user agent logging for audit purposes
