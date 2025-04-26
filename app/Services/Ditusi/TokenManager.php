<?php

namespace App\Services\Ditusi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenManager
{
    protected SignatureGenerator $signatureGenerator;
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientKey;
    protected int $tokenCacheTime;

    /**
     * Create a new token manager instance.
     */
    public function __construct(SignatureGenerator $signatureGenerator)
    {
        $this->signatureGenerator = $signatureGenerator;
        $this->baseUrl = config('ditusi.base_url'); // Always use production URL for tokens
        $this->clientId = config('ditusi.client_id');
        $this->clientKey = config('ditusi.client_key');
        $this->tokenCacheTime = config('ditusi.token_cache_time');
    }

    /**
     * Get access token.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        // Check if token exists in cache
        if (Cache::has('ditusi_token')) {
            Log::debug("Using cached Ditusi token");
            return Cache::get('ditusi_token');
        }

        // Get new token
        Log::debug("No cached token found, fetching new token");
        return $this->refreshToken();
    }

    /**
     * Refresh access token.
     *
     * @return string|null
     */
    public function refreshToken(): ?string
    {
        try {
            // Generate timestamp in ISO 8601 format with timezone
            $timestamp = now()->format('Y-m-d\TH:i:sP');
            
            // Generate signature according to API documentation:
            // sha256(clientKey + ":" + X-TIMESTAMP)
            $signature = $this->signatureGenerator->generateAuthSignature(
                $this->clientKey,
                $timestamp
            );

            Log::debug("Requesting Ditusi access token", [
                'url' => $this->baseUrl . '/access-token',
                'client_id' => $this->clientId,
                'timestamp' => $timestamp
            ]);

            // Create HTTP client with or without SSL verification based on environment
            $httpClient = Http::withHeaders([
                'X-CLIENT-ID' => $this->clientId,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            // Skip SSL verification in development environment
            if (app()->environment('local', 'development', 'testing')) {
                $httpClient->withoutVerifying();
            }

            // Make request to Ditusi API - always use production URL for access token
            $response = $httpClient->get($this->baseUrl . '/access-token');

            if ($response->successful()) {
                $data = $response->json();
                Log::debug("Ditusi token response", ['data' => $data]);
                
                // According to API docs, the response includes 'accessToken'
                $token = $data['accessToken'] ?? null;
                
                if (!$token) {
                    // Check if it might be in a nested structure
                    if (isset($data['statusCode']) && $data['statusCode'] == 200) {
                        $token = $data['accessToken'] ?? null;
                    }
                }
                
                if ($token) {
                    // Store token in cache
                    $expiryTime = $data['expiryTime'] ?? $data['expiredIn'] ?? $this->tokenCacheTime;
                    Cache::put('ditusi_token', $token, $expiryTime);
                    
                    Log::info("Ditusi token obtained and cached", [
                        'expiry_time' => $expiryTime
                    ]);
                    
                    return $token;
                }
                
                Log::error('Ditusi token response missing accessToken', [
                    'response' => $data,
                ]);
                
                return null;
            }
            
            Log::error('Failed to get Ditusi token', [
                'response' => $response->json(),
                'status' => $response->status(),
                'url' => $this->baseUrl . '/access-token',
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting Ditusi token', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/access-token',
            ]);
            
            return null;
        }
    }
} 