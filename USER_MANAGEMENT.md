# User Management Guide

This guide explains how to view and manage registered users in your hotel microservices application.

## Overview

The User Management system provides comprehensive functionality to:
- View all registered users
- Search and filter users
- Get user statistics
- Manage user information (admin only)
- Delete users (admin only)

## API Endpoints

### Public Endpoints (No Authentication Required)

#### Get All Users
```http
GET /api/users
```

#### Get User by ID
```http
GET /api/users/{userId}
```

#### Search Users
```http
GET /api/users/search?q={search_term}
```

#### Get User Statistics
```http
GET /api/users/stats
```

#### Get Users by Date Range
```http
GET /api/users/date-range?start_date={date}&end_date={date}
```

### Protected Endpoints (Authentication Required - Admin Only)

#### Update User
```http
PUT /api/users/{userId}
```

#### Delete User
```http
DELETE /api/users/{userId}
```

## How to View Registered Users

### 1. Get All Users (Paginated)

**Endpoint:** `GET /api/users`

**Query Parameters:**
- `per_page` (optional): Number of users per page (default: 15, max: 100)
- `search` (optional): Search term for name or email
- `sort_by` (optional): Sort field (name, email, created_at, email_verified_at)
- `sort_order` (optional): Sort direction (asc, desc)

**Examples:**

```bash
# Get first page with 20 users per page
GET /api/users?per_page=20

# Search for users with "john" in name or email
GET /api/users?search=john

# Sort by name in ascending order
GET /api/users?sort_by=name&sort_order=asc

# Combine multiple parameters
GET /api/users?per_page=25&search=john&sort_by=created_at&sort_order=desc
```

**Response:**
```json
{
    "status": "success",
    "message": "Users fetched successfully",
    "data": {
        "users": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "email_verified_at": "2024-01-15T10:30:00.000000Z",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 15,
            "total": 75,
            "from": 1,
            "to": 15
        }
    }
}
```

### 2. Get Specific User Details

**Endpoint:** `GET /api/users/{userId}`

**Response:**
```json
{
    "status": "success",
    "message": "User fetched successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2024-01-15T10:30:00.000000Z",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "bookings": [
            {
                "id": 1,
                "hotel_id": 1,
                "room_id": 1,
                "check_in": "2024-02-01",
                "check_out": "2024-02-03",
                "total_amount": "300.00",
                "status": "confirmed"
            }
        ],
        "wallet": {
            "id": 1,
            "balance": "500.00",
            "currency": "USD"
        }
    }
}
```

### 3. Search Users

**Endpoint:** `GET /api/users/search?q={search_term}`

**Query Parameters:**
- `q` (required): Search term (minimum 2 characters)

**Example:**
```bash
GET /api/users/search?q=john
```

**Response:**
```json
{
    "status": "success",
    "message": "Users found successfully",
    "data": {
        "users": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "created_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "total_found": 1
    }
}
```

### 4. Get User Statistics

**Endpoint:** `GET /api/users/stats`

**Response:**
```json
{
    "status": "success",
    "message": "User statistics fetched successfully",
    "data": {
        "total_users": 150,
        "verified_users": 120,
        "unverified_users": 30,
        "users_with_bookings": 85,
        "users_with_wallets": 150,
        "recent_registrations": 25
    }
}
```

### 5. Get Users by Date Range

**Endpoint:** `GET /api/users/date-range?start_date={date}&end_date={date}`

**Query Parameters:**
- `start_date` (required): Start date (YYYY-MM-DD format)
- `end_date` (required): End date (YYYY-MM-DD format)

**Example:**
```bash
GET /api/users/date-range?start_date=2024-01-01&end_date=2024-01-31
```

**Response:**
```json
{
    "status": "success",
    "message": "Users fetched successfully",
    "data": {
        "users": [...],
        "total_users": 25,
        "date_range": {
            "start_date": "2024-01-01",
            "end_date": "2024-01-31"
        }
    }
}
```

## Admin Operations (Protected Routes)

### 1. Update User Information

**Endpoint:** `PUT /api/users/{userId}`

**Request Body:**
```json
{
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "email_verified_at": "2024-01-15T10:30:00.000000Z"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Smith",
        "email": "johnsmith@example.com",
        "email_verified_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-16T14:20:00.000000Z"
    }
}
```

### 2. Delete User

**Endpoint:** `DELETE /api/users/{userId}`

**Response:**
```json
{
    "status": "success",
    "message": "User deleted successfully"
}
```

**Note:** Users with active bookings cannot be deleted.

## Query Parameters Reference

### Pagination
- `per_page`: Number of items per page (1-100, default: 15)

### Search
- `search`: Search term for name or email

### Sorting
- `sort_by`: Field to sort by
  - `name` - Sort by user name
  - `email` - Sort by email address
  - `created_at` - Sort by registration date
  - `email_verified_at` - Sort by email verification date
- `sort_order`: Sort direction
  - `asc` - Ascending order
  - `desc` - Descending order (default)

### Date Range
- `start_date`: Start date in YYYY-MM-DD format
- `end_date`: End date in YYYY-MM-DD format

## Error Responses

### User Not Found
```json
{
    "error": "User not found"
}
```

### Cannot Delete User
```json
{
    "error": "Cannot delete user. There are active bookings for this user."
}
```

### Validation Errors
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "start_date": ["The start date field is required."]
    }
}
```

## Best Practices

### 1. Pagination
- Always use pagination for large user lists
- Choose appropriate `per_page` values (15-25 is usually optimal)
- Implement "Load More" or pagination controls in your UI

### 2. Search
- Use search for finding specific users quickly
- Implement debouncing for search inputs
- Consider implementing advanced search filters

### 3. Security
- User management operations require authentication
- Consider implementing role-based access control
- Validate user permissions before allowing admin operations

### 4. Performance
- Use appropriate indexes on search fields
- Implement caching for user statistics
- Consider implementing user activity tracking

## Testing the API

### Using cURL

```bash
# Get all users
curl -X GET "http://localhost:8000/api/users"

# Search users
curl -X GET "http://localhost:8000/api/users/search?q=john"

# Get user statistics
curl -X GET "http://localhost:8000/api/users/stats"

# Get specific user
curl -X GET "http://localhost:8000/api/users/1"

# Update user (requires authentication)
curl -X PUT "http://localhost:8000/api/users/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name": "John Smith"}'
```

### Using Postman

1. Set the request method (GET, PUT, DELETE)
2. Use the appropriate endpoint
3. Add query parameters for GET requests
4. Add request body for PUT requests
5. Add Authorization header for protected routes

## Use Cases

### 1. Admin Dashboard
- Display user statistics
- Show recent registrations
- Monitor user activity

### 2. User Management
- View all registered users
- Search for specific users
- Update user information
- Delete inactive users

### 3. Analytics
- Track user growth
- Monitor registration trends
- Analyze user behavior

### 4. Customer Support
- Find users by name or email
- View user booking history
- Check user wallet status

## Troubleshooting

### Common Issues

1. **Authentication Required**: Make sure you're logged in for protected routes
2. **User Not Found**: Verify the user ID exists
3. **Cannot Delete User**: Check if user has active bookings
4. **Validation Errors**: Ensure all required fields are provided

### Debug Tips

- Check the response status codes
- Review error messages in the response
- Verify authentication token is valid
- Check Laravel logs for detailed error information
- Use the user statistics endpoint to verify data
