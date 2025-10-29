<?php

namespace AnyTech\Jinah\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

class WebhookPayload
{
    public function __construct(
        public readonly string $service,
        public readonly string $eventType,
        public readonly ?string $transactionId = null,
        public readonly ?string $merchantOrderId = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?Carbon $timestamp = null,
        public readonly array $rawPayload = [],
        public readonly array $metadata = [],
        public readonly ?string $paymentMethod = null,
    ) {}

    /**
     * Auto-detect service and create appropriate WebhookPayload
     */
    public static function fromRequest(Request $request, ?string $hintService = null): self
    {
        $service = $hintService ?? self::detectServiceFromRequest($request);
        
        return match ($service) {
            'finpay' => self::fromFinPayRequest($request),
            'stripe' => self::fromStripeRequest($request),
            'midtrans' => self::fromMidtransRequest($request),
            default => self::fromGenericRequest($service, $request),
        };
    }

    /**
     * Auto-detect service type from request headers and payload
     */
    public static function detectServiceFromRequest(Request $request): string
    {
        // Check headers for service-specific signatures
        if ($request->hasHeader('Stripe-Signature')) {
            return 'stripe';
        }
        
        if ($request->hasHeader('client-id') && $request->header('client-id') === 'FINPAY') {
            return 'finpay';
        }

        if ($request->hasHeader('client-id') && is_array($request->header('client-id')) && $request->header('client-id')[0] === 'FINPAY') {
            return 'finpay';
        }

        Log::warning("Unable to detect service from headers.", ['headers' => $request->headers->all()]);

        // Check payload structure for service-specific fields
        $data = $request->all();
        
        // Stripe webhook structure detection
        if (isset($data['type']) && isset($data['data']['object']) && isset($data['api_version'])) {
            return 'stripe';
        }
        
        // Midtrans webhook structure detection
        if (isset($data['transaction_status']) && isset($data['order_id']) && isset($data['signature_key'])) {
            return 'midtrans';
        }
        
        // FinPay webhook structure detection
        if (isset($data['transaction_id']) && isset($data['merchant_order_id']) && 
            (isset($data['event_type']) || isset($data['transaction_status']))) {
            return 'finpay';
        }

        // Default to generic if no specific pattern detected
        return 'finpay';
    }

    /**
     * Create WebhookPayload from FinPay request
     */
    public static function fromFinPayRequest(Request $request): self
    {
        $data = $request->all();
        return static::fromFinpay([
            ...$data,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
                'merchant_id' => $data['merchant']['id'] ?? null,
                'payment_type' => $data['sourceOfFunds']['type'] ?? null,
                'channel' => $data['result']['payment']['channel'] ?? null,
                'signature' => $data['signature'] ?? null,
            ],
        ]);
    }

    public static function fromFinpay(array $data) {
        return new self(
            service: 'finpay',
            eventType: $data['event_type'] ?? 'payment.notification',
            transactionId: $data['order']['reference'] ?? $data['transaction_id'] ?? null,
            merchantOrderId: $data['order']['id'] ?? $data['merchant_order_id'] ?? null,
            status: self::mapFinPayStatus($data['result']['payment']['status'] ?? $data['transaction_status'] ?? $data['status'] ?? null),
            amount: isset($data['order']['amount']) ? (float) $data['order']['amount'] : (isset($data['amount']) ? (float) $data['amount'] : null),
            currency: $data['order']['currency'] ?? $data['currency'] ?? 'IDR',
            timestamp: isset($data['result']['payment']['datetime']) ? Carbon::parse($data['result']['payment']['datetime']) : (isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now()),
            rawPayload: $data,
            metadata: $data['metadata'] ?? [],
            paymentMethod: $data['sourceOfFunds']['type'] ?? null,
        );
    }

    /**
     * Map FinPay status to standardized status
     */
    private static function mapFinPayStatus(?string $status): ?string
    {
        return match ($status) {
            'PAID' => 'completed',
            'CAPTURED' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'failed',
            default => $status,
        };
    }

    /**
     * Create WebhookPayload from Stripe request
     */
    public static function fromStripeRequest(Request $request): self
    {
        $data = $request->all();
        $eventData = $data['data']['object'] ?? [];
        
        return new self(
            service: 'stripe',
            eventType: $data['type'] ?? 'payment.notification',
            transactionId: $eventData['id'] ?? null,
            merchantOrderId: $eventData['metadata']['merchant_order_id'] ?? null,
            status: self::mapStripeStatus($eventData['status'] ?? null),
            amount: isset($eventData['amount']) ? (float) $eventData['amount'] / 100 : null, // Stripe uses cents
            currency: strtoupper($eventData['currency'] ?? 'USD'),
            timestamp: isset($eventData['created']) ? Carbon::createFromTimestamp($eventData['created']) : Carbon::now(),
            rawPayload: $data,
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
                'stripe_event_id' => $data['id'] ?? null,
            ]
        );
    }

    /**
     * Create WebhookPayload from Midtrans request
     */
    public static function fromMidtransRequest(Request $request): self
    {
        $data = $request->all();
        
        return new self(
            service: 'midtrans',
            eventType: 'payment.notification',
            transactionId: $data['transaction_id'] ?? null,
            merchantOrderId: $data['order_id'] ?? null,
            status: self::mapMidtransStatus($data['transaction_status'] ?? null),
            amount: isset($data['gross_amount']) ? (float) $data['gross_amount'] : null,
            currency: 'IDR',
            timestamp: isset($data['transaction_time']) ? Carbon::parse($data['transaction_time']) : Carbon::now(),
            rawPayload: $data,
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
                'payment_type' => $data['payment_type'] ?? null,
                'fraud_status' => $data['fraud_status'] ?? null,
            ]
        );
    }

    /**
     * Create WebhookPayload from generic request
     */
    public static function fromGenericRequest(string $service, Request $request): self
    {
        $data = $request->all();
        
        return new self(
            service: $service,
            eventType: $data['event_type'] ?? 'payment.notification',
            transactionId: $data['transaction_id'] ?? $data['id'] ?? null,
            merchantOrderId: $data['merchant_order_id'] ?? $data['order_id'] ?? null,
            status: $data['status'] ?? $data['transaction_status'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? 'IDR',
            timestamp: isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now(),
            rawPayload: $data,
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]
        );
    }

    /**
     * Map Stripe status to standardized status
     */
    private static function mapStripeStatus(?string $status): ?string
    {
        return match ($status) {
            'succeeded' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'canceled' => 'cancelled',
            'requires_payment_method' => 'pending',
            'requires_confirmation' => 'pending',
            'requires_action' => 'pending',
            'processing' => 'processing',
            default => $status,
        };
    }

    /**
     * Map Midtrans status to standardized status
     */
    private static function mapMidtransStatus(?string $status): ?string
    {
        return match ($status) {
            'capture', 'settlement' => 'completed',
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'failed',
            'refund' => 'refunded',
            'partial_refund' => 'partial_refund',
            default => $status,
        };
    }

    /**
     * Check if the webhook indicates a successful payment
     */
    public function isPaymentSuccessful(): bool
    {
        return in_array(strtolower($this->status ?? ''), [
            'completed', 'success', 'paid', 'settlement', 'capture'
        ]);
    }

    /**
     * Check if the webhook indicates a failed payment
     */
    public function isPaymentFailed(): bool
    {
        return in_array(strtolower($this->status ?? ''), [
            'failed', 'cancelled', 'expired', 'deny', 'cancel', 'expire'
        ]);
    }

    /**
     * Check if the webhook indicates a pending payment
     */
    public function isPaymentPending(): bool
    {
        return in_array(strtolower($this->status ?? ''), [
            'pending', 'waiting', 'processing'
        ]);
    }

    /**
     * Get webhook payload as array
     */
    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'event_type' => $this->eventType,
            'transaction_id' => $this->transactionId,
            'merchant_order_id' => $this->merchantOrderId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'timestamp' => $this->timestamp?->toISOString(),
            'raw_payload' => $this->rawPayload,
            'metadata' => $this->metadata,
            'payment_method' => $this->paymentMethod,
        ];
    }

    /**
     * Get specific field from raw payload
     */
    public function getRawField(string $key, $default = null): mixed
    {
        return data_get($this->rawPayload, $key, $default);
    }

    /**
     * Get metadata field
     */
    public function getMetadata(string $key, $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}