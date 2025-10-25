<?php

namespace AnyTech\Jinah\Http\Controllers;

use AnyTech\Jinah\DTOs\WebhookPayload;
use AnyTech\Jinah\Events\PaymentWebhookReceived;
use AnyTech\Jinah\Events\PaymentSuccessful;
use AnyTech\Jinah\Events\PaymentFailed;
use AnyTech\Jinah\Events\PaymentPending;
use AnyTech\Jinah\Exceptions\JinahException;
use AnyTech\Jinah\Facades\Jinah;
use AnyTech\Jinah\Services\WebhookVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookVerificationService $verificationService
    ) {}

    /**
     * Universal webhook handler - routes to appropriate service automatically
     */
    public function handle(Request $request, string $service): JsonResponse
    {
        try {
            $this->logWebhookRequest($service, $request);

            // Verify webhook signature
            if (!$this->verificationService->verifySignature($service, $request)) {
                Log::warning("Invalid {$service} webhook signature", [
                    'service' => $service,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Create webhook payload DTO - uses service hint but can auto-detect
            $payload = WebhookPayload::fromRequest($request, $service);
            
            // Update service name if auto-detection found a different service
            $detectedService = $payload->service;
            if ($detectedService !== $service) {
                Log::info("Service auto-detection override", [
                    'url_service' => $service,
                    'detected_service' => $detectedService,
                    'using' => $detectedService,
                ]);
                $service = $detectedService;
            }
            
            // Process the webhook
            $this->processWebhook($service, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'service' => $service,
            ]);

        } catch (\Exception $e) {
            Log::error("{$service} webhook processing failed", [
                'service' => $service,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Auto-detection webhook handler (no service specified in URL)
     */
    public function handleAutoDetect(Request $request): JsonResponse
    {
        try {
            $detectedService = WebhookPayload::detectServiceFromRequest($request);
            
            $this->logWebhookRequest($detectedService, $request);
            Log::info("Auto-detected webhook service", ['service' => $detectedService]);

            // Verify webhook signature
            if (!$this->verificationService->verifySignature($detectedService, $request)) {
                Log::warning("Invalid {$detectedService} webhook signature", [
                    'service' => $detectedService,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Create webhook payload DTO
            $payload = WebhookPayload::fromRequest($request);
            
            // Process the webhook
            $this->processWebhook($detectedService, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'service' => $detectedService,
                'auto_detected' => true,
            ]);

        } catch (\Exception $e) {
            Log::error("Auto-detect webhook processing failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Process webhook payload
     */
    private function processWebhook(string $service, WebhookPayload $payload): void
    {
        // Switch to the appropriate service
        Jinah::switchService($service);

        // Dispatch general webhook event
        event(new PaymentWebhookReceived($payload));

        // Dispatch specific events based on payment status
        if ($payload->isPaymentSuccessful()) {
            event(new PaymentSuccessful($payload));
        } elseif ($payload->isPaymentFailed()) {
            event(new PaymentFailed($payload));
        } elseif ($payload->isPaymentPending()) {
            event(new PaymentPending($payload));
        }

        // Log successful processing
        Log::info("Webhook processed successfully", [
            'service' => $service,
            'event_type' => $payload->eventType,
            'transaction_id' => $payload->transactionId,
            'status' => $payload->status,
        ]);
    }

    /**
     * Log webhook request for debugging
     */
    private function logWebhookRequest(string $service, Request $request): void
    {
        if (config('jinah.logging.enabled', false)) {
            Log::channel(config('jinah.logging.channel', 'single'))
                ->info("Webhook received from {$service}", [
                    'service' => $service,
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all(),
                    'payload' => $request->all(),
                ]);
        }
    }
}