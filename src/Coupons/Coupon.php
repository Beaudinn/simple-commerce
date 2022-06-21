<?php

namespace DoubleThreeDigital\SimpleCommerce\Coupons;

use DoubleThreeDigital\SimpleCommerce\Contracts\Coupon as Contract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon as CouponFacade;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use Statamic\Http\Resources\API\EntryResource;

class Coupon implements Contract
{
    use HasData;

    public $id;
    public $code;
    public $value;
    public $type;
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

    public function type($type = null)
    {
        return $this
            ->fluentlyGetOrSet('type')
            ->args(func_get_args());
    }

    public function value($value = null)
    {
        return $this
            ->fluentlyGetOrSet('value')
            ->setter(function ($value) {
                if (is_string($value) && str_contains($value, '.')) {
                    $value = (int) str_replace('.', '', $value);
                }elseif (is_string($value)){
	                $value = (int) $value;
                }

                if ($this->type === 'percentage' && $value > 100) {
                    $value = $value / 100;
                }

                return $value;
            })
            ->args(func_get_args());
    }

    public function resource($resource = null)
    {
        return $this
            ->fluentlyGetOrSet('resource')
            ->args(func_get_args());
    }

    public function isValid(Order $order): bool
    {
        $order = OrderFacade::find($order->id());

        if ($this->value('minimum_cart_value') && $order->itemsTotal()) {
            if ($order->itemsTotal() < $this->get('minimum_cart_value')) {
                return false;
            }
        }

        if ($this->value('redeemed') && $this->get('maximum_uses') && $this->get('maximum_uses') !== null) {
            if ($this->value('redeemed') >= $this->get('maximum_uses')) {
                return false;
            }
        }

        if ($this->isProductSpecific()) {
            $couponProductsInOrder = $order->lineItems()->filter(function ($lineItem) {
                return in_array($lineItem->product()->id(), $this->get('products'));
            });

            if ($couponProductsInOrder->count() === 0) {
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
        $redeemed = $this->get('redeemed') ? $this->get('redeemed') : 0;

        $this->get('redeemed', $redeemed + 1);
        $this->save();

        return $this;
    }

    protected function isProductSpecific()
    {
        return $this->get('products')
            && collect($this->get('products'))->count() >= 1;
    }

    protected function isCustomerSpecific()
    {
        return $this->get('customers')
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

        CouponFacade::save($this);

        if (method_exists($this, 'afterSaved')) {
            $this->afterSaved();
        }

        return $this;
    }

    public function delete(): void
    {
        CouponFacade::delete($this);
    }

    public function fresh(): self
    {
        $freshCoupon = CouponFacade::find($this->id());

        $this->id = $freshCoupon->id;
        $this->code = $freshCoupon->code;
        $this->value = $freshCoupon->value;
        $this->type = $freshCoupon->type;
        $this->data = $freshCoupon->data;
        $this->resource = $freshCoupon->resource;

        return $this;
    }

    public function toResource()
    {
        return new EntryResource($this->resource());
    }

    public function toAugmentedArray($keys = null)
    {
        $blueprintFields = $this->resource()->blueprint()->fields()->items()->reject(function ($field) {
            return isset($field['import']) || $field['handle'] === 'value'; // TODO 4.0: Don't need this as coupon_value is 'the way' now
        })->pluck('handle')->toArray();

        $augmentedData = $this->resource()->toAugmentedArray($blueprintFields);


        return array_merge(
            $this->toArray(),
            $augmentedData,
        );
    }
}
