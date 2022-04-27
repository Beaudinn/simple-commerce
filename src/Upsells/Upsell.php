<?php

namespace DoubleThreeDigital\SimpleCommerce\Upsells;

use DoubleThreeDigital\SimpleCommerce\Contracts\Upsell as Contract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Facades\Upsell as UpsellFacade;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use Statamic\Http\Resources\API\EntryResource;

class Upsell implements Contract
{
    use HasData;

    public $id;
    public $code;
    public $data;
    public $resource;

    public function __construct()
    {
        $this->data = collect();
    }

    public function id($id = null)
    {
        return $this
            ->fluentlyGetOrSet('id')
            ->args(func_get_args());
    }

    public function code($id = null)
    {
        return $this
            ->fluentlyGetOrSet('code')
            ->args(func_get_args());
    }


	public function options($options = [])
	{
		return $this
			->fluentlyGetOrSet('options')
			->getter(function ($options) {
				if (empty($options) || !$options) {
					return collect([]);
				}

				return collect($options);
			})
			->args(func_get_args());
	}


    public function multiple($multiple = null)
    {
        return $this
            ->fluentlyGetOrSet('multiple')
            ->args(func_get_args());
    }

    public function resource($resource = null)
    {
        return $this
            ->fluentlyGetOrSet('resource')
            ->args(func_get_args());
    }

    public function getOptions($values){

    	if(!is_array($values)){
		    $values = [$values];
	    }

    	$options = [];
    	foreach ($values as $value) {
		    $options[] =  $this->options()->values()->get($value);
	    }

    	return collect($options);
    }



    public function setValue($value, $cart)
    {



	    $cart->updateLineItem($this->itemId, [
		     'upsells' => [
			     [
				     'id' => $id,
				     'value' => $value,
				     'price' => 2000
			     ]
		     ],
	     ]);
    }

    public function isValid(Order $order): bool
    {
        $order = OrderFacade::find($order->id());

        if ($this->has('minimum_cart_value') && $order->itemsTotal()) {
            if ($order->itemsTotal() < $this->get('minimum_cart_value')) {
                return false;
            }
        }

        if ($this->has('redeemed') && $this->has('maximum_uses') && $this->get('maximum_uses') !== null) {
            if ($this->get('redeemed') >= $this->get('maximum_uses')) {
                return false;
            }
        }

        if ($this->isProductSpecific()) {
            $upsellProductsInOrder = $order->lineItems()->filter(function ($lineItem) {
                return in_array($lineItem['product'], $this->get('products'));
            });

            if ($upsellProductsInOrder->count() === 0) {
                return false;
            }
        }

        if ($this->isCustomerSpecific()) {
            $isCustomerAllowed = collect($this->get('customers'))
                ->contains(optional($order->customer())->id());

            if (! $isCustomerAllowed) {
                return false;
            }
        }

        return true;
    }

    public function redeem(): self
    {
        $redeemed = $this->has('redeemed') ? $this->get('redeemed') : 0;

        $this->set('redeemed', $redeemed + 1);
        $this->save();

        return $this;
    }

    protected function isProductSpecific()
    {
        return $this->has('products')
            && collect($this->get('products'))->count() >= 1;
    }

    protected function isCustomerSpecific()
    {
        return $this->has('customers')
            && collect($this->get('customers'))->count() >= 1;
    }

    public function beforeSaved()
    {
        return null;
    }

    public function afterSaved()
    {
        return null;
    }

    public function save(): self
    {
        if (method_exists($this, 'beforeSaved')) {
            $this->beforeSaved();
        }

        UpsellFacade::save($this);

        if (method_exists($this, 'afterSaved')) {
            $this->afterSaved();
        }

        return $this;
    }

    public function delete(): void
    {
        UpsellFacade::delete($this);
    }

    public function fresh(): self
    {
        $freshUpsell = UpsellFacade::find($this->id());

        $this->id = $freshUpsell->id;
        $this->code = $freshUpsell->code;
        $this->multiple = $freshUpsell->multiple;
        $this->options = $freshUpsell->options;
        $this->data = $freshUpsell->data;
        $this->resource = $freshUpsell->resource;

        return $this;
    }

    public function toResource()
    {
        return new EntryResource($this->resource());
    }

    public function toAugmentedArray($keys = null)
    {
        $blueprintFields = $this->resource()->blueprint()->fields()->items()->reject(function ($field) {
            return $field['handle'] === 'value';
        })->pluck('handle')->toArray();

        $augmentedData = $this->resource()->toAugmentedArray($blueprintFields);

        return array_merge(
            $this->toArray(),
            $augmentedData,
        );
    }
}
