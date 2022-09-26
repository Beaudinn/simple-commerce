<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers;

use DoubleThreeDigital\SimpleCommerce\Exceptions\GatewayCallbackMethodDoesNotExist;
use DoubleThreeDigital\SimpleCommerce\Exceptions\GatewayDoesNotExist;
use DoubleThreeDigital\SimpleCommerce\Facades\Gateway;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\Cart\Drivers\CartDriver;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Http\Request;

class GatewayCallbackController extends BaseActionController
{
	use CartDriver;

	public function index(Request $request, $gateway)
	{
		var_dump(1);


		if ($request->has('_order_id')) {

			$order = Order::find($request->get('_order_id'), true);
		} else {
			$order = $this->getCart();
		}
		$gatewayName = $gateway;

		$gateway = collect(SimpleCommerce::gateways())
			->where('handle', $gateway)
			->first();

		if (!$gateway) {
			throw new GatewayDoesNotExist("Gateway [{$gatewayName}] does not exist.");
		}
		var_dump(3);
		try {
			$callbackSuccess = Gateway::use($gateway['class'])->callback($request);
		} catch (GatewayCallbackMethodDoesNotExist $e) {
			$callbackSuccess = $order->isPaid() === true;
		}
		var_dump(4);
		if (!$callbackSuccess) {
			return $this->withErrors($request, "Order [{$order->orderNumber()}] has not been marked as paid yet.");
		}


		$this->forgetCart();
		var_dump(5);

		$ecommerceDataLayer = [
			"transaction_id" => $order->id(),
			"value" =>  $order->grandTotal(),
			"tax" => $order->taxTotal(),
			"shipping" => $order->shippingTotal(),
			"currency" => "EUR",
			//"coupon" => "SUMMER_SALE",
		];
		$ecommerceDataLayer['items'] = $order->lineItems()->map(function ($lineItem, $index){
			return [
				"item_id" => $lineItem->product()->id(),
				"item_name" => $lineItem->product()->get('title'),
				"currency" => "EUR",
				"index" => $index,
				"price" => $lineItem->price(),
				"quantity" => $lineItem->quantity()
			];
		})->toArray();

		var_dump(6);
		return $this->withSuccess($request, [
			'success' => __('simple-commerce.messages.checkout_complete'),
			'cart' => $request->wantsJson()
				? $order->toResource()
				: $order->toAugmentedArray(),
			'datalayer' => [
				"event" => "purchase",
				"ecommerce" => $ecommerceDataLayer
			],
			'is_checkout_request' => true,
		]);
	}
}
