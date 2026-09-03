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

class ConversoService implements PaymentServiceContract
{
    private array $config;
    private string $baseUrl;
    private ?string $accessToken = null;
    private string $clientSecret;
    private string $clientId;
    private string $apiKey;
    private string $storeId;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['environment'] === 'production'
            ? $config['services']['converso']['production_url']
            : $config['services']['converso']['development_url'];

        // $this->clientId = $config['services']['converso']['client_id'];
        // $this->clientSecret = $config['services']['converso']['client_secret'];
        $this->apiKey = $config['services']['converso']['api_key'];
        $this->storeId = $config['services']['converso']['store_id'];
    }

    public function getServiceName(): string
    {
        return 'converso';
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $payload = $this->buildPayload($request);
        $response = $this->sendSignedRequest('/partner/v1/payments', $payload);
        return new PaymentResponse(
            success: isset($response['error']) && !empty($response['error']) ? false : true,
            transactionId: $response['id'],
            merchantOrderId: $request->orderId,
            redirectUrl: $response['redirecturl'] ?? $response['redirectUrl'] ?? null,
            expiryTime: isset($response['expires_at']) ? \Carbon\Carbon::parse($response['expires_at']) : null,
            rawResponse: $response,
        );
    }

    public function check(string $orderId): WebhookPayload
    {
        $response = $this->sendSignedRequest('/partner/v1/payments/' . $orderId, [], 'GET');
        return WebhookPayload::fromConverso($response);
    }

    private function buildPayload(PaymentRequest $request, ?string $sourceOfFunds = null): array
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
            'external_id' => $request->orderId,
            'channel' => $this->mapChannelType($sourceOfFunds),
            'amount' => $amount,
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'customer_phone' => $phone,
            'description' => $request->description,
            'fee_type' => $sourceOfFunds == 'qris' ? 'inclusive' : 'exclusive',
            'metadata' => [
                'base_amount' => $amount,
                'admin_fee' => intval($request->getAdminFeeValue()),
                'source_of_funds' => $sourceOfFunds,
            ],
        ];

        if ($this->mapChannelMethod($sourceOfFunds)) {
            $payload['method'] = $this->mapChannelMethod($sourceOfFunds);
        }

        if ($this->storeId && !empty($this->storeId)) {
            $payload['store_id'] = $this->storeId;
        }

        if ($request->getAdminFeeValue() > 0) {
            $payload['amount'] = $amount + intval($request->getAdminFeeValue());
        }

        return $payload;
    }

    private function sendSignedRequest(string $endpoint, array $body, string $method = 'POST'): array
    {
        $baseUrl = $this->baseUrl;
        $apiKey = $this->apiKey;

        try {
            $httpClient = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $apiKey,
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
        $response = $this->sendSignedRequest('/partner/v1/payments', $payload);
        $contentType = null;
        $content = null;
        if (str_starts_with($type, 'va')) {
            $contentType = PaymentResponse::CONTENT_TYPE_VA;
            $content = $response['instructions']['va_number'] ?? null;
        } elseif (str_starts_with($type, 'qr')) {
            $contentType = PaymentResponse::CONTENT_TYPE_QR;
            $content = $response['instructions']['qr_string'] ?? null;
            $content = (new QRCode(new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
            ])))->render($content);
        }  else if (str_starts_with($type, 'cc')) {
            $contentType = PaymentResponse::CONTENT_TYPE_CC;
            // $content = $response['redirecturl'] ?? null;
        }
        return new PaymentResponse(
            success: isset($response['error']) && !empty($response['error']) ? false : true,
            transactionId: $response['id'],
            merchantOrderId: $request->orderId,
            redirectUrl: $response['redirecturl'] ?? $response['redirectUrl'] ?? null,
            expiryTime: isset($response['expires_at']) ? \Carbon\Carbon::parse($response['expires_at']) : null,
            rawResponse: $response,
            contentType: $contentType,
            content: $content
        );
    }

    private function mapChannelType(string $channelType): string
	{
		return match (strtolower($channelType)) {
			'vabca' => 'VA',
            'vabri' => 'VA',
            'vabni' => 'VA',
            'vamandiri' => 'VA',
            'cc' => 'CARD',
            'qris' => 'QRIS',
			default => strtoupper($channelType),
		};
	}

    private function mapChannelMethod(string $channelMethod): ?string
	{
		return match (strtolower($channelMethod)) {
			'vabca' => 'BCA',
            'vabri' => 'BRI',
            'vabni' => 'BNI',
            'vamandiri' => 'MANDIRI',
			default => null,
		};
	}

}