<?php

namespace AnyTech\Jinah\Factories;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\Exceptions\JinahException;
use AnyTech\Jinah\Services\FinPayService;
use AnyTech\Jinah\Services\JinahService;
use Illuminate\Http\Request;
use Log;

class PaymentServiceFactory
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create a payment service instance based on the service name
     */
    public function create(?string $serviceName = null): PaymentServiceContract
    {
        $serviceName ??= $this->config['default_service'] ?? 'jinah';

        return match ($serviceName) {
            'finpay' => $this->createFinPayService(),
            default => $this->createJinahService(),
        };
    }
    
    /**
     * Create FinPay service instance
     */
    private function createFinPayService(): FinPayService
    {
        if (!isset($this->config['services']['finpay'])) {
            throw new JinahException("FinPay service is not configured");
        }

        $service = new FinPayService($this->config);

        return $service;
    }

    private function createJinahService(): JinahService
    {
        if (!isset($this->config['services']['jinah'])) {
            throw new JinahException("Jinah service is not configured");
        }

        $service = new JinahService($this->config);

        return $service;
    }

    /**
     * Get available payment services
     */
    public function getAvailableServices(): array
    {
        $services = [];
        
        foreach ($this->config['services'] ?? [] as $serviceName => $serviceConfig) {
            try {
                $service = $this->create($serviceName);
                if ($service->isConfigured()) {
                    $services[] = [
                        'name' => $serviceName,
                        'service_name' => $service->getServiceName(),
                        'configured' => true,
                    ];
                }
            } catch (\Exception $e) {
                $services[] = [
                    'name' => $serviceName,
                    'service_name' => $serviceName,
                    'configured' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $services;
    }

    /**
     * Check if a specific service is available
     */
    public function isServiceAvailable(string $serviceName): bool
    {
        try {
            $service = $this->create($serviceName);
            return $service->isConfigured();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get default service name
     */
    public function getDefaultService(): string
    {
        return $this->config['default_service'] ?? 'jinah';
    }
}