<?php

namespace DoubleThreeDigital\SimpleCommerce\ProductTypes;

use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Contracts\ProductType;
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

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'variant' => $this->variant,
		]);
	}
}
