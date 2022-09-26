<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Cart\Drivers;

use DoubleThreeDigital\SimpleCommerce\Contracts\CartDriver as CartDriverContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;

trait CartDriver
{
    protected function getCartKey(): string
    {
        return $this->resolve()->getCartKey();
    }

    public function getCart(): Order
    {
        try {
            return $this->resolve()->getCart();
        } catch (OrderNotFound $e) {
            $this->makeCart();

            return $this->getCart();
        }
    }

    public function hasCart(): bool
    {
        try {
            return $this->resolve()->hasCart();
        } catch (OrderNotFound $e) {
            return false;
        }
    }

	public function setCart($cart): Order
	{
		return $this->resolve()->setCart($cart);
	}

    protected function makeCart(): Order
    {
        return $this->resolve()->makeCart();
    }

	public function getCartCount(): int
	{
		try {

			return $this->resolve()->getCartCount();
		} catch (\Exception $e) {

			return 0;
		}
	}

    protected function getOrMakeCart(): Order
    {
        return $this->resolve()->getOrMakeCart();
    }

    protected function forgetCart()
    {
        return $this->resolve()->forgetCart();
    }

    protected function resolve()
    {
        if (request()->hasSession() && $checkoutSuccess = request()->session()->get('simple-commerce.checkout.success')) {
            // Has success expired? Use normal cart driver.
            //if ($checkoutSuccess['expiry']->isPast()) {
            //    return resolve(CartDriverContract::class);
            //}

            // Is the user on the redirect URL? If not, use normal cart driver.
            if (request()->path() !== ltrim($checkoutSuccess['url'], '/')) {
                return resolve(CartDriverContract::class);
            }

	        return resolve(PostCheckoutDriver::class, [
                'checkoutSuccess' => $checkoutSuccess,
            ]);
        }

        return resolve(CartDriverContract::class);
    }
}
