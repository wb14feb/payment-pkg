<?php

namespace AnyTech\Jinah\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AnyTech\Jinah\Jinah
 * 
 * @method static \AnyTech\Jinah\DTOs\PaymentResponse create(\AnyTech\Jinah\DTOs\PaymentRequest $request)
 * @method static \AnyTech\Jinah\DTOs\PaymentResponse check(string $orderId)
 * @method static array getConfig()
 * @method static \AnyTech\Jinah\Contracts\PaymentServiceContract getPaymentService()
 * @method static \AnyTech\Jinah\Factories\PaymentServiceFactory getServiceFactory()
 * @method static \AnyTech\Jinah\Jinah switchService(string $serviceName)
 * @method static string getCurrentServiceName()
 * @method static array getAvailableServices()
 */
class Jinah extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'jinah';
    }
}
