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
		if ($request->has('_order_id')) {
			var_dump('haaaaaaay'); die();
			$order = Order::find($request->get('_order_id'));
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

		try {
			$callbackSuccess = Gateway::use($gateway['class'])->callback($request);
		} catch (GatewayCallbackMethodDoesNotExist $e) {
			$callbackSuccess = $order->isPaid() === true;
		}

		if (!$callbackSuccess) {
			return $this->withErrors($request, "Order [{$order->get('title')}] has not been marked as paid yet.");
		}


		//$this->forgetCart();

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
