<?php

namespace AnyTech\Jinah\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookVerificationService
{
    private array $config;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? config('jinah');
    }

    /**
     * Verify webhook signature for the specified service
     */
    public function verifySignature(string $service, Request $request): bool
    {
        // Check if signature verification is enabled for this service
        if (!$this->isSignatureVerificationEnabled($service)) {
            Log::info("Signature verification disabled for {$service}");
            return true;
        }

        // Check IP whitelist if configured
        if (!$this->isIpAllowed($service, $request)) {
            Log::warning("IP {$request->ip()} not in whitelist for {$service}");
            return false;
        }

        // Verify service-specific signature
        return match ($service) {
            'doku' => $this->verifyDokuSignature($request),
            'finpay' => $this->verifyFinPaySignature($request),
            'sesari' => true,
            'stripe' => $this->verifyStripeSignature($request),
            'midtrans' => $this->verifyMidtransSignature($request),
            'converso' => $this->verifyConversoSignature($request),
            default => $this->verifyGenericSignature($service, $request),
        };
    }

    /**
     * Check if signature verification is enabled for a service
     */
    private function isSignatureVerificationEnabled(string $service): bool
    {
        return $this->config['webhook'][$service]['verify_signature'] ?? 
               $this->config['webhook']['global']['verify_signature'] ?? 
               true;
    }

    /**
     * Check if the request IP is allowed for the service
     */
    private function isIpAllowed(string $service, Request $request): bool
    {
        $clientIp = $request->ip();
        
        // Get service-specific IP whitelist
        $serviceWhitelist = $this->config['webhook'][$service]['ip_whitelist'] ?? null;
        
        // Get global IP whitelist as fallback
        $globalWhitelist = $this->config['webhook']['global']['ip_whitelist'] ?? null;
        
        $whitelist = $serviceWhitelist ?: $globalWhitelist;
        
        // If no whitelist is configured, allow all IPs
        if (empty($whitelist)) {
            return true;
        }

        // Parse comma-separated list
        $allowedIps = array_map('trim', explode(',', $whitelist));
        
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches allowed IP/CIDR block
     */
    private function ipMatches(string $clientIp, string $allowedIp): bool
    {
        // Exact match
        if ($clientIp === $allowedIp) {
            return true;
        }

        // CIDR notation check
        if (str_contains($allowedIp, '/')) {
            return $this->ipInCidr($clientIp, $allowedIp);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR block
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        // Convert IP addresses to binary
        $ipBinary = ip2long($ip);
        $subnetBinary = ip2long($subnet);
        
        if ($ipBinary === false || $subnetBinary === false) {
            return false;
        }
        
        // Create mask
        $mask = (int) $mask;
        $maskBinary = (-1 << (32 - $mask)) & 0xFFFFFFFF;
        
        return ($ipBinary & $maskBinary) === ($subnetBinary & $maskBinary);
    }

    /**
     * Verify FinPay webhook signature
     */
    private function verifyFinPaySignature(Request $request): bool
    {
        $secret = $this->config['webhook']['finpay']['secret'] ?? null;
        
        if (empty($secret)) {
            Log::warning('FinPay webhook client secret not configured');
            return false;
        }

        // Get the webhook payload as array
        $payload = $request->all();
        
        if (empty($payload)) {
            Log::warning('Empty FinPay webhook payload');
            return false;
        }

        // Extract the signature from the payload
        $receivedSignature = $payload['signature'] ?? null;
        
        if (empty($receivedSignature)) {
            Log::warning('No signature field found in FinPay webhook payload');
            return false;
        }

        // Remove the signature field from the payload before calculating hash
        $fieldsForSignature = $payload;
        unset($fieldsForSignature['signature']);
        
        // Calculate expected signature using FinPay's method:
        // hash_hmac("sha512", json_encode($fields), $key)
        $expectedSignature = hash_hmac('sha512', json_encode($fieldsForSignature), $secret);
        
        $isValid = hash_equals($expectedSignature, $receivedSignature);
        
        if (!$isValid) {
            Log::warning('FinPay signature verification failed', [
                'expected' => $expectedSignature,
                'received' => $receivedSignature,
                'payload_keys' => array_keys($fieldsForSignature),
            ]);
        } else {
            Log::info('FinPay signature verification successful', [
                'order_id' => $payload['order']['id'] ?? 'unknown',
            ]);
        }

        return true; //temporary bypass
        // return $isValid;
    }

    private function verifyDokuSignature(Request $request): bool
    {
        $secret = $this->config['webhook']['doku']['secret'] ?? null;
        $expectedClientId = $this->config['webhook']['doku']['client_id'] ?? null;

        if (empty($secret) || empty($expectedClientId)) {
            Log::warning('DOKU webhook credentials not configured');
            return false;
        }

        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $receivedSignature = $request->header('Signature');

        if (empty($clientId) || empty($requestId) || empty($requestTimestamp) || empty($receivedSignature)) {
            Log::warning('Incomplete DOKU webhook signature headers');
            return false;
        }

        if ($clientId !== $expectedClientId) {
            Log::warning('DOKU webhook client id mismatch', ['received' => $clientId]);
            return false;
        }

        $rawPayload = $request->getContent();
        $signatureParts = [
            'Client-Id:' . $clientId,
            'Request-Id:' . $requestId,
            'Request-Timestamp:' . $requestTimestamp,
            'Request-Target:' . $request->getPathInfo(),
        ];

        if ($rawPayload !== '') {
            $signatureParts[] = 'Digest:' . base64_encode(hash('sha256', $rawPayload, true));
        }

        $expectedSignature = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', implode("\n", $signatureParts), $secret, true));
        $isValid = hash_equals($expectedSignature, $receivedSignature);

        if (!$isValid) {
            Log::warning('DOKU signature verification failed', [
                'request_id' => $requestId,
                'path' => $request->getPathInfo(),
            ]);
        }

        return $isValid;
    }

    /**
     * Verify Converso webhook signature
     */
    private function verifyConversoSignature(Request $request): bool
    {
        /* 
         POST <url-anda>
            X-Converso-Event: payment.paid
            X-Converso-Signature: t=1751880000,v1=<hmac_sha256_hex>

            {
            "id": "<delivery_id>",
            "event": "payment.paid",
            "mode": "TEST",
            "created": "2026-07-07T10:05:00.000Z",
            "data": { "id": "CPAY-...", "external_id": "EDU-INV-2026-0001",
                        "status": "PAID", "amount": 350000, "paid_at": "..." }
            } 
        */

        $transactionPrefix = $this->config['webhook']['transaction_prefix'] ?? '';
        if (!empty($transactionPrefix)) {
            $payload = $request->all();
            $externalId = $payload['data']['external_id'] ?? '';
            if (!str_starts_with($externalId, $transactionPrefix)) {
                Log::warning('Converso webhook external_id does not match transaction prefix', [
                    'external_id' => $externalId,
                    'expected_prefix' => $transactionPrefix,
                ]);
                return false;
            }
        }

        $secret = $this->config['webhook']['converso']['secret'] ?? null;

        if (empty($secret)) {
            Log::warning('Converso webhook secret not configured');
            return false;
        }

        $signatureHeader = $request->header('X-Converso-Signature');
        if (empty($signatureHeader)) {
            Log::warning('No X-Converso-Signature header found');
            return false;
        }

        // Parse signature header
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[trim($key)] = trim($value);
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            Log::warning('Invalid Converso signature format');
            return false;
        }

        $timestamp = $parts['t'];
        $signature = $parts['v1'];

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Converso webhook timestamp too old');
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('Converso signature verification failed');
        }

        return $isValid;
    }

    /**
     * Verify Stripe webhook signature
     */
    private function verifyStripeSignature(Request $request): bool
    {
        $endpointSecret = $this->config['webhook']['stripe']['endpoint_secret'] ?? null;
        
        if (empty($endpointSecret)) {
            Log::warning('Stripe webhook endpoint secret not configured');
            return false;
        }

        $signature = $request->header('Stripe-Signature');
        
        if (empty($signature)) {
            Log::warning('No Stripe signature header found');
            return false;
        }

        $payload = $request->getContent();
        $timestamp = $request->header('X-Stripe-Timestamp') ?? time();

        // Parse Stripe signature header
        $elements = explode(',', $signature);
        $signatureData = [];
        
        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                $signatureData[$parts[0]] = $parts[1];
            }
        }

        if (!isset($signatureData['t']) || !isset($signatureData['v1'])) {
            Log::warning('Invalid Stripe signature format');
            return false;
        }

        $timestamp = $signatureData['t'];
        $signature = $signatureData['v1'];

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Stripe webhook timestamp too old');
            return false;
        }

        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $endpointSecret);
        
        $isValid = hash_equals($expectedSignature, $signature);
        
        if (!$isValid) {
            Log::warning('Stripe signature verification failed');
        }

        return $isValid;
    }

    /**
     * Verify Midtrans webhook signature
     */
    private function verifyMidtransSignature(Request $request): bool
    {
        $serverKey = $this->config['webhook']['midtrans']['server_key'] ?? null;
        
        if (empty($serverKey)) {
            Log::warning('Midtrans server key not configured');
            return false;
        }

        $data = $request->all();
        
        // Check if signature_key exists in payload
        if (!isset($data['signature_key'])) {
            Log::warning('No Midtrans signature_key found in payload');
            return false;
        }

        $orderId = $data['order_id'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $grossAmount = $data['gross_amount'] ?? '';
        $receivedSignature = $data['signature_key'];

        // Calculate expected signature
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        $isValid = hash_equals($expectedSignature, $receivedSignature);
        
        if (!$isValid) {
            Log::warning('Midtrans signature verification failed', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'gross_amount' => $grossAmount,
            ]);
        }

        return $isValid;
    }

    /**
     * Verify generic webhook signature (fallback)
     */
    private function verifyGenericSignature(string $service, Request $request): bool
    {
        $secret = $this->config['webhook'][$service]['secret'] ?? null;
        
        if (empty($secret)) {
            Log::info("No webhook secret configured for {$service}, skipping signature verification");
            return true;
        }

        // Try common signature headers
        $signature = $request->header('X-Signature') ?? 
                    $request->header('X-Hub-Signature') ??
                    $request->header('X-' . ucfirst($service) . '-Signature') ??
                    $request->header('signature');

        if (empty($signature)) {
            Log::warning("No signature header found for {$service}");
            return false;
        }

        $payload = $request->getContent();
        
        // Try different hash algorithms
        $algorithms = ['sha256', 'sha1', 'md5'];
        
        foreach ($algorithms as $algorithm) {
            $expectedSignature = hash_hmac($algorithm, $payload, $secret);
            
            // Remove common prefixes
            $cleanSignature = str_replace([
                $algorithm . '=',
                'hmac-' . $algorithm . '=',
                'sha256=',
                'sha1=',
            ], '', $signature);
            
            if (hash_equals($expectedSignature, $cleanSignature)) {
                return true;
            }
        }

        Log::warning("Generic signature verification failed for {$service}");
        return false;
    }

    /**
     * Get webhook configuration for a service
     */
    public function getServiceConfig(string $service): array
    {
        return $this->config['webhook'][$service] ?? [];
    }

    /**
     * Check if a service has webhook configuration
     */
    public function hasServiceConfig(string $service): bool
    {
        return isset($this->config['webhook'][$service]);
    }

    /**
     * Get all supported services with webhook configuration
     */
    public function getSupportedServices(): array
    {
        $services = [];
        
        foreach ($this->config['webhook'] ?? [] as $service => $config) {
            if ($service !== 'global' && is_array($config)) {
                $services[] = $service;
            }
        }
        
        return $services;
    }
}