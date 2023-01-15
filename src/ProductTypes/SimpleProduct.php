<?php

namespace DoubleThreeDigital\SimpleCommerce\ProductTypes;

use App\Models\ShippingMethods;
use DoubleThreeDigital\SimpleCommerce\Contracts\ProductType;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;

class SimpleProduct extends BaseProductType implements ProductType
{

	static public function calculateLineItem(array $data, array $lineItem): array
	{
		$product = ProductAPI::find($lineItem['product']);

		//if (SimpleCommerce::$productPriceHook) {
		//	$productPrice = (SimpleCommerce::$productPriceHook)($this->order, $product);
		//} else {
		//	$productPrice = $product->price();
		//}
		$productPrice = $product->price();

		// Ensure we strip any decimals from price
		$productPrice = (int)str_replace('.', '', (string)$productPrice);

		$lineItem['total'] = ($productPrice * $lineItem['quantity']);


		$deliveries = collect($product->get('delivery_options'))->map(function ($delivery_option) use($lineItem){
			$deliveries =  ShippingMethods::whereIn('id', $delivery_option['shipping_methods'])->get()->map(function ($method) use($delivery_option,$lineItem){
				$methodData = $method->simpleArray();
				if(!isset($delivery_option['delivery_date'])) {
					$methodData['delivery_date'] = now()->addWeekdays(($delivery_option['production_hours'] / 24))->startOfDay()->format('Y-m-d');
				}
				return $methodData;
			});

			return  $deliveries;
			return [$key => $deliveries];
		})->flatten(1)->toArray();
		//$deliveries =  ShippingMethods::all()->mapWithKeys(function ($method){
		//	return [$method->code => $method->overwritableArray()];
		//});

		$data['deliveries'] = array_merge($deliveries, $data['deliveries']);

		return [
			'data' => $data,
			'lineItem' => $lineItem,
		];
	}

	static public function calculateRushpriceItem($order, array $data, array $lineItem, $priceResource): array
	{
		$lineItem['rush_prices'] = collect([
			now()->addDays(6)->startOfDay()->format('Y-m-d\TH:i:s.uP') => (object)[
				'delivery_date' => now()->addDays(5)->startOfDay()->format('Y-m-d\TH:i:s.uP'),
				'shipping_date' => now()->addDays(4)->startOfDay()->format('Y-m-d\TH:i:s.uP'),
				'production_hours' => '96',
				"prices_per_product" => [
					"purchase_price" => 0,
					"purchase_price_incl_vat" => 0,
					"purchase_rush_surcharge" => 0,
					"sales_price" => 0,
					"sales_price_incl_vat" => 0,
				],
			],
		]);


		return [
			'data' => $data,
			'lineItem' => $lineItem,
		];

	}

	public function rush_prices($rush_prices = [])
	{


		return $this
			->fluentlyGetOrSet('rush_prices')
			->setter(function ($value) {

				if (!$value) {
					return collect([]);
				}

				if (is_array($value)) {
					$value = collect($value);
				}


				return $value;
			})
			->getter(function ($value) {
				if (empty($value) || !$value) {
					return collect([]);
				}

				if (is_array($value)) {
					$value = collect($value);
				}

				return $value;
			})
			->args(func_get_args());
	}

}
