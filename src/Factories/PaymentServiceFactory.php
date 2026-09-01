<?php

namespace AnyTech\Jinah\Factories;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\Exceptions\JinahException;
use AnyTech\Jinah\Services\DokuCheckoutService;
use AnyTech\Jinah\Services\FinPayService;
use AnyTech\Jinah\Services\JinahService;
use AnyTech\Jinah\Services\SesariService;
use AnyTech\Jinah\Services\ConversoService;
use App\Services\SesariPaymentService;
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
            'doku' => $this->createDokuService(),
            'finpay' => $this->createFinPayService(),
            'sesari' => $this->createSesariService(),
            'converso' => $this->createConversoService(),
            default => $this->createJinahService(),
        };
    }

    private function createDokuService(): DokuCheckoutService
    {
        if (!isset($this->config['services']['doku'])) {
            throw new JinahException("DOKU service is not configured");
        }

        return new DokuCheckoutService($this->config);
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

    private function createSesariService(): SesariService
    {
        if (!isset($this->config['services']['sesari'])) {
            throw new JinahException("Sesari service is not configured");
        }

        $service = new SesariService($this->config);

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

    private function createConversoService(): ConversoService
    {
        if (!isset($this->config['services']['converso'])) {
            throw new JinahException("Converso service is not configured");
        }

        $service = new ConversoService($this->config);

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