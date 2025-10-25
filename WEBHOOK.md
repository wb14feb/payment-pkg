# Webhook Integration Guide

This guide explains how to set up and handle webhooks from payment services in the Jinah Payment Package.

## Overview

Webhooks allow payment services to notify your application about payment status changes in real-time. The Jinah package provides a **single agnostic webhook handler** that automatically routes payloads to the appropriate service with:

- **Auto-detection** of payment service from headers and payload structure
- **Universal handler** that works with any supported payment service
- Secure signature verification
- Event-driven architecture
- Automatic payload normalization
- IP whitelisting support

## Webhook URLs

The package provides a single universal webhook endpoint that automatically detects and routes to the appropriate service:

### Universal Endpoints (Recommended)
```
POST /jinah/webhook                  - Auto-detects service from payload/headers
POST /payment-webhook               - Alternative auto-detection URL
```

### Service-Specific Endpoints (Optional)
```
POST /jinah/webhook/{service}        - Explicit service specification
POST /payment-webhook/{service}      - Alternative with custom prefix
```

### Utility Endpoints
```
GET  /jinah/webhook/health          - Health check endpoint
```

## Auto-Detection Features

The webhook handler automatically detects the payment service using:

### 1. Request Headers
- `Stripe-Signature` → Routes to Stripe handler
- `X-FinPay-Signature` or `X-FinPay-Event` → Routes to FinPay handler

### 2. Payload Structure
- **Stripe**: `{"type": "...", "data": {"object": "..."}, "api_version": "..."}`
- **Midtrans**: `{"transaction_status": "...", "order_id": "...", "signature_key": "..."}`
- **FinPay**: `{"transaction_id": "...", "merchant_order_id": "...", "event_type": "..."}`

### 3. Fallback
- Uses URL service parameter if specified: `/jinah/webhook/finpay`
- Defaults to generic handler if no pattern matches

## Configuration

Add webhook configuration to your `.env` file:

```env
# Webhook route prefix (optional)
JINAH_WEBHOOK_PREFIX=payment-webhook

# Global IP whitelist (optional)
JINAH_WEBHOOK_IP_WHITELIST=192.168.1.0/24,10.0.0.1

# Service-specific webhook settings (automatically detected)
JINAH_FINPAY_WEBHOOK_SECRET=your_finpay_webhook_secret
JINAH_STRIPE_WEBHOOK_SECRET=whsec_your_stripe_endpoint_secret
JINAH_MIDTRANS_SERVER_KEY=your_midtrans_server_key
```

## Simple Setup

### Option 1: Single Universal URL (Recommended)
Configure this single URL in all your payment services:
```
https://your-app.com/jinah/webhook
```

The system will automatically:
1. Detect which payment service sent the webhook
2. Verify the signature using the appropriate method
3. Parse the payload in the correct format
4. Dispatch the appropriate events

### Option 2: Service-Specific URLs
If you prefer explicit URLs for each service:
```
FinPay:   https://your-app.com/jinah/webhook/finpay
Stripe:   https://your-app.com/jinah/webhook/stripe
Midtrans: https://your-app.com/jinah/webhook/midtrans
```

## Security Features

### Signature Verification

Each payment service uses different signature verification methods:

- **FinPay**: HMAC-SHA256 with webhook secret
- **Stripe**: Timestamp + HMAC-SHA256 verification
- **Midtrans**: SHA512 hash verification

### IP Whitelisting

You can restrict webhook access by IP address:

```env
# Global whitelist (applies to all services)
JINAH_WEBHOOK_IP_WHITELIST=192.168.1.0/24,10.0.0.1

# Service-specific whitelists
JINAH_FINPAY_WEBHOOK_IPS=203.194.112.0/24
JINAH_STRIPE_WEBHOOK_IPS=3.18.12.63,3.130.192.231
```

## Event Handling

The package dispatches Laravel events for different webhook scenarios:

### Available Events

1. **PaymentWebhookReceived** - General webhook received (all services)
2. **PaymentSuccessful** - Payment completed successfully  
3. **PaymentFailed** - Payment failed or was rejected
4. **PaymentPending** - Payment is pending/processing

### Creating Event Listeners

Create listeners to handle webhook events:

```php
// app/Listeners/HandlePaymentSuccess.php
<?php

namespace App\Listeners;

use AnyTech\Jinah\Events\PaymentSuccessful;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandlePaymentSuccess implements ShouldQueue
{
    public function handle(PaymentSuccessful $event): void
    {
        $details = $event->getPaymentDetails();
        
        // Update order status
        Order::where('id', $details['merchant_order_id'])
            ->update(['status' => 'paid', 'paid_at' => now()]);
        
        // Send confirmation email
        Mail::to($customer)->send(new PaymentConfirmation($details));
        
        // Log the payment
        Log::info('Payment successful', $details);
    }
}
```

### Register Event Listeners

In `app/Providers/EventServiceProvider.php`:

```php
use AnyTech\Jinah\Events\PaymentSuccessful;
use AnyTech\Jinah\Events\PaymentFailed;
use AnyTech\Jinah\Events\PaymentPending;
use App\Listeners\HandlePaymentSuccess;
use App\Listeners\HandlePaymentFailure;
use App\Listeners\HandlePaymentPending;

protected $listen = [
    PaymentSuccessful::class => [
        HandlePaymentSuccess::class,
    ],
    PaymentFailed::class => [
        HandlePaymentFailure::class,
    ],
    PaymentPending::class => [
        HandlePaymentPending::class,
    ],
];
```

## Webhook Payload Structure

All webhooks are normalized into a standard `WebhookPayload` DTO:

```php
$payload = new WebhookPayload(
    service: 'finpay',                    // Payment service name
    eventType: 'payment.notification',   // Event type
    transactionId: 'TXN123',             // Service transaction ID
    merchantOrderId: 'ORDER123',         // Your order ID
    status: 'completed',                 // Normalized status
    amount: 50000.0,                     // Payment amount
    currency: 'IDR',                     // Currency code
    timestamp: Carbon::now(),            // Event timestamp
    rawPayload: [...],                   // Original webhook data
    metadata: [...]                      // Request metadata
);
```

### Status Normalization

The package normalizes different service statuses:

| FinPay Status | Stripe Status | Midtrans Status | Normalized Status |
|---------------|---------------|-----------------|-------------------|
| success/paid  | succeeded     | capture/settlement | completed |
| pending       | pending       | pending         | pending |
| failed        | failed        | deny/cancel/expire | failed |
| cancelled     | canceled      | cancel          | cancelled |

## Testing Webhooks

### Using the CLI Command

```bash
# Show webhook configuration and URLs
php artisan jinah webhook-info
```

### Local Testing with ngrok

1. Install ngrok: `npm install -g ngrok`
2. Expose your local server: `ngrok http 8000`
3. Use the ngrok URL for webhook configuration
4. Test webhook endpoints

### Manual Testing

Send test webhook requests to the universal endpoint:

```bash
# Test FinPay webhook (auto-detected)
curl -X POST http://your-app.com/jinah/webhook \
  -H "Content-Type: application/json" \
  -H "X-FinPay-Signature: your_signature" \
  -d '{
    "event_type": "payment.notification",
    "transaction_id": "TXN123",
    "merchant_order_id": "ORDER123",
    "transaction_status": "success",
    "amount": 50000,
    "currency": "IDR"
  }'

# Test Stripe webhook (auto-detected)
curl -X POST http://your-app.com/jinah/webhook \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: t=1234567890,v1=signature_here" \
  -d '{
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_123",
        "amount": 5000,
        "currency": "usd",
        "status": "succeeded"
      }
    },
    "api_version": "2020-08-27"
  }'

# Test with explicit service specification
curl -X POST http://your-app.com/jinah/webhook/finpay \
  -H "Content-Type: application/json" \
  -d '{"transaction_id": "TXN123", "status": "success"}'
```

## Troubleshooting

### Common Issues

1. **Signature Verification Failed**
   - Check webhook secret configuration
   - Verify the signature format matches expected format
   - Ensure the payload hasn't been modified

2. **IP Blocked**
   - Check IP whitelist configuration
   - Verify the requesting IP is allowed
   - Check firewall settings

3. **Webhook Not Received**
   - Verify the webhook URL is correct
   - Check if the endpoint is accessible from the internet
   - Review web server logs

### Debugging

Enable detailed logging:

```env
JINAH_LOGGING_ENABLED=true
JINAH_LOGGING_LEVEL=debug
```

Check logs in `storage/logs/laravel.log` for webhook processing details.

### Health Check

Test webhook infrastructure:

```bash
curl http://your-app.com/jinah/webhook/health
```

Expected response:
```json
{
  "status": "ok",
  "service": "jinah-webhook", 
  "timestamp": "2025-10-10T10:00:00.000000Z",
  "version": "1.0.0"
}
```

## Best Practices

1. **Always verify signatures** in production
2. **Use HTTPS** for webhook endpoints
3. **Implement idempotency** to handle duplicate webhooks
4. **Queue webhook processing** for better performance
5. **Log all webhook events** for audit trails
6. **Set up monitoring** for webhook failures
7. **Test webhook handling** thoroughly before going live

## Security Checklist

- ✅ Webhook signatures are verified
- ✅ IP whitelisting is configured (if required)
- ✅ HTTPS is used for webhook endpoints
- ✅ Webhook secrets are stored securely
- ✅ Failed webhooks are logged and monitored
- ✅ Webhook processing is idempotent
- ✅ Error handling is implemented
- ✅ Rate limiting is configured (if needed)