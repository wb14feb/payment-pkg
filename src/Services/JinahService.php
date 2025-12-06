<?php

namespace AnyTech\Jinah\Services;

use AnyTech\Jinah\Contracts\PaymentServiceContract;
use AnyTech\Jinah\DTOs\PaymentItemRequest;
use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentResponse;
use AnyTech\Jinah\DTOs\TransactionInquiry;
use AnyTech\Jinah\DTOs\WebhookPayload;
use AnyTech\Jinah\Exceptions\ApiException;
use AnyTech\Jinah\Exceptions\PaymentException;
use AnyTech\Jinah\Factories\PaymentServiceFactory;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class JinahService implements PaymentServiceContract
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
        return 'jinah';
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $payload = $this->buildPayload($request);
        Cache::put('jinah_payload_' . $request->orderId, $payload, now()->addMinutes(20));
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
        $response = $this->sendSignedRequest('/pg/payment/card/check/' . $orderId, [], 'GET');
        return WebhookPayload::fromFinpay($response['data']);
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
                'itemAmount' => $amount,
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
        $channelUsed = config('jinah.services.jinah.channels.' . $type);
        $service = app()->makeWith('jinah.service', ['service' => $channelUsed['service']]);
        return $service->initiateChannel($request, $type);
    }
}