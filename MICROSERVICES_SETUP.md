# Laravel-to-Laravel Microservices Setup Guide

This guide explains how to set up the simple Laravel-to-Laravel microservices architecture for the Hotel Booking System.

## Architecture Overview

The system consists of:
- **Main Laravel App** (this repository) - Acts as API Gateway
- **User Service** - Laravel app for user management
- **Hotel Service** - Laravel app for hotel and room management
- **Booking Service** - Laravel app for booking operations
- **Wallet Service** - Laravel app for wallet and payment operations

## Setup Instructions

### 1. Main Laravel App (This Repository)

1. Install dependencies:
   ```bash
   composer install
   ```

2. Copy environment file:
   ```bash
   cp env.example .env
   ```

3. Generate application key:
   ```bash
   php artisan key:generate
   ```

4. Configure microservices URLs in `.env`:
   ```
   USER_SERVICE_URL=http://localhost:8001
   HOTEL_SERVICE_URL=http://localhost:8002
   BOOKING_SERVICE_URL=http://localhost:8003
   WALLET_SERVICE_URL=http://localhost:8004
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Start the application:
   ```bash
   php artisan serve
   ```

### 2. User Service

Create a new Laravel application for user management:

```bash
composer create-project laravel/laravel user-service
cd user-service
```

**Required Models:**
- User (with authentication)

**Required Controllers:**
- AuthController (register, login, profile, updateProfile)

**Required Routes:**
- POST /api/auth/register
- POST /api/auth/login
- GET /api/users/{id}
- PUT /api/users/{id}

**Database:**
- users table (id, name, email, password, created_at, updated_at)

### 3. Hotel Service

Create a new Laravel application for hotel management:

```bash
composer create-project laravel/laravel hotel-service
cd hotel-service
```

**Required Models:**
- Hotel
- Room
- RoomType

**Required Controllers:**
- HotelController (getAllHotels, getHotel, getHotelRooms, getAvailableRooms, searchHotels)

**Required Routes:**
- GET /api/hotels
- GET /api/hotels/{id}
- GET /api/hotels/{id}/rooms
- GET /api/hotels/{id}/available-rooms
- GET /api/hotels/search

**Database:**
- hotels table (id, name, address, description, created_at, updated_at)
- room_types table (id, name, description, price_per_night, created_at, updated_at)
- rooms table (id, hotel_id, room_type_id, room_number, status, created_at, updated_at)

### 4. Booking Service

Create a new Laravel application for booking management:

```bash
composer create-project laravel/laravel booking-service
cd booking-service
```

**Required Models:**
- Booking

**Required Controllers:**
- BookingController (createBooking, getBooking, getUserBookings, cancelBooking, getHotelBookings)

**Required Routes:**
- POST /api/bookings
- GET /api/bookings/{id}
- GET /api/users/{id}/bookings
- DELETE /api/bookings/{id}
- GET /api/hotels/{id}/bookings

**Database:**
- bookings table (id, user_id, hotel_id, room_id, check_in, check_out, total_amount, status, created_at, updated_at)

### 5. Wallet Service

Create a new Laravel application for wallet management:

```bash
composer create-project laravel/laravel wallet-service
cd wallet-service
```

**Required Models:**
- Wallet
- Transaction

**Required Controllers:**
- WalletController (getBalance, deposit, withdraw, transfer, getTransactions)

**Required Routes:**
- GET /api/wallets/{id}/balance
- POST /api/wallets/{id}/deposit
- POST /api/wallets/{id}/withdraw
- POST /api/wallets/transfer
- GET /api/wallets/{id}/transactions

**Database:**
- wallets table (id, user_id, balance, created_at, updated_at)
- transactions table (id, wallet_id, type, amount, description, created_at, updated_at)

## Communication Pattern

All services communicate via HTTP requests using Laravel's built-in HTTP client. The main app acts as an API Gateway that:

1. Receives requests from clients
2. Authenticates users using Sanctum
3. Forwards requests to appropriate microservices
4. Returns responses to clients

## Features

- **User Management**: Registration, login, profile management
- **Hotel Management**: List hotels, search, view rooms, check availability
- **Booking System**: Create, view, cancel bookings
- **Wallet System**: Deposit, withdraw, transfer money
- **Authentication**: JWT-like tokens via Laravel Sanctum
- **Caching**: Redis-based caching for improved performance

## Testing

Test the API endpoints using tools like Postman or curl:

```bash
# Register a user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Get hotels (with token)
curl -X GET http://localhost:8000/api/hotels \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Benefits of This Architecture

1. **Simple**: Uses standard Laravel applications
2. **Independent**: Each service can be developed and deployed separately
3. **Scalable**: Services can be scaled independently
4. **Maintainable**: Clear separation of concerns
5. **Familiar**: Uses Laravel's standard patterns and tools
