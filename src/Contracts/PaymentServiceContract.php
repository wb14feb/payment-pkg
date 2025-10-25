<?php

namespace AnyTech\Jinah\Contracts;

use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\TransactionInquiry;

interface PaymentServiceContract
{
    /**
     * Initiate a payment process
     */
    public function initiate(PaymentRequest $request): PaymentResponse;

    public function check(string $orderId): PaymentResponse;

    public function getServiceName(): string;

}