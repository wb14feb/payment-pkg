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
     * Display the payment page
     */
    public function index(): View
    {
        $payload = $this->loadOrderPayload(request()->query('order_id'));
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
        $paymentMethods = [
            ['id' => 'cc', 'name' => 'Credit Card', 'icon' => 'credit-card'],
            ['id' => 'qris', 'name' => 'QRIS', 'icon' => 'qrcode'],
            ['id' => 'vabca', 'name' => 'Virtual Account BCA', 'icon' => 'bca'],
            ['id' => 'vabni', 'name' => 'Virtual Account BNI', 'icon' => 'bni'],
            ['id' => 'vamandiri', 'name' => 'Virtual Account Mandiri', 'icon' => 'mandiri'],
            ['id' => 'vabri', 'name' => 'Virtual Account BRI', 'icon' => 'bri'],
        ];

        $orderId = request()->query('order_id');

        return view('jinah::payment.index', compact('items', 'paymentMethods', 'customerInfo', 'orderId'));
    }

    /**
     * Process the payment
     */
    public function process(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
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
            $orderPayload = $this->loadOrderPayload($request->input('order_id'));
            $service = app()->makeWith('jinah.service', ['service' => 'jinah']);
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
                orderId: $request->input('order_id'),
                amount: $orderPayload['order']['amount'] ?? 0,
                currency: $orderPayload['order']['currency'] ?? 'IDR',
                customerEmail: $request->input('customer_email'),
                customerName: $request->input('customer_name'),
                customerPhone: $request->input('customer_phone'),
                description: 'Payment for selected items',
                items: $paymentItems,
            );

            // Process payment through Jinah
            $paymentResponse = $service->initiateChannel($paymentRequest, $request->input('payment_method'));

            if ($paymentResponse->success && $paymentResponse->content != null) {
                return redirect()
                    ->route('jinah.payment.success')
                    ->with('order', [
                        'order_id' => $request->input('order_id'),
                        'content' => $paymentResponse->content,
                        'content_type' => $paymentResponse->contentType,
                        'amount' => $paymentRequest->amount,
                    ]);
            } else if ($paymentResponse->success) {
                return redirect($paymentResponse->redirectUrl ?? '');
            }

            return redirect()->route('jinah.payment.failed', ['order_id' => $request->input('order_id')]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment processing failed: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['payment' => 'Payment processing failed. Please try again.'])->withInput();
        }
    }

    
    public function success(Request $request): View
    {
        $transactionId = session('order.order_id');
        $amount = session('order.amount');
        $content = session('order.content');
        $contentType = session('order.content_type');
        
        return view('jinah::payment.success', compact('transactionId', 'amount', 'content', 'contentType'));
    }

    public function failed(Request $request): View
    {
        $transactionId = session('order.order_id');
        $error = $request->query('error', 'Payment failed');
        
        return view('jinah::payment.failed', compact('transactionId', 'error'));
    }

    private function loadOrderPayload(string $orderId): ?array
    {
        return Cache::get('jinah_payload_' . $orderId);
    }
}