<?php

namespace AnyTech\Jinah\Services;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\DTOs\PaymentItemRequest;
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\WebhookPayload;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Http;

class FinPayService implements PaymentServiceContract
{
    private array $config;
    private string $baseUrl;
    private ?string $accessToken = null;
    private string $clientSecret;
    private string $clientId;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['environment'] === 'production'
            ? $config['services']['finpay']['production_url']
            : $config['services']['finpay']['development_url'];

        $this->clientId = $config['services']['finpay']['client_id'];
        $this->clientSecret = $config['services']['finpay']['client_secret'];
    }

    public function getServiceName(): string
    {
        return 'finpay';
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $payload = $this->buildPayload($request);
        $response = $this->sendSignedRequest('/pg/payment/card/initiate', $payload);
        return new PaymentResponse(
            success: str_starts_with($response['responseCode'], '2'),
            transactionId: $request->orderId,
            merchantOrderId: $request->orderId,
            redirectUrl: $response['redirectUrl'] ?? $response['redirecturl'] ?? null,
            expiryTime: isset($response['expiryTime']) ? \Carbon\Carbon::parse($response['expiryTime']) : null,
            rawResponse: $response
        );
    }

    public function check(string $orderId): WebhookPayload
    {
        $response = $this->sendSignedRequest('/pg/payment/card/check/' . $orderId, [], 'GET');
        return WebhookPayload::fromFinpay($response['data'] ?? []);
    }

    private function buildPayload(PaymentRequest $request, $sourceOfFunds = null): array
    {
        $amount = intval($request->amount);
        $nameSplit = explode(' ', $request->customerName, 2);
        $firstName = $nameSplit[0];
        $lastName = $nameSplit[1] ?? $firstName;
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
            ],
            'url' => [
                'callbackUrl' => route('jinah.webhook', ['service' => 'finpay']),
                'successUrl' => $request->returnUrl,
                'failureUrl' => $request->cancelUrl,
                'backUrl' => $request->returnUrl,
            ],
            'customer' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $request->customerEmail,
                'mobilePhone' => $phone,
            ],
        ];
        if (!empty($request->items)) {
            $payload['order'] = [
                ...$payload['order'],
                'itemAmount' => $request->discount ? null : $amount,
                'item' => $request->discount ? null : array_map(function (PaymentItemRequest $item, $index) use ($request) {
                    $unitPrice = $item->price;
                    return [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unitPrice' => $unitPrice,
                        'sku' => $item->sku,
                        'brand' => $item->brand,
                        'category' => $item->category,
                        'description' => $item->description,
                    ];
                }, $request->items, array_keys($request->items))
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
                'amount' => $amount + intval($request->getAdminFeeValue()),
                'itemAmount' => $amount + intval($request->getAdminFeeValue()),
            ];
            $payload['order']['item'][] = [
                'name' => $request->getAdminFeeName(),
                'quantity' => 1,
                'unitPrice' => intval($request->getAdminFeeValue()),
            ];
        }
        return $payload;
    }

    private function sendSignedRequest(string $endpoint, array $body, string $method = 'POST'): array
    {
        $baseUrl = $this->baseUrl;
        $clientSecret = $this->clientSecret;
        $clientId = $this->clientId;

        $credentials = "{$clientId}:{$clientSecret}";
        $authorization = 'Basic ' . base64_encode($credentials);
        try {
            $httpClient = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
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
        $payload = $this->buildPayload($request, $type);
        $response = $this->sendSignedRequest('/pg/payment/card/initiate', $payload);
        $contentType = null;
        $content = null;
        if (str_starts_with($type, 'va')) {
            $contentType = PaymentResponse::CONTENT_TYPE_VA;
            $content = $response['paymentCode'] ?? null;
        } elseif (str_starts_with($type, 'qr')) {
            $contentType = PaymentResponse::CONTENT_TYPE_QR;
            $content = $response['stringQr'] ?? null;
            $content = (new QRCode())->render($content);
        } 
        // else if (str_starts_with($type, 'cc')) {
        //     $contentType = PaymentResponse::CONTENT_TYPE_CC;
        //     $content = $response['redirecturl'] ?? null;
        // }
        return new PaymentResponse(
            success: str_starts_with($response['responseCode'], '2'),
            transactionId: $request->orderId,
            merchantOrderId: $request->orderId,
            redirectUrl: $response['redirecturl'] ?? $response['redirectUrl'] ?? null,
            expiryTime: isset($response['expiryTime']) ? \Carbon\Carbon::parse($response['expiryTime']) : null,
            rawResponse: $response,
            contentType: $contentType,
            content: $content
        );
    }
}