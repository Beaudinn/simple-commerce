<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Contracts\Calculator as Contract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order as OrderContract;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;
use DoubleThreeDigital\SimpleCommerce\Facades\Shipping;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Statamic\Facades\Site;

class OrderCalculator implements Contract
{
	/** @var \DoubleThreeDigital\SimpleCommerce\Contracts\Order */
	protected $order;

	public function calculate(OrderContract $order): array
	{

		//if ($order->isPaid()) {
		//	return $order->data()->merge([
		//		'items' => $order->lineItems()->toArray(),
		//		'grand_total' => $order->grandTotal(),
		//		'items_total' => $order->itemsTotal(),
		//		'tax_total' => $order->taxTotal(),
		//		'shipping_total' => $order->shippingTotal(),
		//		'coupon_total' => $order->couponTotal(),
		//	])->toArray();
		//}

		$this->order = $order;

		$data = [
			'grand_total' => 0,
			'rush_total' => $this->order->rushTotal(),
			'items_total' => 0,
			'upsell_total' => 0,
			'shipping_total' =>  $this->order->shippingTotal(),
			'tax_total' => 0,
			'coupon_total' => 0,
		];

		$data['items'] = $order
			->lineItems()
			->map(function ($lineItem) {
				return $lineItem->toArray();
			})
			->map(function ($lineItem) use (&$data) {
				$calculate = $this->calculateLineItem($data, $lineItem);

				$data = $calculate['data'];
				$lineItem = $calculate['lineItem'];

				return $lineItem;
			})
			->map(function ($lineItem) use (&$data) {

				$calculate = $this->calculateCouponLineItem($data, $lineItem);

				$data = $calculate['data'];
				$lineItem = $calculate['lineItem'];

				return $lineItem;
			})
			->map(function ($lineItem) use (&$data) {
				$calculate = $this->calculateLineItemTax($data, $lineItem);

				$data = $calculate['data'];
				$lineItem = $calculate['lineItem'];

				return $lineItem;
			})
			->each(function ($lineItem) use (&$data) {
				$data['coupon_total'] += $lineItem['coupon_total'] ?? 0;
				$data['items_total'] += $lineItem['total_with_discount'];
			})
			->toArray();


		$data['upsells'] = $order
			->upsells()
			->map(function ($lineItem) {
				return $lineItem->toArray();
			})
			->map(function ($lineItem) use (&$data) {

				$calculate = $this->calculateLineItem($data, $lineItem);

				$data = $calculate['data'];
				$lineItem = $calculate['lineItem'];

				return $lineItem;
			})
			->map(function ($lineItem) use (&$data) {
				$calculate = $this->calculateLineItemTax($data, $lineItem);

				$data = $calculate['data'];
				$lineItem = $calculate['lineItem'];

				return $lineItem;
			})
			->each(function ($lineItem) use (&$data) {
				$data['upsell_total'] += $lineItem['total'];
			})
			->toArray();

		if ($data['shipping_total']) {
			$data['tax_total'] += $data['shipping_total'] * (21 / 100);
		}

		if ($data['rush_total']) {
			$data['tax_total'] += $data['rush_total'] * (21 / 100);
		}

		$data['grand_total'] = $data['items_total'] + $data['upsell_total'] + $data['rush_total'] + $data['shipping_total'] +  $data['tax_total'];


		return $data;
	}

	public function calculateLineItem(array $data, array $lineItem): array
	{

		$lineItem['total'] = ($lineItem['price'] * $lineItem['quantity']);

		return [
			'data' => $data,
			'lineItem'  => $lineItem,
		];
	}

	public function calculateLineItemTax(array $data, array $lineItem): array
	{
		$taxEngine = SimpleCommerce::taxEngine();
		$taxCalculation = $taxEngine->calculate($this->order, $lineItem);

		$lineItem['tax'] = $taxCalculation->toArray();

		//var_dump($lineItem['rush_price']); die();
		//$data['tax_total'] += $lineItem['rush_price'] * (21 / 100);

		if ($taxCalculation->priceIncludesTax()) {

			if(isset($lineItem['total_with_discount'])){
				$lineItem['total_with_discount'] -= $taxCalculation->amount();
			}else{
				$lineItem['total'] -= $taxCalculation->amount();
			}

			$data['tax_total'] += $taxCalculation->amount();
		} else {
			$data['tax_total'] += $taxCalculation->amount();
		}

		return [
			'data' => $data,
			'lineItem' => $lineItem,
		];
	}

	public function calculateCouponLineItem(array $data, array $lineItem){

		if ($coupon = $this->order->coupon()) {

			$value = (int)$coupon->value();


			// Double check coupon is still valid
			//if (!$coupon->isValid($this->order)) {
			//
			//	return [
			//		'data' => $data,
			//	];
			//}

			$baseAmount = $lineItem['total'];

			// Otherwise do all the other stuff...
			if ($coupon->type() === 'percentage') {
				$lineItem['coupon_total'] = (int)($value * $baseAmount) / 100;

			}

			if ($coupon->type() === 'fixed') {
				$lineItem['coupon_total'] = (int)($baseAmount / count($data['items']) ) - (($baseAmount / count($data['items']) ) - $value);
			}


		}


		$lineItem['total_with_discount'] = $lineItem['total'] - ($lineItem['coupon_total'] ?? 0) ;
		return [
			'data' => $data,
			'lineItem' => $lineItem,
		];
	}



	public function calculateOrderCoupons(array $data): array
	{
		//Not used discount is set manualy

		return [
			'data' => $data,
		];
	}

	public function calculateOrderShipping(array $data): array
	{
		//Not used shipping is set manualy

		return [
			'data' => $data,
		];
	}

	public static function bindings(): array
	{
		return [];
	}
}
