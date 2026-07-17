<?php

namespace App\Listeners;

use AnyTech\Jinah\DTOs\WebhookPayload;
use AnyTech\Jinah\Events\PaymentWebhookReceived;
use App\Jobs\ProcessBibAndSendEmailJob;
use App\Models\OrderPayment;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Log;
use App\Services\JinahPaymentService;

class JinahListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentWebhookReceived $event): void
    {
        $payload = $event->payload;

        Log::info('[Jinah] Webhook received', ['payload' => $payload->toArray()]);

        $refId = explode('_', $payload->merchantOrderId)[2];

        $payment = Payment::find($refId);
        if (!$payment) {
            Log::error('[Jinah] Payment not found for reference ID: ' . $refId);
        }

        $statusCode = 0;
        if ($payload->isPaymentSuccessful()) {
            $statusCode = JinahPaymentService::STATUS_SUCCESS;
        } else if ($payload->isPaymentFailed()) {
            $statusCode = JinahPaymentService::STATUS_FAILED;
        } else if ($payload->isPaymentPending()) {
            $statusCode = JinahPaymentService::STATUS_PENDING;
        }
    
        $updated = app(JinahPaymentService::class)->updatePaymentStatus(
            $payment,
            $payload->transactionId,
            $statusCode,
            $payload->paymentMethod,
            $payload->amount,
            $payload->rawPayload
        );

        if ($updated && $statusCode == JinahPaymentService::STATUS_SUCCESS) {
            ProcessBibAndSendEmailJob::dispatch($payment['reference_id']);
        }

        Log::info('[Jinah] payment processed for payment ID: ' . $payment->id);
    }
}
