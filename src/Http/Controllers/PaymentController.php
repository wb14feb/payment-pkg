<?php

namespace AnyTech\Jinah\Http\Controllers;

use AnyTech\Jinah\DTOs\PaymentRequest;
use AnyTech\Jinah\DTOs\PaymentItemRequest;
use AnyTech\Jinah\Facades\Jinah;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get available payment methods with fees
     */
    private function getPaymentMethods(): array
    {
        return [
            // ['id' => 'cc', 'name' => 'Credit Card', 'icon' => 'credit-card', 'fee' => 0],
            ['id' => 'qris', 'name' => 'QRIS', 'icon' => 'qrcode', 'fee' => 0],
            ['id' => 'vabca', 'name' => 'Virtual Account BCA', 'icon' => 'bca', 'fee' => 3500],
            ['id' => 'vabni', 'name' => 'Virtual Account BNI', 'icon' => 'bni', 'fee' => 3500],
            ['id' => 'vamandiri', 'name' => 'Virtual Account Mandiri', 'icon' => 'mandiri', 'fee' => 3500],
            ['id' => 'vabri', 'name' => 'Virtual Account BRI', 'icon' => 'bri', 'fee' => 3500],
        ];
    }

    /**
     * Display the payment page
     */
    public function index()
    {
        $payload = $this->loadOrderPayload(request()->query('order_id'));
        
        $service = app()->makeWith('jinah.service', ['service' => 'jinah']);
        $paymentCheck = $service->check(request()->query('order_id'));
        if ($paymentCheck->transactionId) {
            return redirect()
                ->route('jinah.payment.success', ['transactionId' => request()->query('order_id')]);
        }

        $items = [];
        if (isset($payload['order']['item'])) {
            $id = 1;
            $items = array_map(function ($item) use (&$id) {
                return [
                    'id' => $id++,
                    'name' => $item['name'] ?? 'Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'description' => $item['description'] ?? '',
                    'price' => $item['unitPrice'] ?? 0,
                    'currency' => $payload['order']['currency'] ?? 'IDR',
                ];
            }, $payload['order']['item']);
        }

        $customerInfo = $payload['customer'] ?? [];

        // Available payment methods
        $paymentMethods = $this->getPaymentMethods();

        $orderId = request()->query('order_id');
        $order = $payload['order'] ?? null;

        return view('jinah::payment.index', compact('items', 'paymentMethods', 'customerInfo', 'orderId', 'order'));
    }

    /**
     * Process the payment
     */
    public function process(Request $request, string $transactionId)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()->withErrors($validator)->withInput();
        }

        try {
            $orderPayload = $this->loadOrderPayload($transactionId);
            $service = app()->makeWith('jinah.service', ['service' => 'jinah']);
            
            // Get payment method fee
            $paymentMethods = $this->getPaymentMethods();
            $selectedMethod = collect($paymentMethods)->firstWhere('id', $request->input('payment_method'));
            $fee = $selectedMethod['fee'] ?? 0;
            
            // Calculate total amount with fee
            $baseAmount = $orderPayload['order']['amount'] ?? 0;
            $totalAmount = $baseAmount + $fee;
            
            // Create payment items
            $paymentItems = collect($orderPayload['order']['item'] ?? [])->map(function ($item) {
                return new PaymentItemRequest(
                    name: $item['name'],
                    quantity: $item['quantity'],
                    price: $item['unitPrice'],
                    sku: $item['sku'] ?? null,
                    brand: $item['brand'] ?? null,
                    category: $item['category'] ?? null,
                    description: $item['description'] ?? null,
                );
            })->toArray();

            // Create payment request
            $paymentRequest = new PaymentRequest(
                orderId: $transactionId,
                amount: $baseAmount,
                currency: $orderPayload['order']['currency'] ?? 'IDR',
                customerEmail: $request->input('customer_email'),
                customerName: $request->input('customer_name'),
                customerPhone: $request->input('customer_phone'),
                description: 'Pembayaran untuk item yang dipilih',
                items: $paymentItems,
                discount: $orderPayload['order']['discount'] ?? 0,
            );

            // Process payment through Jinah
            $paymentResponse = $service->initiateChannel($paymentRequest, $request->input('payment_method'));
            Cache::put('jinah_order_destination_' . $transactionId, [
                'content_type' => $paymentResponse->contentType,
                'content' => $paymentResponse->content,
                'amount' => $baseAmount,
                'taxedAmount' => $totalAmount,
                'payment_method_name' => $selectedMethod['name'] ?? null,
            ], now()->addHours(3));

            if ($paymentResponse->success && $paymentResponse->content != null) {
                return redirect()
                    ->route('jinah.payment.success', ['transactionId' => $transactionId])
                    ->with('order', [
                        'order_id' => $transactionId,
                        'content' => $paymentResponse->content,
                        'content_type' => $paymentResponse->contentType,
                        'amount' => $paymentRequest->amount,
                    ]);
            } else if ($paymentResponse->success) {
                return redirect($paymentResponse->redirectUrl ?? '');
            }

            return redirect()->route('jinah.payment.failed', ['transactionId' => $transactionId]);
        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage(), [
                'order_id' => $transactionId,
                'stack' => $e->getTraceAsString(),
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pemrosesan pembayaran gagal: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['payment' => 'Pemrosesan pembayaran gagal. Silakan coba lagi.'])->withInput();
        }
    }

    
    public function success(Request $request, string $transactionId)
    {
        $orderDestination = Cache::get('jinah_order_destination_' . $transactionId);
        if (empty($orderDestination)) {
            return redirect()->route('jinah.payment.failed', ['transactionId' => $transactionId, 'error' => 'Pesanan sudah kedaluwarsa atau tidak ditemukan.']);
        }

        $amount = $orderDestination['taxedAmount'] ?? null;
        $content = $orderDestination['content'] ?? null;
        $contentType = $orderDestination['content_type'] ?? null;
        $paymentMethodName = $orderDestination['payment_method_name'] ?? null;
        
        return view('jinah::payment.success', compact('transactionId', 'amount', 'content', 'contentType', 'paymentMethodName'));
    }

    public function failed(Request $request, string $transactionId): View
    {
        $error = $request->query('error', 'Payment failed');
        
        return view('jinah::payment.failed', compact('transactionId', 'error'));
    }

    public function status(string $transactionId)
    {
        $service = app()->makeWith('jinah.service', ['service' => 'jinah']);
        $statusResponse = $service->check($transactionId);
        $orderPayload = $this->loadOrderPayload($transactionId);

        if ($statusResponse->isPaymentSuccessful()) {
            $backUrl = $orderPayload['url']['successUrl'] ?? $orderPayload['url']['backUrl'] ?? null;
            if ($backUrl) {
                return redirect($backUrl)->with('success', 'Pembayaran berhasil untuk ID transaksi: ' . $transactionId);
            }
            return back()->with([
                'success' => 'Pembayaran berhasil untuk ID transaksi: ' . $transactionId,
                'order' => [
                    'order_id' => $transactionId,
                ]
            ]);
        }

        return back()->with([
            'error' => 'Pembayaran belum selesai untuk ID transaksi: ' . $transactionId,
            'order' => [
                'order_id' => $transactionId,
            ]
        ]);
    }

    private function loadOrderPayload(string $orderId): ?array
    {
        return Cache::get('jinah_payload_' . $orderId);
    }
}