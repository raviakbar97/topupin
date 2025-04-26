# Topupin Project Documentation

## Table of Contents
- [Introduction](#introduction)
- [API Integration](#api-integration)
  - [Ditusi API](#ditusi-api)
  - [Authentication](#authentication)
  - [Token Management](#token-management)
  - [Data Synchronization](#data-synchronization)
- [API Testing](#api-testing)
  - [Postman Setup](#postman-setup)
  - [Authentication Endpoints](#authentication-endpoints)
  - [Games and Products](#games-and-products)
  - [Transactions](#transactions)
  - [Balance](#balance)
- [Features](#features)
- [Completed Work](#completed-work)
- [Issues and Solutions](#issues-and-solutions)
- [Next Steps](#next-steps)
- [Improvements](#improvements)

## Introduction

Topupin is a Laravel-based web application designed to provide game top-up services by integrating with the Ditusi API. The application allows users to browse games and products, make purchases, and track transaction status. This document provides an overview of the project, its current state, and future development plans.

## API Integration

### Ditusi API

The Topupin application integrates with the Ditusi API to fetch game data, product information, and process transactions. The integration is implemented through a dedicated service layer with the following components:

- `DitusiService`: Main service class that provides methods for interacting with the API endpoints
- `TokenManager`: Handles access token lifecycle, including retrieval, caching, and refreshing
- `SignatureGenerator`: Generates secure signatures for API requests according to Ditusi's requirements

#### Configuration

Ditusi API configuration is stored in `config/ditusi.php` and uses the following environment variables:

```env
DITUSI_BASE_URL=https://api.ditusi.co.id/api/v1
DITUSI_DEV_BASE_URL=https://api.ditusi.co.id/api/dev/v1
DITUSI_CLIENT_ID=421341515
DITUSI_CLIENT_KEY=0spOZMo16aFQLKwKz
DITUSI_TOKEN_CACHE_TIME=600
```

### Authentication

The system implements a token-based authentication mechanism:

1. Tokens are obtained by calling the `/access-token` endpoint with appropriate headers
2. Tokens are cached for configurable duration (default: 10 minutes)
3. If a token expires, the system automatically refreshes it and retries the original request

```php
// TokenManager refresh logic
public function refreshToken(): ?string
{
    // Clear existing token from cache
    Cache::forget($this->cacheKey);
    
    // Get fresh token
    return $this->getToken(true);
}
```

### Token Management

The token management system handles token expiration gracefully:

1. When a request fails with a 401 status code or contains token-related error messages
2. The system clears the token cache
3. Requests a new token
4. Automatically retries the original request with the new token

This approach ensures seamless API interactions even when tokens expire during application usage.

### Data Synchronization

The application synchronizes game and product data from the Ditusi API using:

1. An Artisan command for manual imports: `ditusi:import`
2. A scheduled job for automatic daily updates
3. A dedicated job class `SyncDitusiProducts` that can be dispatched to the queue

Available import options:

```bash
# Import only games
php artisan ditusi:import --games

# Import only products
php artisan ditusi:import --products

# Import both games and products
php artisan ditusi:import --all

# Show debug information
php artisan ditusi:import --all --debug
```

> **Note:** According to the Ditusi API requirements, products can only be fetched per game (by providing a gameCode parameter) or by specifying a specific productCode. The import command handles this by fetching products for each game sequentially.

## API Testing

This section provides guidelines for testing the Topupin API endpoints using Postman and other tools. The API follows REST principles and implements OAuth 2.0 authentication via Laravel Sanctum.

### Postman Setup

To test the API using Postman:

1. **Create a new Postman Collection** named "Topupin API"
2. **Set up Environment Variables**:
   - `base_url`: Your local or production API URL (e.g., `http://localhost:8000/api` or `https://topupin.example.com/api`)
   - `token`: This will store the authentication token

3. **Configure Authentication**:
   - For protected endpoints, add the following header:
     - Key: `Authorization`
     - Value: `Bearer {{token}}`

### Authentication Endpoints

#### Register

Register a new user account.

```http
POST {{base_url}}/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Expected Response (201 Created):**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "created_at": "2025-04-24T20:15:30.000000Z",
      "updated_at": "2025-04-24T20:15:30.000000Z"
    },
    "token": "1|laravel_sanctum_xxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

#### Login

Log in with existing credentials to obtain an authentication token.

```http
POST {{base_url}}/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "created_at": "2025-04-24T20:15:30.000000Z",
      "updated_at": "2025-04-24T20:15:30.000000Z"
    },
    "token": "2|laravel_sanctum_xxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

#### Logout

Log out the current user and invalidate the token.

```http
POST {{base_url}}/logout
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

### Games and Products

#### List Games

Retrieve all active games.

```http
GET {{base_url}}/games
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "game_code": "ML",
      "title": "Mobile Legends",
      "product_amount": 5,
      "user_information": {
        "forms": [
          {
            "name": "user_id",
            "type": "text"
          },
          {
            "name": "server",
            "type": "text"
          }
        ]
      },
      "is_active": true,
      "created_at": "2025-04-24T18:30:25.000000Z",
      "updated_at": "2025-04-24T18:30:25.000000Z",
      "products": [
        {
          "id": 1,
          "game_id": 1,
          "product_code": "ML-01",
          "name": "86 Diamonds",
          "code": "ML-86",
          "description": "86 Diamonds for Mobile Legends",
          "price": "19000.00",
          "currency": "IDR",
          "ingame_currency": "Diamonds",
          "is_active": true,
          "created_at": "2025-04-24T18:30:25.000000Z",
          "updated_at": "2025-04-24T18:30:25.000000Z"
        }
      ]
    }
  ]
}
```

#### List Products

Retrieve all active products or filter by game code.

```http
GET {{base_url}}/products?game_code=ML
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "game_id": 1,
      "product_code": "ML-01",
      "name": "86 Diamonds",
      "code": "ML-86",
      "description": "86 Diamonds for Mobile Legends",
      "price": "19000.00",
      "currency": "IDR",
      "ingame_currency": "Diamonds",
      "is_active": true,
      "created_at": "2025-04-24T18:30:25.000000Z",
      "updated_at": "2025-04-24T18:30:25.000000Z",
      "game": {
        "id": 1,
        "game_code": "ML",
        "title": "Mobile Legends",
        "product_amount": 5,
        "user_information": {
          "forms": [
            {
              "name": "user_id",
              "type": "text"
            },
            {
              "name": "server",
              "type": "text"
            }
          ]
        },
        "is_active": true,
        "created_at": "2025-04-24T18:30:25.000000Z",
        "updated_at": "2025-04-24T18:30:25.000000Z"
      }
    }
  ]
}
```

### Transactions

#### Create Transaction

Create a new transaction for a product.

```http
POST {{base_url}}/transactions
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "product_code": "ML-01",
  "amount": 1,
  "additional_information": {
    "user_id": "123456789",
    "server": "1234"
  }
}
```

**Expected Response (201 Created):**
```json
{
  "status": "success",
  "message": "Transaction created successfully",
  "data": {
    "transaction": {
      "id": 1,
      "user_id": 1,
      "reference_id": "TOP-ABCDEF12345",
      "transaction_id": "DTI-XXXXXXXX",
      "product_code": "ML-01",
      "product_name": "86 Diamonds",
      "amount": 1,
      "price": "19000.00",
      "status": "PENDING",
      "additional_information": {
        "user_id": "123456789",
        "server": "1234"
      },
      "created_at": "2025-04-24T20:30:45.000000Z",
      "updated_at": "2025-04-24T20:30:45.000000Z"
    },
    "ditusi_response": {
      "statusCode": 200,
      "message": "Transaction created",
      "transactionId": "DTI-XXXXXXXX",
      "statusTransaction": "PENDING"
    }
  }
}
```

#### Check Transaction Status

Check the status of an existing transaction.

```http
GET {{base_url}}/transactions/TOP-ABCDEF12345
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "transaction": {
      "id": 1,
      "user_id": 1,
      "reference_id": "TOP-ABCDEF12345",
      "transaction_id": "DTI-XXXXXXXX",
      "product_code": "ML-01",
      "product_name": "86 Diamonds",
      "amount": 1,
      "price": "19000.00",
      "status": "SUCCESS",
      "additional_information": {
        "user_id": "123456789",
        "server": "1234"
      },
      "created_at": "2025-04-24T20:30:45.000000Z",
      "updated_at": "2025-04-24T20:35:12.000000Z"
    }
  }
}
```

### Balance

Check the current deposit balance.

```http
GET {{base_url}}/balance
Authorization: Bearer {{token}}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "balance": 1000000,
    "partner_type": "RESELLER",
    "raw_response": {
      "statusCode": 200,
      "message": "Success",
      "data": {
        "data": {
          "DepositBalance": 1000000,
          "partnerType": "RESELLER"
        }
      }
    }
  }
}
```

## Features

The Topupin application currently provides the following features:

1. **Game Catalog**
   - Display list of available games
   - Show game details including supported user identification methods

2. **Product Management**
   - Display products for each game
   - Filter products by various criteria
   - Show product details including price and description

3. **Transaction Processing**
   - Create new transactions
   - Check transaction status
   - View transaction history

4. **Data Synchronization**
   - Manual sync via Artisan commands
   - Automatic daily updates via scheduled tasks
   - On-demand updates through admin interface

5. **API Services**
   - `DitusiService::getGames()`: Fetch available games
   - `DitusiService::getProducts()`: Fetch available products
   - `DitusiService::createTransaction()`: Create a new transaction
   - `DitusiService::checkTransaction()`: Check transaction status
   - `DitusiService::checkBalance()`: Check deposit balance

## Completed Work

- [x] Initial Laravel project setup and configuration
- [x] Ditusi API integration architecture
- [x] Token-based authentication system
- [x] Token refresh mechanism
- [x] Game and product data models
- [x] Database migrations and relationships
- [x] Data synchronization commands
- [x] Scheduled automatic updates
- [x] Product price handling enhancements (increased decimal precision)
- [x] API endpoints for accessing game and product data
- [x] Removal of sample data seeders in favor of direct API imports

## Issues and Solutions

During the development process, several challenges were encountered and addressed:

### 1. Route Service Provider Configuration

**Issue:** The application needed custom route grouping and configuration that wasn't immediately clear in the default Laravel setup.

**Solution:** Located and modified the `RouteServiceProvider.php` file to properly define API routes and apply middleware groups.

```php
// Modified RouteServiceProvider to include API routes
public function boot(): void
{
    $this->routes(function () {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));
            
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    });
}
```

### 2. Token Expiration and Refresh

**Issue:** API tokens would expire during application usage, causing request failures.

**Solution:** Implemented a robust token management system that:
- Detects token expiration based on API response
- Automatically refreshes expired tokens
- Retries the original request with a new token
- Uses caching to minimize unnecessary token requests

### 3. Product Price Column Size

**Issue:** Some product prices exceeded the default decimal column size (10,2), causing database insertion errors.

**Solution:** Modified the products table migration to increase the decimal precision:

```php
// Changed from
$table->decimal('price', 10, 2);

// To
$table->decimal('price', 16, 2);
```

### 4. Sample Data vs. API Data

**Issue:** Initially, the application used database seeders to create sample games and products, but this data was not synchronized with the actual Ditusi API data.

**Solution:** 
- Implemented robust API synchronization through the `ditusi:import` command
- Removed the sample data seeders (`GamesAndProductsSeeder`)
- All game and product data is now directly sourced from the Ditusi API

## Next Steps

The following tasks are planned for the next development phases:

- [ ] User authentication and authorization system
- [ ] Admin dashboard for monitoring and management
- [ ] Transaction processing and payment integration
- [ ] User interface for browsing games and products
- [ ] Order history and transaction tracking
- [ ] Implement caching for frequently accessed data
- [ ] Add comprehensive logging and monitoring
- [ ] Implement rate limiting for API requests
- [ ] Create API documentation
- [ ] Set up automated testing

## Improvements

Several enhancements could be implemented to improve the current system:

### 1. Enhanced Error Handling

Implement more robust error handling with:
- Detailed error logging
- Retry mechanisms with exponential backoff
- Alerting system for recurring API issues

### 2. Performance Optimization

Improve performance through:
- Implementing Redis caching for API responses
- Adding database indexes on frequently queried columns
- Optimizing database queries with eager loading

### 3. API Integration Improvements

Enhance the API integration with:
- Circuit breaker pattern to prevent cascading failures
- Webhook support for real-time updates
- Batch processing for high-volume operations

### 4. User Experience Enhancements

Improve user experience with:
- Real-time transaction status updates
- Responsive design for mobile users
- Streamlined checkout process

### 5. Security Enhancements

Strengthen application security with:
- Implement rate limiting for authentication attempts
- Add 2FA for administrative accounts
- Regular security audits
- Input validation and sanitization

> **Important:** Always keep Ditusi API credentials secure and never expose them in client-side code or public repositories. 