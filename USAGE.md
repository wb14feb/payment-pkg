# Jinah Payment Package

A Laravel package that provides a proxy interface to multiple payment gateways with a unified API. Currently supports FinPay with the ability to easily add more payment services.

## Installation

Install the package via Composer:

```bash
composer require wb14feb/jinah
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=jinah-config
```

## Configuration

Add the following environment variables to your `.env` file:

```env
# Default service configuration
JINAH_DEFAULT_SERVICE=finpay
JINAH_ENVIRONMENT=development

# FinPay Configuration
JINAH_FINPAY_CLIENT_ID=your_client_id
JINAH_FINPAY_CLIENT_SECRET=your_client_secret

# URLs (optional - defaults provided)
JINAH_FINPAY_SANDBOX_URL=https://devo.finnet.co.id
JINAH_FINPAY_PRODUCTION_URL=https://live.finnet.co.id

# Payment Configuration
JINAH_DEFAULT_CURRENCY=IDR
JINAH_CALLBACK_URL=https://your-app.com/payment/callback
JINAH_RETURN_URL=https://your-app.com/payment/return

# Logging (optional)
JINAH_LOGGING_ENABLED=false
JINAH_LOGGING_LEVEL=info
JINAH_LOGGING_CHANNEL=single
```

## Multi-Service Architecture

The package uses a contract-based architecture that supports multiple payment services:

### Available Payment Services

- **FinPay**: Fully implemented FinPay payment gateway integration
- **Stripe, Midtrans**: Example configurations provided for future implementation

### Service Management

```php
use AnyTech\Jinah\Facades\Jinah;

// Get current service
$currentService = Jinah::getCurrentServiceName(); // Returns: 'finpay'

// List available services
$services = Jinah::getAvailableServices();
/*
Returns:
[
    [
        'name' => 'finpay',
        'service_name' => 'finpay',
        'configured' => true,
    ]
]
*/

// Switch to a different service
Jinah::switchService('finpay');

// Check if a service is available
$factory = Jinah::getServiceFactory();
$isAvailable = $factory->isServiceAvailable('finpay');
```

## Usage

### Using the Facade (Recommended)

```php
use AnyTech\Jinah\Facades\Jinah;

// Create a payment charge with the default service
$response = Jinah::charge(
    merchantOrderId: 'ORDER-123',
    amount: 50000,
    currency: 'IDR',
    options: [
        'description' => 'Product purchase',
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+6281234567890',
    ]
);

if ($response->isSuccessful()) {
    echo "Payment URL: " . $response->paymentUrl;
    echo "Transaction ID: " . $response->transactionId;
} else {
    echo "Error: " . $response->message;
}
```

### Using Specific Payment Services

```php
use AnyTech\Jinah\Facades\Jinah;

// Use a specific service for this operation
$response = Jinah::switchService('finpay')
    ->charge('ORDER-123', 50000, 'IDR');

// Or create a new instance with a specific service
$jinah = new \AnyTech\Jinah\Jinah(config('jinah'), 'finpay');
$response = $jinah->charge('ORDER-123', 50000);
```

### Using Dependency Injection

```php
use AnyTech\Jinah\JinahContract;
use AnyTech\Jinah\Contracts\PaymentServiceContract;

class PaymentController extends Controller
{
    public function __construct(private JinahContract $jinah)
    {
    }

    public function createPayment(Request $request)
    {
        // Use the configured default service
        $response = $this->jinah->charge(
            merchantOrderId: $request->order_id,
            amount: $request->amount,
            currency: 'IDR'
        );

        return response()->json($response->toArray());
    }

    public function createPaymentWithSpecificService(Request $request, string $service)
    {
        // Switch to a specific service
        $response = $this->jinah->switchService($service)
            ->charge(
                merchantOrderId: $request->order_id,
                amount: $request->amount,
                currency: 'IDR'
            );

        return response()->json($response->toArray());
    }
}
```

### Using DTOs

```php
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\Facades\Jinah;

$paymentRequest = new PaymentRequest(
    merchantOrderId: 'ORDER-123',
    amount: 50000,
    currency: 'IDR',
    description: 'Product purchase',
    customerName: 'John Doe',
    customerEmail: 'john@example.com'
);

$response = Jinah::chargeFromRequest($paymentRequest);
```

### Direct Service Usage

```php
use AnyTech\Jinah\Factories\PaymentServiceFactory;

$factory = new PaymentServiceFactory(config('jinah'));

// Create a specific payment service
$finpayService = $factory->create('finpay');

// Use the service directly
$request = new PaymentRequest(/* ... */);
$response = $finpayService->charge($request);
```

## Available Methods

### Payment Operations

- `charge()` - Create a payment charge
- `chargeFromRequest()` - Create a payment charge from PaymentRequest DTO
- `inquiryByTransactionId()` - Check payment status by transaction ID
- `inquiryByMerchantOrderId()` - Check payment status by merchant order ID
- `cancel()` - Cancel a payment transaction

### Service Management

- `getCurrentServiceName()` - Get the currently active service name
- `getAvailableServices()` - List all available and configured services
- `switchService()` - Switch to a different payment service
- `getServiceFactory()` - Get the payment service factory instance
- `getPaymentService()` - Get the current payment service instance

### Payment Response

The `PaymentResponse` object provides several helper methods:

```php
$response = Jinah::charge(...);

// Check status
$response->isSuccessful();  // true if API call succeeded
$response->isPending();     // true if payment is pending
$response->isCompleted();   // true if payment is completed
$response->isFailed();      // true if payment failed

// Get data
$response->transactionId;   // Payment gateway transaction ID
$response->paymentUrl;      // URL to redirect customer for payment
$response->amount;          // Payment amount
$response->status;          // Payment status
$response->message;         // Response message
```

## Testing & CLI Commands

Use the built-in command to test the integration:

```bash
# Test payment creation
php artisan jinah test-charge --order-id=ORDER123 --amount=50000

# Test with specific service
php artisan jinah test-charge --service=finpay --amount=25000

# Test payment inquiry
php artisan jinah test-inquiry --transaction-id=TXN123
php artisan jinah test-inquiry --order-id=ORDER123

# Test payment cancellation
php artisan jinah test-cancel --transaction-id=TXN123

# List available services
php artisan jinah list-services

# Switch service
php artisan jinah switch-service --service=finpay

# Show help and configuration
php artisan jinah
```

## Adding New Payment Services

To add a new payment service:

1. **Create a Service Class** implementing `PaymentServiceContract`:

```php
<?php

namespace AnyTech\Jinah\Services;

use AnyTech\Jinah\Contracts\PaymentServiceContract;

class YourPaymentService implements PaymentServiceContract
{
    public function charge(PaymentRequest $request): PaymentResponse { /* ... */ }
    public function inquiry(TransactionInquiry $inquiry): PaymentResponse { /* ... */ }
    public function cancel(string $transactionId): PaymentResponse { /* ... */ }
    public function getAccessToken(): string { /* ... */ }
    public function getServiceName(): string { return 'your-service'; }
    public function isConfigured(): bool { /* ... */ }
    public function getServiceConfig(): array { /* ... */ }
}
```

2. **Add Configuration** in `config/jinah.php`:

```php
"services" => [
    "your-service" => [
        "driver" => "your-service",
        "name" => "Your Service",
        "description" => "Your Payment Gateway",
        "api_key" => env('JINAH_YOUR_SERVICE_API_KEY'),
        // ... other config
    ],
]
```

3. **Update the Factory** in `PaymentServiceFactory::create()`:

```php
return match ($serviceName) {
    'finpay' => $this->createFinPayService(),
    'your-service' => $this->createYourService(),
    default => throw new JinahException("Unsupported payment service: {$serviceName}"),
};
```

## Exception Handling

The package provides specific exceptions for different scenarios:

```php
use AnyTech\Jinah\Exceptions\PaymentException;
use AnyTech\Jinah\Exceptions\ApiException;
use AnyTech\Jinah\Exceptions\JinahException;

try {
    $response = Jinah::charge('ORDER-123', 50000);
} catch (PaymentException $e) {
    // Handle payment-specific errors (validation, etc.)
    echo "Payment error: " . $e->getMessage();
} catch (ApiException $e) {
    // Handle API communication errors
    echo "API error: " . $e->getMessage();
} catch (JinahException $e) {
    // Handle general package errors
    echo "Package error: " . $e->getMessage();
}
```

## Security

- All API communications use HTTPS
- Client credentials are securely transmitted
- Request/response logging can be enabled for debugging (disabled by default)
- Service switching is memory-safe and doesn't affect other requests

## Architecture

The package uses a clean architecture with:

- **Contracts**: Define interfaces for payment services
- **DTOs**: Type-safe data transfer objects
- **Factories**: Create service instances based on configuration
- **Services**: Implement specific payment gateway logic
- **Exceptions**: Handle different error scenarios
- **Facades**: Provide easy Laravel integration

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.