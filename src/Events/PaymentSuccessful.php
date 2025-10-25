<?php

namespace AnyTech\Jinah\Events;

use AnyTech\Jinah\DTOs\WebhookPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessful
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WebhookPayload $payload
    ) {}

    /**
     * Get payment details
     */
    public function getPaymentDetails(): array
    {
        return [
            'service' => $this->payload->service,
            'transaction_id' => $this->payload->transactionId,
            'merchant_order_id' => $this->payload->merchantOrderId,
            'amount' => $this->payload->amount,
            'currency' => $this->payload->currency,
            'status' => $this->payload->status,
            'timestamp' => $this->payload->timestamp,
            'raw_payload' => $this->payload->rawPayload,
        ];
    }
}
