<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class LineItem
{
    use FluentlyGetsAndSets;

    public $id;
    public $product;
    public $variant;
    public $quantity;
    public $total;
    public $tax;
    public $purchase_price;
	public $purchase_price_incl_vat;
    public $initial;
	public $options;
	public $uploader;
	public $rush_prices;
    public $metadata;

    public function __construct()
    {
        $this->metadata = collect();
        $this->rush_prices = collect();
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

    public function variant($variant = null)
    {
        return $this
            ->fluentlyGetOrSet('variant')
            ->args(func_get_args());
    }

    public function quantity($quantity = null)
    {
        return $this
            ->fluentlyGetOrSet('quantity')
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

    public function initial($initial = null)
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

    public function rush_prices($rush_prices = [])
    {
        return $this
            ->fluentlyGetOrSet('rush_prices')
            ->setter(function ($value) {

	            if (!$value) {
		           return  collect([]);
	            }

                if (is_array($value)) {
                    $value = collect($value);
                }


                return $value;
            })
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
	        'type' => $this->product->purchasableType(),
            'variant' => $this->variant,
            'quantity' => $this->quantity,
            'total' => $this->total,
            'tax' => $this->tax,
	        'initial' => $this->initial,
	        'options' => $this->options,
	        'uploader' => $this->uploader,
	        'rush_prices' => $this->rush_prices->toArray(),
            'metadata' => $this->metadata->toArray(),
        ];
    }
}
