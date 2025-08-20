# User Service Microservice

A Laravel-based microservice for user authentication and management in the Hotel Booking System.

## Features

- **User Registration**: Create new user accounts
- **User Authentication**: Login with email and password
- **Profile Management**: View and update user profiles
- **Token-based Authentication**: Using Laravel Sanctum
- **User Management**: Get and update users by ID

## API Endpoints

### Public Routes
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login

### Protected Routes (Require Authentication)
- `GET /api/auth/profile` - Get current user profile
- `PUT /api/auth/profile` - Update current user profile
- `POST /api/auth/logout` - Logout and invalidate token
- `GET /api/users/{id}` - Get user by ID
- `PUT /api/users/{id}` - Update user by ID

## Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Environment configuration:**
   ```bash
   cp env.example .env
   # Update database configuration in .env
   ```

3. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

5. **Start the service:**
   ```bash
   php artisan serve --port=8001
   ```

## Database

The service uses MySQL and requires a database named `user_service_db` (configurable in `.env`).

## Testing

### User Registration
```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123"}'
```

### User Login
```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

### Protected Route (with token)
```bash
curl -X GET http://localhost:8001/api/auth/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Architecture

This service is part of a microservices architecture where:
- **Main App** communicates with this service via HTTP
- **Authentication** is handled using Laravel Sanctum tokens
- **Data** is stored in a dedicated MySQL database
- **Port**: Runs on port 8001

## Communication

The main Laravel application communicates with this service using the `UserService` class, which makes HTTP requests to the endpoints defined above.
