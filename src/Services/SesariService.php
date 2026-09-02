<?php

namespace AnyTech\Jinah\Services;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\DTOs\PaymentItemRequest;
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\WebhookPayload;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use Exception;
use Http;

class SesariService implements PaymentServiceContract
{
    private array $config;
    private string $baseUrl;
    private ?string $accessToken = null;
    private string $serviceKey;
    private string $serviceUUID;
    private string $serviceVA;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['environment'] === 'production'
            ? $config['services']['sesari']['production_url']
            : $config['services']['sesari']['development_url'];

        $this->serviceUUID = $config['services']['sesari']['service_uuid'];
        $this->serviceKey = $config['services']['sesari']['service_key'];
        $this->serviceVA = $config['services']['sesari']['service_va'];
    }

    public function getServiceName(): string
    {
        return 'sesari';
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $payload = $this->buildPayload($request);
        $response = $this->sendSignedRequest('/api/transactions/issue', $payload);
        $transaction = $response['transaction'];
        return new PaymentResponse(
            success: $response['success'] ?? false,
            transactionId: $transaction['code'],
            merchantOrderId: $request->orderId,
            redirectUrl: $transaction['interactable']['url'] ?? null,
            expiryTime: isset($transaction['expiryTime']) ? \Carbon\Carbon::parse($transaction['expiryTime']) : null,
            rawResponse: $response
        );
    }

    public function check(string $orderId): WebhookPayload
    {
        $response = $this->sendSignedRequest('/api/transactions/status', [
            'id' => $orderId,
        ]);
        return WebhookPayload::fromSesari($response);
    }

    private function buildPayload(PaymentRequest $request, $sourceOfFunds = null): array
    {
        $phone = $request->customerPhone ?? '0';
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }
        $phone = str_pad($phone, 10, '0', STR_PAD_RIGHT);
        if (!str_starts_with($phone, '+')) {
            $phone = "+{$phone}";
        }
        $referenceId = $request->orderId;
        $total = 0;
        $detail = [
            'buyer_name' => $request->customerName,
            'buyer_email' => $request->customerEmail,
            'buyer_phone' => $phone,
            'description' => $request->description,
            'return_url' => route('jinah.payment.completed', ['transactionId' => $referenceId]),
            'cancel_url' => route('jinah.payment.failed', ['transactionId' => $referenceId]),
        ];
        if (!empty($request->items)) {
            $detail['items'] = array_map(function (PaymentItemRequest $item, $index) use (&$total) {
                $total += $item->price * $item->quantity;
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'image_url' => $item->imageUrl ?? null,
                ];
            }, $request->items, array_keys($request->items));
        }
        if ($request->getAdminFeeValue() > 0) {
            $detail['items'][] = [
                'name' => 'Admin Fee',
                'quantity' => 1,
                'price' => intval($request->getAdminFeeValue()),
                'image_url' => null,
            ];
        }

        $payload = [
            "amount" => $total,
            "details" => $detail,
            "ref_id" => $referenceId,
        ];

        return $payload;
    }

    private function sendSignedRequest(string $endpoint, array $body, string $method = 'POST'): array
    {
        $baseUrl = $this->baseUrl;
        $serviceKey = $this->serviceKey;
        $serviceUUid = $this->serviceUUID;

        $body = array_merge($body, [
            'service_uuid' => $serviceUUid,
        ]);

        try {
            $httpClient = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->retry(3, function (int $attempt, Exception $exception) {
                return $attempt * 1000;
            });
            if ($method === 'GET') {
                if (!empty($body)) {
                    $endpoint .= '?' . http_build_query($body);
                }
                return $httpClient->get($baseUrl . $endpoint)->throw()->json();
            } else {
                return $httpClient->post($baseUrl . $endpoint, $body)->throw()->json();
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $responseBody = $e->response ? $e->response->body() : null;
            return $responseBody ? json_decode($responseBody, true) : [];
        }
    }

    public function initiateChannel(PaymentRequest $request, $type): PaymentResponse
    {
        return $this->initiate($request);
    }
}