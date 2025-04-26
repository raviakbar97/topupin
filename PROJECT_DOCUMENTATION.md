<!-- PROJECT_DOCUMENTATION.md - Viewable as HTML with inline styles -->

<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: #333;
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
}
h1, h2, h3, h4 {
  color: #2c3e50;
  margin-top: 30px;
}
h1 {
  border-bottom: 2px solid #3498db;
  padding-bottom: 10px;
  text-align: center;
}
h2 {
  border-bottom: 1px solid #eaecef;
  padding-bottom: 8px;
}
code {
  font-family: 'Consolas', 'Monaco', monospace;
  background-color: #f6f8fa;
  padding: 2px 5px;
  border-radius: 3px;
}
pre {
  background-color: #f6f8fa;
  border-radius: 6px;
  padding: 16px;
  overflow: auto;
}
blockquote {
  border-left: 4px solid #3498db;
  padding-left: 20px;
  margin-left: 0;
  color: #555;
}
a {
  color: #3498db;
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}
table {
  border-collapse: collapse;
  width: 100%;
  margin: 20px 0;
}
table, th, td {
  border: 1px solid #ddd;
}
th, td {
  padding: 12px;
  text-align: left;
}
th {
  background-color: #f2f2f2;
}
tr:hover {
  background-color: #f5f5f5;
}
.highlight {
  background-color: #ffffcc;
  padding: 2px;
}
.tip {
  background-color: #e8f4f8;
  border-left: 4px solid #3498db;
  padding: 12px;
  margin: 20px 0;
  border-radius: 4px;
}
.warning {
  background-color: #fff8dc;
  border-left: 4px solid #f1c40f;
  padding: 12px;
  margin: 20px 0;
  border-radius: 4px;
}
.checklist {
  list-style-type: none;
  padding-left: 0;
}
.checklist li {
  position: relative;
  padding-left: 30px;
  margin-bottom: 10px;
}
.checklist li:before {
  content: '';
  position: absolute;
  left: 0;
  top: 2px;
  width: 20px;
  height: 20px;
  border: 1px solid #3498db;
  border-radius: 3px;
}
.checklist li.checked:before {
  background-color: #3498db;
  content: '✓';
  color: white;
  text-align: center;
  line-height: 20px;
}
.code-container {
  position: relative;
}
.copy-btn {
  position: absolute;
  top: 5px;
  right: 5px;
  background-color: #f2f2f2;
  border: none;
  border-radius: 3px;
  padding: 5px 10px;
  cursor: pointer;
  font-size: 12px;
}
.copy-btn:hover {
  background-color: #e0e0e0;
}
@media (max-width: 768px) {
  body {
    padding: 15px;
  }
}
#toc {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 5px;
  margin-bottom: 30px;
}
#toc ul {
  list-style-type: none;
  padding-left: 20px;
}
</style>

# Topupin Project Documentation

<div id="toc">
  <h2>Table of Contents</h2>
  <ul>
    <li><a href="#introduction">Introduction</a></li>
    <li><a href="#api-integration">API Integration</a>
      <ul>
        <li><a href="#ditusi-api">Ditusi API</a></li>
        <li><a href="#authentication">Authentication</a></li>
        <li><a href="#token-management">Token Management</a></li>
        <li><a href="#data-synchronization">Data Synchronization</a></li>
      </ul>
    </li>
    <li><a href="#api-testing">API Testing</a>
      <ul>
        <li><a href="#postman-setup">Postman Setup</a></li>
        <li><a href="#authentication-endpoints">Authentication Endpoints</a></li>
        <li><a href="#games-and-products">Games and Products</a></li>
        <li><a href="#transactions">Transactions</a></li>
        <li><a href="#balance">Balance</a></li>
      </ul>
    </li>
    <li><a href="#features">Features</a></li>
    <li><a href="#completed-work">Completed Work</a></li>
    <li><a href="#issues-and-solutions">Issues and Solutions</a></li>
    <li><a href="#next-steps">Next Steps</a></li>
    <li><a href="#improvements">Potential Improvements</a></li>
  </ul>
</div>

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

```php
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

<div class="code-container">
<pre><code class="language-php">// TokenManager refresh logic
public function refreshToken(): ?string
{
    // Clear existing token from cache
    Cache::forget($this->cacheKey);
    
    // Get fresh token
    return $this->getToken(true);
}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

<div class="tip">
<strong>Note:</strong> According to the Ditusi API requirements, products can only be fetched per game (by providing a gameCode parameter) or by specifying a specific productCode. The import command handles this by fetching products for each game sequentially.
</div>

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

<div class="code-container">
<pre><code class="language-http">POST {{base_url}}/register

Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

**cURL Example:**
```bash
curl -X POST "http://localhost:8000/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login

Log in with existing credentials to obtain an authentication token.

<div class="code-container">
<pre><code class="language-http">POST {{base_url}}/login

Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

**cURL Example:**
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### Logout

Log out the current user and invalidate the token.

<div class="code-container">
<pre><code class="language-http">POST {{base_url}}/logout

Authorization: Bearer {{token}}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

**cURL Example:**
```bash
curl -X POST "http://localhost:8000/api/logout" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Games and Products

The following endpoints provide access to games and products data.

#### List Games

Retrieve all active games.

<div class="code-container">
<pre><code class="language-http">GET {{base_url}}/games

Authorization: Bearer {{token}}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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
        // ... more products
      ]
    }
    // ... more games
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/games" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### List Products

Retrieve all active products or filter by game code.

<div class="code-container">
<pre><code class="language-http">GET {{base_url}}/products?game_code=ML

Authorization: Bearer {{token}}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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
    // ... more products
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/products?game_code=ML" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Transactions

The following endpoints allow creating and checking transactions.

#### Create Transaction

Create a new transaction for a product.

<div class="code-container">
<pre><code class="language-http">POST {{base_url}}/transactions

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
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

**cURL Example:**
```bash
curl -X POST "http://localhost:8000/api/transactions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_code": "ML-01",
    "amount": 1,
    "additional_information": {
      "user_id": "123456789",
      "server": "1234"
    }
  }'
```

#### Check Transaction Status

Check the status of an existing transaction.

<div class="code-container">
<pre><code class="language-http">GET {{base_url}}/transactions/TOP-ABCDEF12345

Authorization: Bearer {{token}}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/transactions/TOP-ABCDEF12345" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Balance

Check the current deposit balance.

<div class="code-container">
<pre><code class="language-http">GET {{base_url}}/balance

Authorization: Bearer {{token}}
</code></pre>
<button class="copy-btn" onclick="copyCode(this)">Copy</button>
</div>

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

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/balance" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

<div class="tip">
<strong>Tip:</strong> When testing the API, consider creating a Postman Collection to save all these requests. You can also set up a collection-level variable for the auth token that is automatically updated when you login.
</div>

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

<ul class="checklist">
  <li class="checked">Initial Laravel project setup and configuration</li>
  <li class="checked">Ditusi API integration architecture</li>
  <li class="checked">Token-based authentication system</li>
  <li class="checked">Token refresh mechanism</li>
  <li class="checked">Game and product data models</li>
  <li class="checked">Database migrations and relationships</li>
  <li class="checked">Data synchronization commands</li>
  <li class="checked">Scheduled automatic updates</li>
  <li class="checked">Product price handling enhancements (increased decimal precision)</li>
  <li class="checked">API endpoints for accessing game and product data</li>
  <li class="checked">Removal of sample data seeders in favor of direct API imports</li>
</ul>

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

<ul class="checklist">
  <li>User authentication and authorization system</li>
  <li>Admin dashboard for monitoring and management</li>
  <li>Transaction processing and payment integration</li>
  <li>User interface for browsing games and products</li>
  <li>Order history and transaction tracking</li>
  <li>Implement caching for frequently accessed data</li>
  <li>Add comprehensive logging and monitoring</li>
  <li>Implement rate limiting for API requests</li>
  <li>Create API documentation</li>
  <li>Set up automated testing</li>
</ul>

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

<div class="warning">
<strong>Important:</strong> Always keep Ditusi API credentials secure and never expose them in client-side code or public repositories.
</div>

<script>
// Function to generate table of contents
document.addEventListener('DOMContentLoaded', function() {
  // Already have static TOC
});

// Function to make checklist items interactive
document.addEventListener('DOMContentLoaded', function() {
  const checklistItems = document.querySelectorAll('.checklist li:not(.checked)');
  checklistItems.forEach(item => {
    item.addEventListener('click', function() {
      this.classList.toggle('checked');
    });
  });
});

// Function to copy code snippets
function copyCode(button) {
  const codeBlock = button.previousElementSibling;
  const code = codeBlock.textContent;
  
  navigator.clipboard.writeText(code).then(() => {
    const originalText = button.textContent;
    button.textContent = 'Copied!';
    setTimeout(() => {
      button.textContent = originalText;
    }, 2000);
  }).catch(err => {
    console.error('Failed to copy text: ', err);
  });
}

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        window.scrollTo({
          top: target.offsetTop,
          behavior: 'smooth'
        });
      }
    });
  });
});
</script> 