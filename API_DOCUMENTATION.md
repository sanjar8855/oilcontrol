# 📡 OilControl REST API Documentation

Complete API documentation for the OilControl mobile application (Flutter).

**Base URL:** `http://yourdomain.com/api`

**Authentication:** Laravel Sanctum (Bearer Token)

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Dashboard](#dashboard)
3. [Clients](#clients)
4. [Vehicles](#vehicles)
5. [Service Logs](#service-logs)
6. [Error Handling](#error-handling)
7. [Pagination](#pagination)

---

## 🔐 Authentication

### Register

Create a new user account and workshop.

**Endpoint:** `POST /api/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Admin Adminov",
  "email": "admin@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "workshop_name": "Avtomashina Servis" (optional),
  "phone": "+998901234567" (optional)
}
```

**Response (201 Created):**
```json
{
  "message": "Registration successful",
  "user": {
    "id": 1,
    "name": "Admin Adminov",
    "email": "admin@example.com"
  },
  "workshop": {
    "id": 1,
    "name": "Avtomashina Servis",
    "owner_name": "Admin Adminov",
    "phone": "+998901234567",
    "email": null,
    "address": null,
    "subscription_plan": "free",
    "subscription_expires_at": "2025-12-15 00:00:00",
    "days_remaining": 30,
    "is_active": true,
    "created_at": "2025-11-15 10:00:00",
    "updated_at": "2025-11-15 10:00:00"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

### Login

Authenticate user and get API token.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "admin@oilcontrol.uz",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin Adminov",
    "email": "admin@oilcontrol.uz"
  },
  "workshop": {
    "id": 1,
    "name": "Avtomashina Servis Markazi",
    "owner_name": "Admin Adminov",
    "phone": "+998901234567",
    "subscription_plan": "pro",
    "subscription_expires_at": "2026-11-15 00:00:00",
    "days_remaining": 365,
    "is_active": true
  },
  "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

### Logout

Revoke current access token.

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

---

### Get Authenticated User

Get current user information.

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "Admin Adminov",
    "email": "admin@oilcontrol.uz"
  },
  "workshop": {
    "id": 1,
    "name": "Avtomashina Servis Markazi",
    "subscription_plan": "pro",
    "days_remaining": 365
  }
}
```

---

## 📊 Dashboard

### Get Dashboard Statistics

**Endpoint:** `GET /api/dashboard`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "workshop": {
    "id": 1,
    "name": "Avtomashina Servis Markazi",
    "owner_name": "Admin Adminov",
    "phone": "+998901234567",
    "subscription_plan": "pro",
    "days_remaining": 365
  },
  "stats": {
    "total_clients": 4,
    "total_vehicles": 5,
    "total_service_logs": 5,
    "pending_reminders": 2,
    "subscription_plan": "pro",
    "subscription_expires_at": "2026-11-15",
    "days_remaining": 365
  },
  "recent_clients": [
    {
      "id": 1,
      "name": "Abbos Karimov",
      "phone": "+998901111111",
      "vehicles_count": 1
    }
  ]
}
```

---

## 👥 Clients

### List All Clients

**Endpoint:** `GET /api/clients`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "workshop_id": 1,
      "name": "Abbos Karimov",
      "phone": "+998901111111",
      "telegram_id": "123456789",
      "email": "abbos@gmail.com",
      "notes": "Doimiy mijoz",
      "vehicles_count": 1,
      "created_at": "2025-11-15 10:00:00",
      "updated_at": "2025-11-15 10:00:00"
    }
  ],
  "links": {
    "first": "http://domain.com/api/clients?page=1",
    "last": "http://domain.com/api/clients?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 4,
    "total": 4
  }
}
```

---

### Create New Client

**Endpoint:** `POST /api/clients`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jasur Yusupov",
  "phone": "+998905555555",
  "telegram_id": "111222333" (optional),
  "email": "jasur@gmail.com" (optional),
  "notes": "Test mijoz" (optional)
}
```

**Response (201 Created):**
```json
{
  "message": "Client created successfully",
  "client": {
    "id": 5,
    "workshop_id": 1,
    "name": "Jasur Yusupov",
    "phone": "+998905555555",
    "telegram_id": "111222333",
    "email": "jasur@gmail.com",
    "notes": "Test mijoz",
    "created_at": "2025-11-15 11:00:00",
    "updated_at": "2025-11-15 11:00:00"
  }
}
```

---

### Get Single Client

**Endpoint:** `GET /api/clients/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "client": {
    "id": 1,
    "name": "Abbos Karimov",
    "phone": "+998901111111",
    "telegram_id": "123456789",
    "email": "abbos@gmail.com",
    "notes": "Doimiy mijoz",
    "vehicles": [
      {
        "id": 1,
        "make": "Chevrolet",
        "model": "Lacetti",
        "year": 2015,
        "plate_number": "01 A 123 BC",
        "service_logs": [
          {
            "id": 1,
            "service_date": "2025-11-05",
            "odometer_reading": 85000,
            "service_type": "Yog' almashtirish"
          }
        ]
      }
    ]
  }
}
```

---

### Update Client

**Endpoint:** `PUT /api/clients/{id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Abbos Karimov (Yangilangan)",
  "phone": "+998901111111",
  "telegram_id": "123456789",
  "email": "abbos.new@gmail.com",
  "notes": "VIP mijoz"
}
```

**Response (200 OK):**
```json
{
  "message": "Client updated successfully",
  "client": {
    "id": 1,
    "name": "Abbos Karimov (Yangilangan)",
    "phone": "+998901111111",
    "email": "abbos.new@gmail.com"
  }
}
```

---

### Delete Client

**Endpoint:** `DELETE /api/clients/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Client deleted successfully"
}
```

---

## 🚗 Vehicles

### List All Vehicles

**Endpoint:** `GET /api/vehicles`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "make": "Chevrolet",
      "model": "Lacetti",
      "year": 2015,
      "plate_number": "01 A 123 BC",
      "vin": "KL1SF68Y38B123456",
      "client": {
        "id": 1,
        "name": "Abbos Karimov",
        "phone": "+998901111111"
      },
      "latest_service": {
        "id": 1,
        "service_date": "2025-11-05",
        "odometer_reading": 85000
      },
      "created_at": "2025-11-15 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5
  }
}
```

---

### Create New Vehicle

**Endpoint:** `POST /api/vehicles`

**Request Body:**
```json
{
  "client_id": 1,
  "make": "Toyota",
  "model": "Camry",
  "year": 2023,
  "plate_number": "01 F 777 LM",
  "vin": "JTNK4RBE0N3123456" (optional)
}
```

**Response (201 Created):**
```json
{
  "message": "Vehicle created successfully",
  "vehicle": {
    "id": 6,
    "client_id": 1,
    "make": "Toyota",
    "model": "Camry",
    "year": 2023,
    "plate_number": "01 F 777 LM",
    "vin": "JTNK4RBE0N3123456"
  }
}
```

---

### Get Single Vehicle

**Endpoint:** `GET /api/vehicles/{id}`

**Response (200 OK):**
```json
{
  "vehicle": {
    "id": 1,
    "make": "Chevrolet",
    "model": "Lacetti",
    "year": 2015,
    "plate_number": "01 A 123 BC",
    "client": {
      "id": 1,
      "name": "Abbos Karimov"
    },
    "service_logs": [
      {
        "id": 1,
        "service_date": "2025-11-05",
        "odometer_reading": 85000,
        "service_type": "Yog' almashtirish",
        "cost": 450000,
        "reminders": [
          {
            "id": 1,
            "scheduled_date": "2025-12-01",
            "status": "pending"
          }
        ]
      }
    ]
  }
}
```

---

### Update Vehicle

**Endpoint:** `PUT /api/vehicles/{id}`

**Request Body:**
```json
{
  "client_id": 1,
  "make": "Chevrolet",
  "model": "Lacetti (Yangilangan)",
  "year": 2015,
  "plate_number": "01 A 123 BC",
  "vin": "KL1SF68Y38B123456"
}
```

**Response (200 OK):**
```json
{
  "message": "Vehicle updated successfully",
  "vehicle": {
    "id": 1,
    "make": "Chevrolet",
    "model": "Lacetti (Yangilangan)"
  }
}
```

---

### Delete Vehicle

**Endpoint:** `DELETE /api/vehicles/{id}`

**Response (200 OK):**
```json
{
  "message": "Vehicle deleted successfully"
}
```

---

## 🔧 Service Logs

### List All Service Logs

**Endpoint:** `GET /api/service-logs`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "vehicle_id": 1,
      "service_date": "2025-11-05",
      "odometer_reading": 85000,
      "next_service_km": 5000,
      "avg_monthly_km": 800,
      "service_type": "Yog' almashtirish",
      "cost": 450000,
      "notes": "Yog' va filter almashtirildi",
      "vehicle": {
        "id": 1,
        "make": "Chevrolet",
        "model": "Lacetti",
        "client": {
          "id": 1,
          "name": "Abbos Karimov"
        }
      },
      "created_at": "2025-11-15 10:00:00"
    }
  ]
}
```

---

### Create New Service Log

**Endpoint:** `POST /api/service-logs`

**Request Body:**
```json
{
  "vehicle_id": 1,
  "service_date": "2025-11-15",
  "odometer_reading": 90000,
  "next_service_km": 5000,
  "avg_monthly_km": 800 (optional),
  "service_type": "Yog' almashtirish",
  "cost": 450000 (optional),
  "notes": "Standart servis" (optional)
}
```

**Response (201 Created):**
```json
{
  "message": "Service log created successfully",
  "service_log": {
    "id": 6,
    "vehicle_id": 1,
    "service_date": "2025-11-15",
    "odometer_reading": 90000,
    "next_service_km": 5000,
    "service_type": "Yog' almashtirish",
    "cost": 450000,
    "reminders": [
      {
        "id": 10,
        "scheduled_date": "2026-04-15",
        "status": "pending"
      },
      {
        "id": 11,
        "scheduled_date": "2026-05-01",
        "status": "pending"
      },
      {
        "id": 12,
        "scheduled_date": "2026-05-08",
        "status": "pending"
      }
    ]
  }
}
```

**Note:** When creating a service log, 3 reminders are automatically created (30, 14, 7 days before next service).

---

### Get Single Service Log

**Endpoint:** `GET /api/service-logs/{id}`

**Response (200 OK):**
```json
{
  "service_log": {
    "id": 1,
    "service_date": "2025-11-05",
    "odometer_reading": 85000,
    "next_service_km": 5000,
    "service_type": "Yog' almashtirish",
    "cost": 450000,
    "notes": "Yog' va filter almashtirildi",
    "vehicle": {
      "id": 1,
      "make": "Chevrolet",
      "model": "Lacetti",
      "client": {
        "id": 1,
        "name": "Abbos Karimov"
      }
    },
    "reminders": [
      {
        "id": 1,
        "scheduled_date": "2025-12-01",
        "status": "pending",
        "notification_type": "telegram"
      }
    ]
  }
}
```

---

### Update Service Log

**Endpoint:** `PUT /api/service-logs/{id}`

**Request Body:**
```json
{
  "vehicle_id": 1,
  "service_date": "2025-11-15",
  "odometer_reading": 90000,
  "next_service_km": 5000,
  "avg_monthly_km": 1000,
  "service_type": "To'liq servis",
  "cost": 850000,
  "notes": "Batafsil tekshirildi"
}
```

**Response (200 OK):**
```json
{
  "message": "Service log updated successfully",
  "service_log": {
    "id": 1,
    "service_type": "To'liq servis",
    "cost": 850000
  }
}
```

---

### Delete Service Log

**Endpoint:** `DELETE /api/service-logs/{id}`

**Response (200 OK):**
```json
{
  "message": "Service log deleted successfully"
}
```

---

## ⚠️ Error Handling

### Validation Error (422)

```json
{
  "message": "The email field is required. (and 1 more error)",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

---

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

---

### Forbidden (403)

```json
{
  "message": "Unauthorized"
}
```

---

### Not Found (404)

```json
{
  "message": "No query results for model [App\\Models\\Client] 99"
}
```

---

### Server Error (500)

```json
{
  "message": "Server Error"
}
```

---

## 📄 Pagination

All list endpoints return paginated data:

```json
{
  "data": [...],
  "links": {
    "first": "http://domain.com/api/clients?page=1",
    "last": "http://domain.com/api/clients?page=3",
    "prev": null,
    "next": "http://domain.com/api/clients?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 20,
    "to": 20,
    "total": 45
  }
}
```

**To get next page:**
```
GET /api/clients?page=2
```

---

## 🔑 Authentication Flow (Flutter)

1. **Register/Login:**
   ```dart
   final response = await http.post(
     Uri.parse('$baseUrl/api/login'),
     headers: {'Content-Type': 'application/json'},
     body: jsonEncode({
       'email': 'admin@oilcontrol.uz',
       'password': 'password'
     }),
   );

   final data = jsonDecode(response.body);
   final token = data['token'];
   // Save token to secure storage
   ```

2. **Use Token:**
   ```dart
   final response = await http.get(
     Uri.parse('$baseUrl/api/clients'),
     headers: {
       'Authorization': 'Bearer $token',
       'Accept': 'application/json',
     },
   );
   ```

3. **Logout:**
   ```dart
   await http.post(
     Uri.parse('$baseUrl/api/logout'),
     headers: {'Authorization': 'Bearer $token'},
   );
   // Delete token from storage
   ```

---

## 🧪 Testing API (Postman/Insomnia)

### 1. Register
```
POST http://localhost:8000/api/register
Body: {
  "name": "Test User",
  "email": "test@test.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### 2. Login
```
POST http://localhost:8000/api/login
Body: {
  "email": "test@test.com",
  "password": "password"
}
```

### 3. Get Dashboard (with token)
```
GET http://localhost:8000/api/dashboard
Headers: {
  "Authorization": "Bearer YOUR_TOKEN_HERE"
}
```

---

**API Version:** 1.0
**Last Updated:** 2025-11-15
**Base URL:** `/api`
