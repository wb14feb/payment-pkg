<?php
declare(strict_types=1);

namespace AnyTech\Jinah\DTOs;

class PaymentItemRequest
{
    public function __construct(
        public readonly string $name,
        public readonly int $quantity,
        public readonly float $price,
        public readonly ?float $discount = null,
        public readonly ?float $tax = null,
        public readonly ?string $sku = null,
        public readonly ?string $brand = null,
        public readonly ?string $category = null,
        public readonly ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'category' => $this->category,
            'description' => $this->description,
        ];
    }
}

