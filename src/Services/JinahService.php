<?php

namespace AnyTech\Jinah\Services;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\DTOs\PaymentItemRequest;
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\WebhookPayload;
use Carbon\Carbon;
use Exception;
use Http;
use Illuminate\Support\Facades\Cache;

class JinahService implements PaymentServiceContract
{
    private array $config;
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getServiceName(): string
    {
        return 'jinah';
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $payload = $this->buildPayload($request);
        Cache::put('jinah_payload_' . $request->orderId, $payload, now()->addHours(3));
        return new PaymentResponse(
            success: true,
            transactionId: $request->orderId,
            merchantOrderId: $request->orderId,
            redirectUrl: route('jinah.payment.index', ['order_id' => $request->orderId]),
            expiryTime: Carbon::now()->addHour(1),
            rawResponse: []
        );
    }

    public function check(string $orderId): WebhookPayload
    {
        $channelUsed = Cache::get('jinah_channel_type_' . $orderId);
        if (empty($channelUsed)) {
            $channelUsed['service'] = 'finpay';
        }
        $service = app()->makeWith('jinah.service', ['service' => $channelUsed['service']]);
        return $service->check($orderId);
    }

    private function buildPayload(PaymentRequest $request, $sourceOfFunds = null): array
    {
        $amount = intval($request->amount);
        $phone = $request->customerPhone ?? '0';
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }
        $phone = str_pad($phone, 10, '0', STR_PAD_RIGHT);
        if (!str_starts_with($phone, '+')) {
            $phone = "+{$phone}";
        }
        $payload = [
            'order' => [
                'id' => $request->orderId,
                'amount' => $amount,
                'description' => $request->description,
                'discount' => $request->discount,
            ],
            'url' => [
                'callbackUrl' => route('jinah.webhook', ['service' => 'finpay']),
                'successUrl' => $request->returnUrl,
                'failureUrl' => $request->cancelUrl,
                'backUrl' => $request->returnUrl,
            ],
            'customer' => [
                'name' => $request->customerName,
                'email' => $request->customerEmail,
                'phone' => $phone,
            ],
        ];
        if (!empty($request->items)) {
            $payload['order'] = [
                ...$payload['order'],
                'item' => array_map(function (PaymentItemRequest $item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unitPrice' => $item->price,
                        'sku' => $item->sku,
                        'brand' => $item->brand,
                        'category' => $item->category,
                        'description' => $item->description,
                    ];
                }, $request->items)
            ];
        }
        if ($sourceOfFunds) {
            $payload['sourceOfFunds'] = [
                'type' => $sourceOfFunds
            ];
        }
        if ($request->getAdminFeeValue() > 0) {
            $payload['order'] = [
                ...$payload['order'],
                'adminFee' => intval($request->getAdminFeeValue()),
            ];
        }
        return $payload;
    }

    public function initiateChannel(PaymentRequest $request, $type): PaymentResponse
    {
        $channelUsed = config('jinah.services.jinah.channels.' . $type);
        Cache::put('jinah_channel_type_' . $request->orderId, $channelUsed, now()->addHours(3));
        $service = app()->makeWith('jinah.service', ['service' => $channelUsed['service']]);
        return $service->initiateChannel($request, $type);
    }
}