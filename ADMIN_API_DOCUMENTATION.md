# Admin API Documentation

## Admin Password Reset and User Management APIs

### 1. Get All Users

**Endpoint:** `GET /api/admin/users`

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Response:**

```json
{
    "status": "success",
    "message": "Users fetched successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "roles": ["child"],
            "created_at": "2023-01-01T00:00:00.000000Z",
            "child_details": {
                "child_name": "John Doe",
                "child_no": "CHILD001",
                "father_name": "David Doe",
                "date_of_birth": "2020-01-01",
                "gender": "male"
            }
        }
    ],
    "total": 25
}
```

### 2. Search Users

**Endpoint:** `POST /api/admin/search-users`

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "search": "john",
    "type": "child",
    "limit": 10
}
```

**Parameters:**

-   `search` (required): Search term (minimum 2 characters)
-   `type` (optional): Filter by user type - "all", "admin", "child", "healthcare"
-   `limit` (optional): Maximum results to return (max 50, default 20)

**Response:**

```json
{
    "status": "success",
    "message": "Search results fetched successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "roles": ["child"],
            "child_details": {
                "child_name": "John Doe",
                "child_no": "CHILD001",
                "father_name": "David Doe"
            },
            "identifier_options": {
                "user_id": 1,
                "email": "john@example.com",
                "child_no": "CHILD001"
            }
        }
    ],
    "total": 1,
    "search_query": "john",
    "search_type": "child"
}
```

### 3. Reset User Password

**Endpoint:** `POST /api/admin/reset-password`

**Headers:**

```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "identifier": "CHILD001",
    "identifier_type": "child_no"
}
```

**Identifier Types:**

-   `user_id`: User's database ID
-   `email`: User's email address
-   `child_no`: Child's registration number (for children)
-   `license`: Healthcare provider's license number (for healthcare providers)

**Response:**

```json
{
    "status": "success",
    "message": "User password has been reset to default successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "username": "john_doe",
        "roles": ["child"]
    },
    "reset_info": {
        "identifier_used": "child_no",
        "identifier_value": "CHILD001",
        "default_password_hint": "Father's name: David Doe"
    }
}
```

## Default Password Rules

### Admin Users

-   Default Password: `Admin123`

### Child Users

-   Default Password: Father's name (e.g., if father's name is "David Doe", password is "David Doe")

### Healthcare Providers (Doctors/Nurses)

-   Default Password: `{facility}@{license}` (e.g., "City Hospital@DOC123")

## Usage Workflow

1. **Find User**: Use the search API to find the user you want to reset
2. **Get Identifier**: From the search results, choose an appropriate identifier (user_id, email, child_no, or license)
3. **Reset Password**: Use the reset password API with the chosen identifier
4. **Inform User**: The user can now login with their default password

## Error Responses

All APIs return consistent error responses:

```json
{
    "status": "error",
    "message": "Error description",
    "error": "Technical error details (in development)"
}
```

Common HTTP status codes:

-   `400`: Bad Request (invalid parameters)
-   `401`: Unauthorized (invalid token)
-   `403`: Forbidden (insufficient permissions)
-   `404`: Not Found (user not found)
-   `422`: Validation Error
-   `500`: Server Error
