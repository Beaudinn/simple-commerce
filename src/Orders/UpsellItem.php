<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class UpsellItem
{
    use FluentlyGetsAndSets;

    public $id;
    public $product;
    public $item;
    public $quantity;
    public $price;
    public $total;
    public $tax;
    public $purchase_price;
	public $purchase_price_incl_vat;
    public $metadata;

    public function __construct()
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

	public function item($item = null)
	{
		return $this
			->fluentlyGetOrSet('item')
			->setter(function ($item) {
				if (! $item instanceof LineItem) {
					return $item;
				}

				return $item;
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product' => $this->product->id(),
	        'item' => $this->item,
            'quantity' => $this->quantity,
            'price' => $this->price,
	        'total' => $this->total,
            'tax' => $this->tax,
            'metadata' => $this->metadata->toArray(),
        ];
    }
}
