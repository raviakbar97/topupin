<?php

namespace App\Services\Ditusi;

class SignatureGenerator
{
    /**
     * Generate signature for authentication (access token).
     *
     * @param  string  $clientKey
     * @param  string  $timestamp
     * @return string
     */
    public function generateAuthSignature(string $clientKey, string $timestamp): string
    {
        $signatureString = $clientKey . ':' . $timestamp;
        return hash('sha256', $signatureString);
    }

    /**
     * Generate signature for service calls.
     *
     * @param  string  $path
     * @param  string  $clientKey
     * @param  string  $timestamp
     * @param  array  $data
     * @return string
     */
    public function generateServiceSignature(string $path, string $clientKey, string $timestamp, array $data = []): string
    {
        $jsonData = empty($data) ? '{}' : json_encode($data);
        $signatureString = $path . ':' . $clientKey . ':' . $timestamp . ':' . $jsonData;
        return hash('sha256', $signatureString);
    }
} 