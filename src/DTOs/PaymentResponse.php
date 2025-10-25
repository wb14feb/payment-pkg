<?php

namespace AnyTech\Jinah\DTOs;

use Carbon\Carbon;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $via = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $merchantOrderId = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $message = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?Carbon $expiryTime = null,
        public readonly array $rawResponse = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'merchant_order_id' => $this->merchantOrderId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_url' => $this->paymentUrl,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'raw_response' => $this->rawResponse,
        ];
    }

    public static function success(array $data): self
    {
        return new self(
            success: true,
            transactionId: $data['transaction_id'] ?? null,
            merchantOrderId: $data['merchant_order_id'] ?? null,
            status: $data['status'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            paymentUrl: $data['payment_url'] ?? null,
            message: $data['message'] ?? null,
            rawResponse: $data
        );
    }

    public static function failed(string $message, ?string $errorCode = null, array $rawResponse = []): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: $errorCode,
            rawResponse: $rawResponse
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['pending', 'waiting', 'processing']);
    }

    public function isCompleted(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['completed', 'success', 'paid']);
    }

    public function isFailed(): bool
    {
        return in_array(strtolower($this->status ?? ''), ['failed', 'cancelled', 'expired']);
    }
}