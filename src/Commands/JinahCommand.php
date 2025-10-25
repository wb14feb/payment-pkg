<?php

namespace AnyTech\Jinah\Commands;

use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\Exceptions\ApiException;
use AnyTech\Jinah\Exceptions\PaymentException;
use AnyTech\Jinah\Facades\Jinah;
use Illuminate\Console\Command;

class JinahCommand extends Command
{
    public $signature = 'jinah 
                        {action : The action to perform (test-charge, test-inquiry, test-cancel, list-services, switch-service, webhook-info)}
                        {--order-id= : Merchant order ID}
                        {--amount= : Payment amount}
                        {--transaction-id= : Transaction ID for inquiry/cancel}
                        {--service= : Payment service to use}';

    public $description = 'Jinah payment package demonstration and testing command';

    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            match ($action) {
                'test-charge' => $this->testCharge(),
                'test-inquiry' => $this->testInquiry(),
                'test-cancel' => $this->testCancel(),
                'list-services' => $this->listServices(),
                'switch-service' => $this->switchService(),
                'webhook-info' => $this->showWebhookInfo(),
                default => $this->showHelp(),
            };
        } catch (PaymentException $e) {
            $this->error("Payment Error: {$e->getMessage()}");
            return self::FAILURE;
        } catch (ApiException $e) {
            $this->error("API Error: {$e->getMessage()}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testCharge(): void
    {
        $this->info('Testing payment charge...');

        // Switch service if specified
        $service = $this->option('service');
        if ($service) {
            $this->line("Switching to service: {$service}");
            Jinah::switchService($service);
        }

        $this->line("Current service: " . Jinah::getCurrentServiceName());

        $orderId = $this->option('order-id') ?? 'ORDER-' . time();
        $amount = (float) ($this->option('amount') ?? 10000);

        $this->line("Order ID: {$orderId}");
        $this->line("Amount: {$amount}");

        $response = Jinah::charge(
            merchantOrderId: $orderId,
            amount: $amount,
            currency: 'IDR',
            options: [
                'description' => 'Test payment from Jinah command',
                'customer_name' => 'Test Customer',
                'customer_email' => 'test@example.com',
            ]
        );

        if ($response->isSuccessful()) {
            $this->info('✅ Payment charge successful!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Transaction ID', $response->transactionId],
                    ['Status', $response->status],
                    ['Payment URL', $response->paymentUrl],
                    ['Message', $response->message],
                ]
            );
        } else {
            $this->error('❌ Payment charge failed!');
            $this->line("Error: {$response->message}");
            $this->line("Error Code: {$response->errorCode}");
        }
    }

    private function testInquiry(): void
    {
        $this->info('Testing payment inquiry...');

        $transactionId = $this->option('transaction-id');
        $orderId = $this->option('order-id');

        if (!$transactionId && !$orderId) {
            $this->error('Please provide either --transaction-id or --order-id');
            return;
        }

        if ($transactionId) {
            $this->line("Transaction ID: {$transactionId}");
            $response = Jinah::inquiryByTransactionId($transactionId);
        } else {
            $this->line("Order ID: {$orderId}");
            $response = Jinah::inquiryByMerchantOrderId($orderId);
        }

        if ($response->isSuccessful()) {
            $this->info('✅ Payment inquiry successful!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Transaction ID', $response->transactionId],
                    ['Merchant Order ID', $response->merchantOrderId],
                    ['Status', $response->status],
                    ['Amount', $response->amount],
                    ['Currency', $response->currency],
                    ['Message', $response->message],
                ]
            );

            // Status indicators
            if ($response->isPending()) {
                $this->warn('⏳ Payment is pending');
            } elseif ($response->isCompleted()) {
                $this->info('✅ Payment is completed');
            } elseif ($response->isFailed()) {
                $this->error('❌ Payment has failed');
            }
        } else {
            $this->error('❌ Payment inquiry failed!');
            $this->line("Error: {$response->message}");
            $this->line("Error Code: {$response->errorCode}");
        }
    }

    private function testCancel(): void
    {
        $this->info('Testing payment cancellation...');

        $transactionId = $this->option('transaction-id');

        if (!$transactionId) {
            $this->error('Please provide --transaction-id for cancellation');
            return;
        }

        $this->line("Transaction ID: {$transactionId}");

        $response = Jinah::cancel($transactionId);

        if ($response->isSuccessful()) {
            $this->info('✅ Payment cancellation successful!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Transaction ID', $response->transactionId],
                    ['Status', $response->status],
                    ['Message', $response->message],
                ]
            );
        } else {
            $this->error('❌ Payment cancellation failed!');
            $this->line("Error: {$response->message}");
            $this->line("Error Code: {$response->errorCode}");
        }
    }

    private function showHelp(): void
    {
        $this->info('Jinah Payment Package Demo');
        $this->line('');
        $this->line('Available actions:');
        $this->line('  test-charge     - Test payment charge creation');
        $this->line('  test-inquiry    - Test payment status inquiry');
        $this->line('  test-cancel     - Test payment cancellation');
        $this->line('  list-services   - List available payment services');
        $this->line('  switch-service  - Switch to a different payment service');
        $this->line('  webhook-info    - Show webhook URLs and configuration');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan jinah test-charge --order-id=ORDER123 --amount=50000');
        $this->line('  php artisan jinah test-charge --service=finpay --amount=25000');
        $this->line('  php artisan jinah test-inquiry --transaction-id=TXN123');
        $this->line('  php artisan jinah test-inquiry --order-id=ORDER123');
        $this->line('  php artisan jinah test-cancel --transaction-id=TXN123');
        $this->line('  php artisan jinah list-services');
        $this->line('  php artisan jinah switch-service --service=finpay');
        $this->line('  php artisan jinah webhook-info');
        $this->line('');
        $this->line('Configuration:');
        $config = Jinah::getConfig();
        $this->table(
            ['Setting', 'Value'],
            [
                ['Default Service', $config['default_service'] ?? 'N/A'],
                ['Environment', $config['environment'] ?? 'N/A'],
                ['FinPay URL', $config['environment'] === 'production' 
                    ? $config['services']['finpay']['production_url'] ?? 'N/A'
                    : $config['services']['finpay']['development_url'] ?? 'N/A'],
                ['Default Currency', $config['payment']['default_currency'] ?? 'N/A'],
                ['Logging Enabled', ($config['logging']['enabled'] ?? false) ? 'Yes' : 'No'],
            ]
        );
    }

    private function listServices(): void
    {
        $this->info('Available Payment Services:');
        $this->line('');

        $services = Jinah::getAvailableServices();
        $currentService = Jinah::getCurrentServiceName();

        if (empty($services)) {
            $this->warn('No payment services are configured.');
            return;
        }

        $tableData = [];
        foreach ($services as $service) {
            $status = $service['configured'] ? '✅ Configured' : '❌ Not configured';
            $current = $service['name'] === $currentService ? ' (CURRENT)' : '';
            
            $tableData[] = [
                $service['name'] . $current,
                $service['service_name'],
                $status,
                $service['error'] ?? 'N/A',
            ];
        }

        $this->table(
            ['Service Key', 'Service Name', 'Status', 'Error'],
            $tableData
        );

        $this->line('');
        $this->line("Current active service: {$currentService}");
    }

    private function switchService(): void
    {
        $service = $this->option('service');
        
        if (!$service) {
            $this->error('Please provide --service option');
            $this->line('Available services:');
            foreach (Jinah::getAvailableServices() as $availableService) {
                if ($availableService['configured']) {
                    $this->line("  - {$availableService['name']}");
                }
            }
            return;
        }

        try {
            $oldService = Jinah::getCurrentServiceName();
            Jinah::switchService($service);
            $newService = Jinah::getCurrentServiceName();
            
            $this->info("✅ Successfully switched from '{$oldService}' to '{$newService}'");
        } catch (\Exception $e) {
            $this->error("❌ Failed to switch to service '{$service}': {$e->getMessage()}");
        }
    }

    private function showWebhookInfo(): void
    {
        $this->info('Webhook Configuration:');
        $this->line('');

        $config = Jinah::getConfig();
        $webhookConfig = $config['webhook'] ?? [];

        // Show webhook URLs
        $this->line('📡 Webhook URLs:');
        $baseUrl = config('app.url');
        $prefix = $webhookConfig['route_prefix'] ?? 'payment-webhook';

        $urls = [
            'Auto-Detection' => "{$baseUrl}/jinah/webhook",
            'Auto-Detection (Alt)' => "{$baseUrl}/{$prefix}",
            'FinPay' => "{$baseUrl}/jinah/webhook/finpay",
            'FinPay (Alt)' => "{$baseUrl}/{$prefix}/finpay",
            'Stripe' => "{$baseUrl}/jinah/webhook/stripe",
            'Stripe (Alt)' => "{$baseUrl}/{$prefix}/stripe",
            'Midtrans' => "{$baseUrl}/jinah/webhook/midtrans", 
            'Midtrans (Alt)' => "{$baseUrl}/{$prefix}/midtrans",
            'Generic' => "{$baseUrl}/jinah/webhook/{{service}}",
            'Health Check' => "{$baseUrl}/jinah/webhook/health",
        ];

        foreach ($urls as $service => $url) {
            $this->line("  {$service}: {$url}");
        }

        $this->line('');
        $this->line('💡 Auto-Detection Features:');
        $this->line('  - Headers: Stripe-Signature, X-FinPay-Signature');
        $this->line('  - Payload: Automatically detects service from structure');
        $this->line('  - Fallback: Uses URL service parameter if specified');

        $this->line('');
        
        // Show webhook security settings
        $this->line('🔒 Security Settings:');
        $securityTable = [];

        foreach (['finpay', 'stripe', 'midtrans'] as $service) {
            $serviceConfig = $webhookConfig[$service] ?? [];
            $secretConfigured = !empty($serviceConfig['secret'] ?? $serviceConfig['endpoint_secret'] ?? $serviceConfig['server_key']);
            $verifySignature = $serviceConfig['verify_signature'] ?? true;
            $ipWhitelist = $serviceConfig['ip_whitelist'] ?? 'None';

            $securityTable[] = [
                ucfirst($service),
                $secretConfigured ? '✅ Configured' : '❌ Not configured',
                $verifySignature ? '✅ Enabled' : '❌ Disabled',
                $ipWhitelist ?: 'None',
            ];
        }

        $this->table(
            ['Service', 'Secret/Key', 'Signature Verification', 'IP Whitelist'],
            $securityTable
        );

        $this->line('');
        
        // Show webhook events
        $this->line('🎯 Available Events:');
        $events = [
            'PaymentWebhookReceived' => 'General webhook received (all services)',
            'PaymentSuccessful' => 'Payment completed successfully',
            'PaymentFailed' => 'Payment failed or was rejected',
            'PaymentPending' => 'Payment is pending/processing',
        ];

        foreach ($events as $event => $description) {
            $this->line("  {$event}: {$description}");
        }

        $this->line('');
        $this->line('💡 Tips:');
        $this->line('  - Configure webhook secrets in your .env file for security');
        $this->line('  - Use ngrok or similar for local webhook testing');
        $this->line('  - Check logs for webhook processing details');
        $this->line('  - Create listeners for webhook events to handle business logic');
    }
}
