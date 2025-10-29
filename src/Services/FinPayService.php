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
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

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
        return WebhookPayload::fromFinpay($response['data']);
    }

    private function buildPayload(PaymentRequest $request): array
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

    // /**
    //  * Get access token for API authentication
    //  */
    // public function getAccessToken(): string
    // {
    //     if ($this->accessToken) {
    //         return $this->accessToken;
    //     }

    //     try {
    //         $response = $this->httpClient->post('/api/v1/auth/token', [
    //             'json' => [
    //                 'client_id' => $this->config['services']['finpay']['client_id'],
    //                 'client_secret' => $this->config['services']['finpay']['client_secret'],
    //                 'grant_type' => 'client_credentials'
    //             ]
    //         ]);

    //         $data = json_decode($response->getBody()->getContents(), true);

    //         if (!isset($data['access_token'])) {
    //             throw ApiException::authenticationFailed();
    //         }

    //         $this->accessToken = $data['access_token'];
    //         return $this->accessToken;

    //     } catch (ClientException $e) {
    //         if ($e->getResponse()->getStatusCode() === 401) {
    //             throw ApiException::authenticationFailed();
    //         }
    //         throw $this->handleClientException($e);
    //     } catch (ConnectException $e) {
    //         throw ApiException::connectionFailed($this->baseUrl, $e->getMessage());
    //     } catch (RequestException $e) {
    //         throw $this->handleRequestException($e);
    //     }
    // }

    // /**
    //  * Create a payment charge
    //  */
    // public function charge(PaymentRequest $request): PaymentResponse
    // {
    //     try {
    //         $this->logRequest('charge', $request->toArray());

    //         $response = $this->makeAuthenticatedRequest('POST', '/api/v1/payment/charge', [
    //             'json' => $request->toArray()
    //         ]);

    //         $data = json_decode($response->getBody()->getContents(), true);

    //         if ($data['success'] ?? false) {
    //             $result = PaymentResponse::success($data);
    //             $this->logResponse('charge', $result->toArray());
    //             return $result;
    //         }

    //         $result = PaymentResponse::failed(
    //             $data['message'] ?? 'Payment charge failed',
    //             $data['error_code'] ?? null,
    //             $data
    //         );

    //         $this->logResponse('charge', $result->toArray());
    //         return $result;

    //     } catch (RequestException $e) {
    //         $error = $this->handleRequestException($e);
    //         $this->logError('charge', $error->getMessage(), $error->getContext());
    //         throw $error;
    //     }
    // }

    // /**
    //  * Inquiry payment status
    //  */
    // public function inquiry(TransactionInquiry $inquiry): PaymentResponse
    // {
    //     try {
    //         $this->logRequest('inquiry', $inquiry->toArray());

    //         $response = $this->makeAuthenticatedRequest('POST', '/api/v1/payment/inquiry', [
    //             'json' => $inquiry->toArray()
    //         ]);

    //         $data = json_decode($response->getBody()->getContents(), true);

    //         if ($data['success'] ?? false) {
    //             $result = PaymentResponse::success($data);
    //             $this->logResponse('inquiry', $result->toArray());
    //             return $result;
    //         }

    //         $result = PaymentResponse::failed(
    //             $data['message'] ?? 'Payment inquiry failed',
    //             $data['error_code'] ?? null,
    //             $data
    //         );

    //         $this->logResponse('inquiry', $result->toArray());
    //         return $result;

    //     } catch (RequestException $e) {
    //         $error = $this->handleRequestException($e);
    //         $this->logError('inquiry', $error->getMessage(), $error->getContext());
    //         throw $error;
    //     }
    // }

    // /**
    //  * Cancel a payment
    //  */
    // public function cancel(string $transactionId): PaymentResponse
    // {
    //     try {
    //         $this->logRequest('cancel', ['transaction_id' => $transactionId]);

    //         $response = $this->makeAuthenticatedRequest('POST', '/api/v1/payment/cancel', [
    //             'json' => ['transaction_id' => $transactionId]
    //         ]);

    //         $data = json_decode($response->getBody()->getContents(), true);

    //         if ($data['success'] ?? false) {
    //             $result = PaymentResponse::success($data);
    //             $this->logResponse('cancel', $result->toArray());
    //             return $result;
    //         }

    //         $result = PaymentResponse::failed(
    //             $data['message'] ?? 'Payment cancellation failed',
    //             $data['error_code'] ?? null,
    //             $data
    //         );

    //         $this->logResponse('cancel', $result->toArray());
    //         return $result;

    //     } catch (RequestException $e) {
    //         $error = $this->handleRequestException($e);
    //         $this->logError('cancel', $error->getMessage(), $error->getContext());
    //         throw $error;
    //     }
    // }

    // /**
    //  * Make authenticated HTTP request
    //  */
    // private function makeAuthenticatedRequest(string $method, string $uri, array $options = []): ResponseInterface
    // {
    //     $token = $this->getAccessToken();

    //     $options['headers'] = array_merge(
    //         $options['headers'] ?? [],
    //         ['Authorization' => "Bearer {$token}"]
    //     );

    //     return $this->httpClient->request($method, $uri, $options);
    // }

    // /**
    //  * Handle client exceptions (4xx errors)
    //  */
    // private function handleClientException(ClientException $e): ApiException
    // {
    //     $statusCode = $e->getResponse()->getStatusCode();
    //     $responseBody = $e->getResponse()->getBody()->getContents();

    //     return match ($statusCode) {
    //         401 => ApiException::authenticationFailed(),
    //         429 => ApiException::rateLimitExceeded(),
    //         default => ApiException::serverError($statusCode, $responseBody),
    //     };
    // }

    // /**
    //  * Handle request exceptions
    //  */
    // private function handleRequestException(RequestException $e): ApiException
    // {
    //     if ($e instanceof ConnectException) {
    //         return ApiException::connectionFailed($this->baseUrl, $e->getMessage());
    //     }

    //     if ($e instanceof ServerException) {
    //         $statusCode = $e->getResponse()->getStatusCode();
    //         $responseBody = $e->getResponse()->getBody()->getContents();
    //         return ApiException::serverError($statusCode, $responseBody);
    //     }

    //     if ($e instanceof ClientException) {
    //         return $this->handleClientException($e);
    //     }

    //     if (str_contains($e->getMessage(), 'timeout')) {
    //         return ApiException::timeout();
    //     }

    //     return new ApiException("Request failed: " . $e->getMessage(), 500, $e);
    // }

    // /**
    //  * Get the service name/identifier
    //  */
    // public function getServiceName(): string
    // {
    //     return 'finpay';
    // }

    // /**
    //  * Check if the service is available/configured
    //  */
    // public function isConfigured(): bool
    // {
    //     $finpayConfig = $this->config['services']['finpay'] ?? [];

    //     return !empty($finpayConfig['client_id']) && 
    //            !empty($finpayConfig['client_secret']) &&
    //            !empty($this->baseUrl);
    // }

    // /**
    //  * Get service-specific configuration
    //  */
    // public function getServiceConfig(): array
    // {
    //     return $this->config['services']['finpay'] ?? [];
    // }

    // /**
    //  * Log request
    //  */
    // private function logRequest(string $operation, array $data): void
    // {
    //     if ($this->config['logging']['enabled'] ?? false) {
    //         Log::channel($this->config['logging']['channel'])
    //             ->info("FinPay {$operation} request", [
    //                 'operation' => $operation,
    //                 'data' => $data,
    //             ]);
    //     }
    // }

    // /**
    //  * Log response
    //  */
    // private function logResponse(string $operation, array $data): void
    // {
    //     if ($this->config['logging']['enabled'] ?? false) {
    //         Log::channel($this->config['logging']['channel'])
    //             ->info("FinPay {$operation} response", [
    //                 'operation' => $operation,
    //                 'data' => $data,
    //             ]);
    //     }
    // }

    // /**
    //  * Log error
    //  */
    // private function logError(string $operation, string $message, array $context = []): void
    // {
    //     if ($this->config['logging']['enabled'] ?? false) {
    //         Log::channel($this->config['logging']['channel'])
    //             ->error("FinPay {$operation} error: {$message}", [
    //                 'operation' => $operation,
    //                 'context' => $context,
    //             ]);
    //     }
    // }
}