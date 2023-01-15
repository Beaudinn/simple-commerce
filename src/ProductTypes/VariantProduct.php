<?php

namespace DoubleThreeDigital\SimpleCommerce\ProductTypes;

use App\Models\ShippingMethods;
use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Contracts\ProductType;
use DoubleThreeDigital\SimpleCommerce\Currency;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class VariantProduct  extends BaseProductType implements ProductType
{
	public $variant;

	public function variant($variant = null)
	{
		return $this
			->fluentlyGetOrSet('variant')
			->args(func_get_args());
	}

	static public function calculateLineItem(array $data, array $lineItem): array
	{
		$product = ProductAPI::find($lineItem['product']);

		$variant = $product->variant(
			isset($lineItem['variant']['variant']) ? $lineItem['variant']['variant'] : $lineItem['variant']
		);

		//if (SimpleCommerce::$productVariantPriceHook) {
		//	$productPrice = (SimpleCommerce::$productVariantPriceHook)($this->order, $product, $variant);
		//} else {
		//	$productPrice = $variant->price();
		//}
		$productPrice = $variant->price();

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

		return collect($this->product()->get('delivery_options'))->mapWithKeys(function ($delivery_option){

			$key = now()->addWeekdays(($delivery_option['production_hours'] / 24))->startOfDay()->format('Y-m-d');

			return [$key => [
				'delivery_date' => $key,
				'shipping_date' => $key,
				'production_hours' => $delivery_option['production_hours'],
				'prices_total' => [
					'purchase_rush_surcharge' =>  Currency::toDecimal($delivery_option['rush_price'] ?? 0)
				],
			]];
		});

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

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'variant' => $this->variant,
		]);
	}
}
