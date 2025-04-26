# Ditusi API Integration

This document provides information about the Ditusi API integration in the TopupIn application.

## Configuration

Ditusi API configuration can be found in `config/ditusi.php`. The following environment variables can be set:

- `DITUSI_BASE_URL`: Production API base URL (default: https://api.ditusi.co.id/api/v1)
- `DITUSI_DEV_BASE_URL`: Development API base URL (default: https://api.ditusi.co.id/api/dev/v1)
- `DITUSI_CLIENT_ID`: Client ID for authentication
- `DITUSI_CLIENT_KEY`: Client key for authentication
- `DITUSI_TOKEN_CACHE_TIME`: Token cache duration in seconds (default: 600 seconds = 10 minutes)

## Authentication

The system uses a token-based authentication system:

1. Tokens are obtained by calling the `/access-token` endpoint with proper headers
2. Tokens are cached for 10 minutes (configurable via `DITUSI_TOKEN_CACHE_TIME`)
3. If a token expires, the system automatically refreshes the token and retries the request

## Data Synchronization

### Importing Games and Products

The application provides an Artisan command to import games and products from the Ditusi API:

```bash
# Import only games
php artisan ditusi:import --games

# Import only products (will import products for each game sequentially)
php artisan ditusi:import --products

# Import both games and products
php artisan ditusi:import --all

# Show debug information including API responses
php artisan ditusi:import --all --debug
```

**Note**: According to the Ditusi API requirements, products can only be fetched per game (by providing a gameCode parameter) or by specifying a specific productCode. It's not possible to fetch all products across all games in a single API call. The import command handles this by fetching products for each game sequentially.

### Scheduled Imports

The import process is scheduled to run daily via Laravel's task scheduler. Make sure your server has a properly configured cron job:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

To change the schedule, edit the `schedule` method in `app/Console/Kernel.php`.

## API Services

The following services are available:

1. `DitusiService::getGames()`: Fetch available games
2. `DitusiService::getProducts()`: Fetch available products
3. `DitusiService::createTransaction()`: Create a new transaction
4. `DitusiService::checkTransaction()`: Check transaction status
5. `DitusiService::checkBalance()`: Check deposit balance

## Models

Games and products are stored in the database using the following models:

- `App\Models\Game`: Stores game information
- `App\Models\Product`: Stores product information

## Token Expiration Handling

The system automatically handles token expiration:

1. Tokens are cached for 10 minutes (configurable)
2. When a request fails with a 401 status code or token-related error message, the system:
   - Clears the token cache
   - Requests a new token
   - Retries the original request with the new token 