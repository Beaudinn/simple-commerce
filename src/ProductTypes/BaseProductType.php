<?php

namespace DoubleThreeDigital\SimpleCommerce\ProductTypes;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Events\PostCheckout;
use DoubleThreeDigital\SimpleCommerce\Exceptions\GatewayDoesNotSupportPurchase;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Site;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class BaseProductType
{
	use FluentlyGetsAndSets;


	protected $handle;
	protected $blueprint;


	public $id;
	public $product;
	public $quantity;
	public $price;
	public $total;
	public $tax;
	public $purchase_price;
	public $purchase_price_incl_vat;
	public $metadata;

	public function __construct($lineItemData = [])
	{
		$this->metadata = collect();
	}

	public function id($id = null)
	{
		return $this
			->fluentlyGetOrSet('id')
			->args(func_get_args());
	}

	public function product($product = null)
	{
		return $this
			->fluentlyGetOrSet('product')
			->setter(function ($product) {
				if (! $product instanceof Product) {
					return ProductFacade::find($product);
				}

				return $product;
			})
			->args(func_get_args());
	}

	public function quantity($quantity = null)
	{
		return $this
			->fluentlyGetOrSet('quantity')
			->args(func_get_args());
	}

	public function price($price = null)
	{
		return $this
			->fluentlyGetOrSet('price')
			->args(func_get_args());
	}


	public function total($total = null)
	{
		return $this
			->fluentlyGetOrSet('total')
			->args(func_get_args());
	}

	public function tax($tax = null)
	{
		return $this
			->fluentlyGetOrSet('tax')
			->args(func_get_args());
	}

	public function initial($initial = NULL)
	{
		return $this
			->fluentlyGetOrSet('initial')
			->args(func_get_args());
	}

	public function options($options = [])
	{
		return $this
			->fluentlyGetOrSet('options')
			->args(func_get_args());
	}

	public function uploader($uploader = [])
	{
		return $this
			->fluentlyGetOrSet('uploader')
			->args(func_get_args());
	}

	public function metadata($metadata = null)
	{
		return $this
			->fluentlyGetOrSet('metadata')
			->setter(function ($value) {
				if (is_array($value)) {
					$value = collect($value);
				}

				return $value;
			})
			->args(func_get_args());
	}


	public function blueprint()
	{
		return $this->fluentlyGetOrSet('blueprint')
			->setter(function ($value) {
				if (is_string($value)) {
					return \Statamic\Facades\Blueprint::find($value);
				}

				if (is_array($value)) {
					return \Statamic\Facades\Blueprint::make()
						->setHandle($this->handle())
						->setContents($value);
				}

				return $value;
			})
			->args(func_get_args());
	}

	public function update(array $lineItemData){

		if (isset($lineItemData['initial'])) {
			$this->initial($lineItemData['initial']);
		}

		if (isset($lineItemData['options'])) {
			$this->options($lineItemData['options']);
		}

		if (isset($lineItemData['uploader'])) {
			$this->uploader($lineItemData['uploader']);
		}

		if (isset($lineItemData['design'])) {
			$this->design($lineItemData['design']);
		}

		if (isset($lineItemData['selected_options'])) {
			$this->selectedOptions($lineItemData['selected_options']);
		}

		if (isset($lineItemData['calculation_input'])) {
			$this->calculationInput($lineItemData['calculation_input']);
		}

		if (isset($lineItemData['rush_prices'])) {
			$this->rush_prices($lineItemData['rush_prices']);
		}
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
			->args(func_get_args());
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'product' => $this->product->id(),
			'type' => $this->product->purchasableType(),
			'quantity' => $this->quantity,
			'price' => $this->price,
			'total' => $this->total,
			'tax' => $this->tax,
			'metadata' => $this->metadata->toArray(),
		];
	}

}
