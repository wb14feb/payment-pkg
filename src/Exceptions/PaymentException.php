<?php

namespace AnyTech\Jinah\Exceptions;

class PaymentException extends JinahException
{
    public static function invalidAmount(float $amount): self
    {
        return new self("Invalid payment amount: {$amount}. Amount must be greater than 0.", 400);
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency: {$currency}", 400);
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Missing required field: {$field}", 400);
    }

    public static function paymentFailed(string $reason, array $context = []): self
    {
        return new self("Payment failed: {$reason}", 402, null, $context);
    }

    public static function transactionNotFound(string $transactionId): self
    {
        return new self("Transaction not found: {$transactionId}", 404);
    }
}