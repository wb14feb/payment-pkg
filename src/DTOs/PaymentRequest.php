<?php
declare(strict_types=1);

namespace AnyTech\Jinah\DTOs;

class PaymentRequest
{   
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $description,
        public readonly string $currency = 'IDR',
        public readonly ?string $customerName = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly ?float $discount = null,
        public readonly ?float $tax = null,
        public readonly ?float $adminFee = null,
        public readonly ?float $adminFeePercentage = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        /** @var PaymentItemRequest[] */
        public readonly array $items = [],
    ) {}

    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'amount' => $this->amount,
            'description' => $this->description,
            'currency' => $this->currency,
            'customerName' => $this->customerName,
            'customerEmail' => $this->customerEmail,
            'customerPhone' => $this->customerPhone,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'cancelUrl' => $this->cancelUrl,
            'adminFee' => $this->adminFee,
            'adminFeePercentage' => $this->adminFeePercentage,
            'items' => array_map(fn(PaymentItemRequest $item) => $item->toArray(), $this->items),
        ];
    }

    public function getAdminFeeValue() {
        return ($this->amount * ($this->adminFeePercentage / 100)) + ($this->adminFee ?? 0);
    }

    public function getAdminFeeName() {
        $adminFee = [];
        if ($this->adminFeePercentage) {
            $adminFee[] = $this->adminFeePercentage . '%';
        }
        if ($this->adminFee) {
            $adminFee[] = $this->adminFee;
        }
        $adminFeeText = implode('+', $adminFee);
        return "Admin Fee ($adminFeeText)";
    }

}