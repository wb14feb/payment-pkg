<?php

namespace AnyTech\Jinah;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\TransactionInquiry;
use AnyTech\Jinah\DTOs\WebhookPayload;
use AnyTech\Jinah\Exceptions\PaymentException;
use AnyTech\Jinah\Factories\PaymentServiceFactory;

class Jinah
{
    private PaymentServiceContract $paymentService;
    private PaymentServiceFactory $serviceFactory;
    private array $config;

    public function __construct(array $config, ?string $serviceName = null)
    {
        $this->config = $config;
        $this->serviceFactory = new PaymentServiceFactory($config);
        $this->paymentService = $this->serviceFactory->create($serviceName);
    }

    /**
     * Create a payment charge
     */
    public function create(
        PaymentRequest $requestBuilder
    ): PaymentResponse {
        return $this->paymentService->initiate($requestBuilder);
    }

    /**
     * Check payment charge status
     */
    public function check(
        string $orderId,
    ): WebhookPayload {
        return $this->paymentService->check($orderId);
    }

    /**
     * Create a payment charge from PaymentRequest DTO
     */
    public function chargeFromRequest(PaymentRequest $request): PaymentResponse
    {
        $this->validatePaymentAmount($request->amount);
        $this->validateCurrency($request->currency);
        $this->validateMerchantOrderId($request->merchantOrderId);

        return $this->paymentService->charge($request);
    }

    /**
     * Inquiry payment status by transaction ID
     */
    public function inquiryByTransactionId(string $transactionId): PaymentResponse
    {
        if (empty($transactionId)) {
            throw PaymentException::missingRequiredField('transaction_id');
        }

        $inquiry = TransactionInquiry::byTransactionId($transactionId);
        return $this->paymentService->inquiry($inquiry);
    }

    /**
     * Inquiry payment status by merchant order ID
     */
    public function inquiryByMerchantOrderId(string $merchantOrderId): PaymentResponse
    {
        if (empty($merchantOrderId)) {
            throw PaymentException::missingRequiredField('merchant_order_id');
        }

        $inquiry = TransactionInquiry::byMerchantOrderId($merchantOrderId);
        return $this->paymentService->inquiry($inquiry);
    }

    /**
     * Cancel a payment transaction
     */
    public function cancel(string $transactionId): PaymentResponse
    {
        if (empty($transactionId)) {
            throw PaymentException::missingRequiredField('transaction_id');
        }

        return $this->paymentService->cancel($transactionId);
    }

    /**
     * Get payment service instance
     */
    public function getPaymentService(): PaymentServiceContract
    {
        return $this->paymentService;
    }

    /**
     * Get service factory instance
     */
    public function getServiceFactory(): PaymentServiceFactory
    {
        return $this->serviceFactory;
    }

    /**
     * Switch to a different payment service
     */
    public function switchService(string $serviceName): self
    {
        $this->paymentService = $this->serviceFactory->create($serviceName);
        return $this;
    }

    /**
     * Get current service name
     */
    public function getCurrentServiceName(): string
    {
        return $this->paymentService->getServiceName();
    }

    /**
     * Get available services
     */
    public function getAvailableServices(): array
    {
        return $this->serviceFactory->getAvailableServices();
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Validate payment amount
     */
    private function validatePaymentAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw PaymentException::invalidAmount($amount);
        }
    }

    /**
     * Validate currency
     */
    private function validateCurrency(string $currency): void
    {
        $allowedCurrencies = ['IDR', 'USD', 'EUR', 'SGD'];
        
        if (!in_array(strtoupper($currency), $allowedCurrencies)) {
            throw PaymentException::invalidCurrency($currency);
        }
    }

    /**
     * Validate merchant order ID
     */
    private function validateMerchantOrderId(string $merchantOrderId): void
    {
        if (empty($merchantOrderId)) {
            throw PaymentException::missingRequiredField('merchant_order_id');
        }

        if (strlen($merchantOrderId) > 100) {
            throw PaymentException::missingRequiredField('merchant_order_id (max 100 characters)');
        }
    }
}
