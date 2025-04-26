<?php

namespace App\Services\Ditusi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DitusiService
{
    protected TokenManager $tokenManager;
    protected SignatureGenerator $signatureGenerator;
    protected string $baseUrl;
    protected string $devBaseUrl;
    protected string $clientId;
    protected string $clientKey;

    /**
     * Create a new Ditusi service instance.
     */
    public function __construct(TokenManager $tokenManager, SignatureGenerator $signatureGenerator)
    {
        $this->tokenManager = $tokenManager;
        $this->signatureGenerator = $signatureGenerator;
        $this->baseUrl = config('ditusi.base_url');
        $this->devBaseUrl = config('ditusi.dev_base_url');
        $this->clientId = config('ditusi.client_id');
        $this->clientKey = config('ditusi.client_key');
    }

    /**
     * Get list of games.
     *
     * @param  string|null  $gameCode
     * @return array|null
     */
    public function getGames(?string $gameCode = null): ?array
    {
        $params = [];
        if ($gameCode) {
            $params['gameCode'] = $gameCode;
        }
        
        return $this->makeRequest('GET', '/game', $params, [], true); // Use dev endpoint
    }

    /**
     * Get list of products.
     *
     * @param  string|null  $gameCode
     * @param  string|null  $productCode
     * @return array|null
     */
    public function getProducts(?string $gameCode = null, ?string $productCode = null): ?array
    {
        $params = [];
        if ($gameCode) {
            $params['gameCode'] = $gameCode;
        }
        if ($productCode) {
            $params['productCode'] = $productCode;
        }
        
        Log::debug("Fetching products", [
            'params' => $params,
            'endpoint' => '/product',
            'base_url' => $this->devBaseUrl,
        ]);
        
        $result = $this->makeRequest('GET', '/product', $params, [], true); // Use dev endpoint
        
        if (!$result) {
            Log::error("Failed to fetch products from Ditusi API", [
                'params' => $params,
                'endpoint' => '/product',
            ]);
        }
        
        return $result;
    }

    /**
     * Create transaction.
     *
     * @param  array  $data
     * @return array|null
     */
    public function createTransaction(array $data): ?array
    {
        return $this->makeRequest('POST', '/transaction', [], $data, true); // Use dev endpoint
    }

    /**
     * Check transaction status.
     *
     * @param  string  $transactionId
     * @return array|null
     */
    public function checkTransaction(string $transactionId): ?array
    {
        return $this->makeRequest('GET', "/transaction/{$transactionId}", [], [], true); // Use dev endpoint
    }

    /**
     * Check deposit balance.
     *
     * @return array|null
     */
    public function checkBalance(): ?array
    {
        return $this->makeRequest('GET', '/balance', [], [], true); // Use dev endpoint
    }

    /**
     * Make request to Ditusi API.
     *
     * @param  string  $method
     * @param  string  $endpoint
     * @param  array  $queryParams
     * @param  array  $bodyParams
     * @param  bool  $useDev  Whether to use the dev endpoint
     * @return array|null
     */
    protected function makeRequest(string $method, string $endpoint, array $queryParams = [], array $bodyParams = [], bool $useDev = false): ?array
    {
        return $this->makeRequestWithRetry($method, $endpoint, $queryParams, $bodyParams, $useDev);
    }

    /**
     * Make request to Ditusi API with retry for token issues.
     *
     * @param  string  $method
     * @param  string  $endpoint
     * @param  array  $queryParams
     * @param  array  $bodyParams
     * @param  bool  $useDev  Whether to use the dev endpoint
     * @param  bool  $isRetry  Whether this is a retry attempt
     * @return array|null
     */
    protected function makeRequestWithRetry(string $method, string $endpoint, array $queryParams = [], array $bodyParams = [], bool $useDev = false, bool $isRetry = false): ?array
    {
        try {
            // Get token
            $token = $isRetry 
                ? $this->refreshTokenForce() 
                : $this->tokenManager->getToken();
                
            if (!$token) {
                Log::error("Failed to get Ditusi access token");
                return null;
            }

            // Determine which base URL to use
            $url = $useDev ? $this->devBaseUrl : $this->baseUrl;

            // Generate timestamp in ISO 8601 format with timezone
            $timestamp = now()->format('Y-m-d\TH:i:sP');
            
            // Generate signature based on API documentation
            $data = $method === 'GET' ? $queryParams : $bodyParams;
            
            // Extract API version from baseUrl (api/v1 or api/dev/v1)
            $apiPathParts = explode('/', parse_url($url, PHP_URL_PATH));
            $apiPath = '/' . implode('/', array_slice($apiPathParts, 1)); // Should be /api/dev/v1 or /api/v1
            
            $signature = $this->signatureGenerator->generateServiceSignature(
                $apiPath . $endpoint,
                $this->clientKey,
                $timestamp,
                $data
            );

            // Prepare request
            $request = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-CLIENT-ID' => $this->clientId,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            // Skip SSL verification in development environment
            if (app()->environment('local', 'development', 'testing')) {
                $request->withoutVerifying();
            }

            // Make request
            $response = match ($method) {
                'GET' => $request->get($url . $endpoint, $queryParams),
                'POST' => $request->post($url . $endpoint, $bodyParams),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            // Log request details for debugging
            Log::debug("Ditusi API request", [
                'method' => $method,
                'url' => $url . $endpoint,
                'headers' => [
                    'X-CLIENT-ID' => $this->clientId,
                    'X-TIMESTAMP' => $timestamp,
                    'X-SIGNATURE' => $signature,
                ],
                'query_params' => $queryParams,
                'body_params' => $bodyParams,
            ]);

            // Check if request was successful
            if ($response->successful()) {
                $responseData = $response->json();
                Log::debug("Ditusi API response", [
                    'status' => $response->status(),
                    'data' => $responseData,
                ]);
                return $responseData;
            }
            
            // Check for token expiration error and retry
            $responseData = $response->json();
            $statusCode = $response->status();
            
            // Log the exact error response for diagnosis
            Log::warning("Ditusi API non-successful response", [
                'status' => $statusCode,
                'response' => $responseData,
                'endpoint' => $endpoint,
                'method' => $method,
                'url' => $url . $endpoint,
                'query_params' => $queryParams,
            ]);
            
            // Check for common token expiration indicators (401 status, or specific error messages)
            $isTokenError = $statusCode === 401 || 
                (isset($responseData['message']) && strpos(strtolower($responseData['message']), 'token') !== false && 
                (strpos(strtolower($responseData['message']), 'expired') !== false || 
                 strpos(strtolower($responseData['message']), 'invalid') !== false));
            
            if ($isTokenError && !$isRetry) {
                Log::warning("Token expired or invalid. Retrying with fresh token", [
                    'status' => $statusCode,
                    'error' => $responseData['message'] ?? 'Unknown error',
                ]);
                
                // Retry once with a fresh token
                return $this->makeRequestWithRetry($method, $endpoint, $queryParams, $bodyParams, $useDev, true);
            }
            
            Log::error("Failed Ditusi API request: {$endpoint}", [
                'response' => $responseData,
                'status' => $statusCode,
                'method' => $method,
                'url' => $url . $endpoint,
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("Error making Ditusi API request: {$endpoint}", [
                'error' => $e->getMessage(),
                'method' => $method,
                'url' => ($useDev ? $this->devBaseUrl : $this->baseUrl) . $endpoint,
            ]);
            
            return null;
        }
    }
    
    /**
     * Force refresh the token by clearing cache and getting a new one.
     *
     * @return string|null
     */
    protected function refreshTokenForce(): ?string
    {
        // Clear cached token
        Cache::forget('ditusi_token');
        
        // Get fresh token
        return $this->tokenManager->refreshToken();
    }
} 