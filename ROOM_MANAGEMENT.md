# Room Management Guide

This guide explains how to create and manage rooms for room types in your hotel microservices application.

## Overview

Your application has a hierarchical structure:
- **Hotel** → **RoomType** → **Room**
- Each hotel can have multiple room types (e.g., "Deluxe", "Standard", "Suite")
- Each room type can have multiple individual rooms

## API Endpoints

### Public Endpoints (No Authentication Required)

#### Get Room Information
```http
GET /api/rooms/{roomId}
GET /api/room-types/{roomTypeId}/rooms
GET /api/room-types/{roomTypeId}/stats
```

### Protected Endpoints (Authentication Required)

#### Create Single Room
```http
POST /api/room-types/{roomTypeId}/rooms
```

#### Bulk Create Rooms
```http
POST /api/room-types/{roomTypeId}/rooms/bulk
```

#### Update Room
```http
PUT /api/rooms/{roomId}
```

#### Delete Room
```http
DELETE /api/rooms/{roomId}
```

## How to Create Rooms for Room Types

### 1. Create a Single Room

**Endpoint:** `POST /api/room-types/{roomTypeId}/rooms`

**Request Body:**
```json
{
    "room_number": "101",
    "floor": 1,
    "status": "available"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Room created successfully",
    "data": {
        "id": 1,
        "hotel_id": 1,
        "room_type_id": 1,
        "room_number": "101",
        "floor": 1,
        "status": "available",
        "hotel": { ... },
        "room_type": { ... }
    }
}
```

### 2. Bulk Create Rooms (Multiple Methods)

#### Method 1: Create from Array

**Request Body:**
```json
{
    "rooms": [
        {
            "room_number": "101",
            "floor": 1,
            "status": "available"
        },
        {
            "room_number": "102",
            "floor": 1,
            "status": "available"
        },
        {
            "room_number": "201",
            "floor": 2,
            "status": "available"
        }
    ]
}
```

#### Method 2: Auto-generate Sequential Rooms

**Request Body:**
```json
{
    "room_count": 20,
    "start_floor": 1
}
```

This will create 20 rooms starting from floor 1, with automatic room numbering.

#### Method 3: Create for Floor Range

**Request Body:**
```json
{
    "floor_range": {
        "start": 1,
        "end": 3
    },
    "rooms_per_floor": 10
}
```

This will create 10 rooms per floor for floors 1-3 (total 30 rooms).

### 3. Room Status Options

- `available` - Room is ready for booking
- `occupied` - Room is currently occupied
- `maintenance` - Room is under maintenance
- `reserved` - Room is reserved but not occupied

## Complete Workflow Example

### Step 1: Create a Hotel
```http
POST /api/hotels/i/initialize
```

### Step 2: Create Room Types
```http
POST /api/hotels/{hotelId}/room-types
```

**Request Body:**
```json
{
    "name": "Deluxe Room",
    "description": "Spacious room with city view",
    "price_per_night": 150.00,
    "capacity": 2,
    "amenities": ["WiFi", "TV", "Mini Bar", "City View"]
}
```

### Step 3: Create Rooms for the Room Type
```http
POST /api/room-types/{roomTypeId}/rooms/bulk
```

**Request Body:**
```json
{
    "floor_range": {
        "start": 1,
        "end": 5
    },
    "rooms_per_floor": 8
}
```

This creates 40 rooms (8 rooms × 5 floors) for the Deluxe Room type.

## Room Numbering Convention

The system supports flexible room numbering:

- **Simple numbers**: 101, 102, 103...
- **Floor-based**: 101, 102, 201, 202...
- **Custom format**: A101, B201, etc.

## Validation Rules

### Room Creation
- `room_number`: Required, max 50 characters, must be unique within hotel
- `floor`: Required, integer 1-100
- `status`: Optional, must be one of: available, occupied, maintenance, reserved

### Bulk Creation
- Maximum 50 rooms per request
- Room numbers must be unique within the hotel
- Automatic conflict resolution (skips existing room numbers)

## Error Handling

The API provides detailed error messages:

```json
{
    "error": "Room number already exists in this hotel"
}
```

```json
{
    "error": "Cannot delete room. There are active bookings for this room."
}
```

## Best Practices

1. **Plan your room structure** before creating rooms
2. **Use bulk creation** for efficiency when setting up hotels
3. **Follow consistent numbering** conventions
4. **Monitor room statistics** using the stats endpoint
5. **Handle maintenance** by updating room status

## Room Statistics

Get detailed statistics for any room type:

```http
GET /api/room-types/{roomTypeId}/stats
```

**Response:**
```json
{
    "status": "success",
    "message": "Room statistics fetched successfully",
    "data": {
        "room_type": { ... },
        "statistics": {
            "total_rooms": 40,
            "available_rooms": 35,
            "occupied_rooms": 3,
            "maintenance_rooms": 1,
            "reserved_rooms": 1
        }
    }
}
```

## Testing the API

### Using cURL

```bash
# Create a single room
curl -X POST http://localhost:8000/api/room-types/1/rooms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "room_number": "101",
    "floor": 1,
    "status": "available"
  }'

# Bulk create rooms
curl -X POST http://localhost:8000/api/room-types/1/rooms/bulk \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "room_count": 10,
    "start_floor": 1
  }'
```

### Using Postman

1. Set the request method to `POST`
2. Use the endpoint: `/api/room-types/{roomTypeId}/rooms` or `/api/room-types/{roomTypeId}/rooms/bulk`
3. Add your authentication token in the Authorization header
4. Set Content-Type to `application/json`
5. Add your request body in JSON format

## Troubleshooting

### Common Issues

1. **Room number already exists**: Check for duplicate room numbers in the hotel
2. **Invalid room type**: Ensure the room type ID exists
3. **Authentication required**: Make sure you're logged in and using a valid token
4. **Validation errors**: Check that all required fields are provided and valid

### Debug Tips

- Use the room statistics endpoint to verify room creation
- Check the response for detailed error messages
- Verify room type existence before creating rooms
- Monitor the Laravel logs for detailed error information
