<?php

namespace AnyTech\Jinah\DTOs;

class TransactionInquiry
{
    public function __construct(
        public readonly ?string $transactionId = null,
        public readonly ?string $merchantOrderId = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'transaction_id' => $this->transactionId,
            'merchant_order_id' => $this->merchantOrderId,
        ], fn($value) => !is_null($value));
    }

    public static function byTransactionId(string $transactionId): self
    {
        return new self(transactionId: $transactionId);
    }

    public static function byMerchantOrderId(string $merchantOrderId): self
    {
        return new self(merchantOrderId: $merchantOrderId);
    }
}