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
use Illuminate\Support\Str;

class DokuService implements PaymentServiceContract
{
	private array $config;
	private string $baseUrl;
	private string $clientId;
	private string $secretKey;

	public function __construct(array $config)
	{
		$this->config = $config;
		$this->baseUrl = $config['environment'] === 'production'
			? $config['services']['doku']['production_url']
			: $config['services']['doku']['development_url'];

		$this->clientId = $config['services']['doku']['client_id'];
		$this->secretKey = $config['services']['doku']['secret_key'];
	}

	public function getServiceName(): string
	{
		return 'doku';
	}

	public function initiate(PaymentRequest $request): PaymentResponse
	{
		$response = $this->createPayment($request);

		return $this->mapPaymentResponse($request, $response);
	}

	public function check(string $orderId): WebhookPayload
	{
		$response = $this->sendSignedRequest('/orders/v1/status/' . rawurlencode($orderId), [], 'GET');

		return WebhookPayload::fromDoku($response);
	}

	public function initiateChannel(PaymentRequest $request, $type): PaymentResponse
	{
		$response = $this->createPayment($request, $type);

		return $this->mapPaymentResponse($request, $response, $type);
	}

	private function createPayment(PaymentRequest $request, ?string $paymentMethodType = null): array
	{
		$payload = $this->buildPayload($request, $paymentMethodType);

		return $this->sendSignedRequest('/checkout/v1/payment', $payload);
	}

	private function buildPayload(PaymentRequest $request, ?string $paymentMethodType = null): array
	{
		$amount = (int) round($request->amount + $request->getAdminFeeValue());
		[$firstName, $lastName] = $this->splitCustomerName($request->customerName);
		$phone = $this->normalizePhone($request->customerPhone);

		$payload = [
			'order' => [
				'amount' => $amount,
				'invoice_number' => $request->orderId,
				'currency' => strtoupper($request->currency ?: 'IDR'),
				'callback_url' => $request->callbackUrl ?: route('jinah.webhook', ['service' => 'doku']),
				'callback_url_cancel' => $request->cancelUrl ?: route('jinah.payment.failed', ['transactionId' => $request->orderId]),
				'callback_url_result' => $request->returnUrl ?: route('jinah.payment.completed', ['transactionId' => $request->orderId]),
			],
			'payment' => [
				'payment_due_date' => 60,
				'type' => 'SALE',
			],
			'customer' => [
				'id' => $request->orderId,
				'name' => $firstName,
				'last_name' => $lastName,
				'phone' => $phone,
				'email' => $request->customerEmail,
				'country' => 'ID',
			],
		];

		$lineItems = $this->buildLineItems($request);
		if (!empty($lineItems)) {
			$payload['order']['line_items'] = $lineItems;
			$payload['additional_info']['line_items'] = $lineItems;
		}

		if (!empty($paymentMethodType)) {
			$payload['payment']['payment_method_types'] = [
				$this->mapChannelType($paymentMethodType),
			];
		}

		if (!empty($request->callbackUrl)) {
			$payload['additional_info']['override_notification_url'] = $request->callbackUrl;
		}

		return $payload;
	}

	private function buildLineItems(PaymentRequest $request): array
	{
		$lineItems = [];

		foreach ($request->items as $index => $item) {
			$lineItems[] = $this->mapItem($item, $index);
		}

		if ($request->getAdminFeeValue() > 0) {
			$lineItems[] = [
				'id' => (string) (count($lineItems) + 1),
				'name' => $request->getAdminFeeName(),
				'quantity' => 1,
				'price' => (int) round($request->getAdminFeeValue()),
				'sku' => 'ADMIN_FEE',
				'category' => 'service',
				'type' => 'fee',
			];
		}

		if (empty($lineItems)) {
			$lineItems[] = [
				'id' => '1',
				'name' => $request->description,
				'quantity' => 1,
				'price' => (int) round($request->amount),
				'sku' => Str::limit($request->orderId, 64, ''),
				'category' => 'general',
				'type' => 'payment',
			];
		}

		return $lineItems;
	}

	private function mapItem(PaymentItemRequest $item, int $index): array
	{
		return [
			'id' => (string) ($index + 1),
			'name' => $item->name,
			'quantity' => $item->quantity,
			'price' => (int) round($item->price),
			'sku' => $item->sku,
			'category' => $item->category ?: 'general',
			'type' => $item->brand ?: 'product',
		];
	}

	private function mapPaymentResponse(PaymentRequest $request, array $response, ?string $type = null): PaymentResponse
	{
		$responseData = $response['response'] ?? [];
		$paymentData = $responseData['payment'] ?? [];
		$orderData = $responseData['order'] ?? [];
		$messages = $response['message'] ?? $response['error_messages'] ?? [];
		$message = is_array($messages) ? implode(', ', $messages) : (string) $messages;
		$status = $this->mapDokuStatus($responseData['transaction']['status'] ?? null);

		return new PaymentResponse(
			success: empty($response['error_messages']),
			via: $type ? $this->mapChannelType($type) : null,
			transactionId: $paymentData['token_id'] ?? $paymentData['url'] ?? $request->orderId,
			merchantOrderId: $orderData['invoice_number'] ?? $request->orderId,
			status: $status ?? 'pending',
			amount: isset($orderData['amount']) ? (float) $orderData['amount'] : (float) ($request->amount + $request->getAdminFeeValue()),
			currency: $orderData['currency'] ?? strtoupper($request->currency ?: 'IDR'),
			paymentUrl: $paymentData['url'] ?? null,
			message: $message ?: null,
			errorCode: $response['response_code'] ?? null,
			redirectUrl: $paymentData['url'] ?? null,
			expiryTime: isset($paymentData['expired_date']) ? Carbon::createFromFormat('YmdHis', $paymentData['expired_date'], 'UTC') : null,
			rawResponse: $response
		);
	}

	private function sendSignedRequest(string $endpoint, array $body, string $method = 'POST'): array
	{
		$requestId = (string) Str::uuid();
		$requestTimestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
		$jsonBody = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES);
		$digest = $jsonBody === '' ? null : base64_encode(hash('sha256', $jsonBody, true));

		$signatureParts = [
			'Client-Id:' . $this->clientId,
			'Request-Id:' . $requestId,
			'Request-Timestamp:' . $requestTimestamp,
			'Request-Target:' . $endpoint,
		];

		if ($digest !== null) {
			$signatureParts[] = 'Digest:' . $digest;
		}

		$signature = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', implode("\n", $signatureParts), $this->secretKey, true));

		$headers = [
			'Client-Id' => $this->clientId,
			'Request-Id' => $requestId,
			'Request-Timestamp' => $requestTimestamp,
			'Signature' => $signature,
			'Content-Type' => 'application/json',
		];

		if ($digest !== null) {
			$headers['Digest'] = $digest;
		}

		try {
			$httpClient = Http::withHeaders($headers)->retry(3, function (int $attempt, Exception $exception) {
				return $attempt * 1000;
			});

			if ($method === 'GET') {
				return $httpClient->get($this->baseUrl . $endpoint)->throw()->json();
			}

			return $httpClient->withBody($jsonBody, 'application/json')->post($this->baseUrl . $endpoint)->throw()->json();
		} catch (\Illuminate\Http\Client\RequestException $e) {
			$responseBody = $e->response ? $e->response->body() : null;

			return $responseBody ? json_decode($responseBody, true) : [];
		}
	}

	private function splitCustomerName(?string $customerName): array
	{
		$name = trim($customerName ?: 'Customer');
		$nameSplit = preg_split('/\s+/', $name, 2) ?: ['Customer'];

		return [
			$nameSplit[0],
			$nameSplit[1] ?? $nameSplit[0],
		];
	}

	private function normalizePhone(?string $phone): string
	{
		$normalized = preg_replace('/\D+/', '', $phone ?: '');

		if ($normalized === '') {
			return '6280000000000';
		}

		if (str_starts_with($normalized, '0')) {
			return '62' . substr($normalized, 1);
		}

		if (!str_starts_with($normalized, '62')) {
			return '62' . $normalized;
		}

		return $normalized;
	}

	private function mapChannelType(string $type): string
	{
		return match (strtolower($type)) {
			'vabca' => 'VIRTUAL_ACCOUNT_BCA',
			'vabri' => 'VIRTUAL_ACCOUNT_BRI',
			'vabni' => 'VIRTUAL_ACCOUNT_BNI',
			'vamandiri' => 'VIRTUAL_ACCOUNT_BANK_MANDIRI',
			'vapermata' => 'VIRTUAL_ACCOUNT_BANK_PERMATA',
			'vacimb', 'vacimbniaga' => 'VIRTUAL_ACCOUNT_BANK_CIMB',
			'vadanamon' => 'VIRTUAL_ACCOUNT_BANK_DANAMON',
			'vabsi' => 'VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI',
			'vadoku' => 'VIRTUAL_ACCOUNT_DOKU',
			'qris', 'qr' => 'QRIS',
			'cc', 'creditcard', 'credit_card' => 'CREDIT_CARD',
			'alfamart', 'alfa' => 'ONLINE_TO_OFFLINE_ALFA',
			'indomaret' => 'ONLINE_TO_OFFLINE_INDOMARET',
			'shopeepay' => 'EMONEY_SHOPEE_PAY',
			'ovo' => 'EMONEY_OVO',
			'dana' => 'EMONEY_DANA',
			'akulaku' => 'PEER_TO_PEER_AKULAKU',
			'kredivo' => 'PEER_TO_PEER_KREDIVO',
			'indodana' => 'PEER_TO_PEER_INDODANA',
			default => strtoupper($type),
		};
	}

	private function mapDokuStatus(?string $status): ?string
	{
		return match ($status) {
			'SUCCESS' => 'completed',
			'PENDING', 'REDIRECT', 'TIMEOUT' => 'pending',
			'FAILED', 'EXPIRED', 'VOIDED' => 'failed',
			'REFUNDED', 'PARTIAL_REFUNDED' => 'refunded',
			default => $status ? strtolower($status) : null,
		};
	}
}
