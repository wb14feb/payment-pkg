<?php

namespace AnyTech\Jinah\Listeners;

use AnyTech\Jinah\Events\PaymentSuccessful;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePaymentSuccessful implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessful $event): void
    {
        $details = $event->getPaymentDetails();
        
        Log::info('Payment successful webhook processed', $details);

        // Example: Update order status in your application
        // $this->updateOrderStatus($details['merchant_order_id'], 'paid');
        
        // Example: Send confirmation email
        // $this->sendPaymentConfirmation($details);
        
        // Example: Update customer balance
        // $this->updateCustomerBalance($details);
    }

    /**
     * Example method to update order status
     */
    private function updateOrderStatus(string $orderId, string $status): void
    {
        // Implement your order status update logic here
        // Example:
        // Order::where('id', $orderId)->update(['status' => $status, 'paid_at' => now()]);
    }

    /**
     * Example method to send payment confirmation
     */
    private function sendPaymentConfirmation(array $details): void
    {
        // Implement your email notification logic here
        // Example:
        // Mail::to($customerEmail)->send(new PaymentConfirmationMail($details));
    }

    /**
     * Example method to update customer balance
     */
    private function updateCustomerBalance(array $details): void
    {
        // Implement your customer balance update logic here
        // Example:
        // Customer::where('order_id', $details['merchant_order_id'])
        //     ->increment('balance', $details['amount']);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentSuccessful $event, \Throwable $exception): void
    {
        Log::error('Failed to process payment successful webhook', [
            'event' => $event->getPaymentDetails(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}