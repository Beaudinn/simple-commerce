<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Cart\Drivers;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderAPI;

class PostCheckoutDriver extends SessionDriver
{
    protected $orderId;

    public function __construct(array $checkoutSuccess)
    {
        $this->orderId = $checkoutSuccess['order_id'];
    }

    public function getCartKey(): string
    {
        return $this->orderId;
    }

    public function hasCart(): bool
    {
        return ! empty($this->orderId);
    }

	public function getCartCount(): int
	{
		if (! $this->hasCart()) {
			return 0;
		}

		return OrderAPI::itemCount($this->getCartKey());
	}


	public function getCart(): Order
	{
		if (! $this->hasCart()) {
			return $this->makeCart();
		}

		try {
			return OrderAPI::find($this->getCartKey(), true);
		} catch (OrderNotFound $e) {
			return $this->makeCart();
		}
	}

}
