<?php

namespace AnyTech\Jinah\Contracts;

use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\TransactionInquiry;
use AnyTech\Jinah\DTOs\WebhookPayload;

interface PaymentServiceContract
{
    /**
     * Initiate a payment process
     */
    public function initiate(PaymentRequest $request): PaymentResponse;

    public function check(string $orderId): WebhookPayload;

    public function getServiceName(): string;

    public function initiateChannel(PaymentRequest $request, $type): PaymentResponse;

}