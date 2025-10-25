<?php

namespace AnyTech\Jinah\Listeners;

use AnyTech\Jinah\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePaymentFailed implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $details = $event->getFailureDetails();
        
        Log::warning('Payment failed webhook processed', $details);

        // Example: Update order status in your application
        // $this->updateOrderStatus($details['merchant_order_id'], 'failed');
        
        // Example: Send failure notification
        // $this->sendPaymentFailureNotification($details);
        
        // Example: Retry payment if applicable
        // $this->schedulePaymentRetry($details);
    }

    /**
     * Example method to update order status
     */
    private function updateOrderStatus(string $orderId, string $status): void
    {
        // Implement your order status update logic here
        // Example:
        // Order::where('id', $orderId)->update([
        //     'status' => $status, 
        //     'failed_at' => now(),
        //     'failure_reason' => $details['failure_reason']
        // ]);
    }

    /**
     * Example method to send failure notification
     */
    private function sendPaymentFailureNotification(array $details): void
    {
        // Implement your notification logic here
        // Example:
        // Mail::to($customerEmail)->send(new PaymentFailedMail($details));
        // 
        // Or send to admin:
        // Mail::to(config('app.admin_email'))->send(new PaymentFailedNotification($details));
    }

    /**
     * Example method to schedule payment retry
     */
    private function schedulePaymentRetry(array $details): void
    {
        // Implement retry logic if applicable
        // Example:
        // if ($this->shouldRetryPayment($details)) {
        //     RetryPaymentJob::dispatch($details)->delay(now()->addMinutes(30));
        // }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentFailed $event, \Throwable $exception): void
    {
        Log::error('Failed to process payment failed webhook', [
            'event' => $event->getFailureDetails(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}