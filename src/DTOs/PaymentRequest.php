<?php
declare(strict_types=1);

namespace AnyTech\Jinah\DTOs;

class PaymentRequest
{   
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $description,
        public readonly string $currency = 'IDR',
        public readonly ?string $customerName = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
    ) {}

    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'amount' => $this->amount,
            'description' => $this->description,
            'currency' => $this->currency,
            'customerName' => $this->customerName,
            'customerEmail' => $this->customerEmail,
            'customerPhone' => $this->customerPhone,
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'cancelUrl' => $this->cancelUrl,
        ];
    }

}

