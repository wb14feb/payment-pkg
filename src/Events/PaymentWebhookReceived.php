<?php

namespace AnyTech\Jinah\Events;

use AnyTech\Jinah\DTOs\WebhookPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WebhookPayload $payload
    ) {}

    /**
     * Get the event name for logging
     */
    public function getEventName(): string
    {
        return "webhook.{$this->payload->service}.{$this->payload->eventType}";
    }

    /**
     * Check if this is a payment success event
     */
    public function isPaymentSuccess(): bool
    {
        return $this->payload->isPaymentSuccessful();
    }

    /**
     * Check if this is a payment failure event
     */
    public function isPaymentFailure(): bool
    {
        return $this->payload->isPaymentFailed();
    }

    /**
     * Check if this is a payment pending event
     */
    public function isPaymentPending(): bool
    {
        return $this->payload->isPaymentPending();
    }

    /**
     * Get transaction information
     */
    public function getTransactionInfo(): array
    {
        return [
            'service' => $this->payload->service,
            'transaction_id' => $this->payload->transactionId,
            'merchant_order_id' => $this->payload->merchantOrderId,
            'status' => $this->payload->status,
            'amount' => $this->payload->amount,
            'currency' => $this->payload->currency,
            'timestamp' => $this->payload->timestamp,
        ];
    }
}