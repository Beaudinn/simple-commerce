<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Eloquent;

use DoubleThreeDigital\SimpleCommerce\Contracts\Calculator;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order as OrderContract;
use DoubleThreeDigital\SimpleCommerce\Events\CouponRedeemed;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPaid;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Http\Resources\GenericResource;
use DoubleThreeDigital\SimpleCommerce\Orders\Address;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class EloquentOrder implements OrderContract
{
    public $id;
    public $orderNumber;
    public $data;

    /** @var \Illuminate\Database\Eloquent\Model $model */
    protected $model;

    protected $withoutRecalculating = false;

    public function all()
    {
        return SimpleCommerce::orderDriver()['model']::all();
    }

    // Note: this method will return a Query Builder instance which
    // contains models, rather than instances of this class.
    public function query()
    {
        return SimpleCommerce::orderDriver()['model']::query();
    }

    public function find($id): OrderContract
    {
        $this->model = SimpleCommerce::orderDriver()['model']::find($id);

        if (! $this->model) {
            throw new \Exception(); // TODO: what message should we be passing in?
        }

        return $this->hydrateFromModel($this->model);
    }

    public function create(array $data = [], string $site = ''): OrderContract
    {
        if (! isset($data['order_number'])) {
            $data['order_number'] = $this->generateOrderNumber();
        }

        $this->model = SimpleCommerce::orderDriver()['model']::create($data);

        return $this->hydrateFromModel($this->model);
    }

    public function save(): OrderContract
    {
        $this->model->update(array_merge($this->data, [
            'orderNumber' => $this->orderNumber,
        ]));

        $this->model = $this->model->fresh();

        return $this->hydrateFromModel($this->model);
    }

    public function delete()
    {
        $this->model->delete();
    }

    public function toResource()
    {
        return new GenericResource($this);
    }

    public function toAugmentedArray($keys = null)
    {
        // TODO: If using Runway, we should get the model's blueprint and return it's augmented data.

        return $this->toArray();
    }

    public function id()
    {
        return $this->id;
    }

    public function title(?string $title = null)
    {
        if (! $title) {
            return $this->title;
        }

        $this->title = $title;
        return $this;
    }

    public function slug(?string $slug = null)
    {
        if (! $slug) {
            return $this->slug;
        }

        $this->slug = $slug;
        return $this;
    }

    public function site($site = null)
    {
        if (! $site) {
            return '';
        }

        // $this->email = $slug;
        return $this;
    }

    public function fresh(): OrderContract
    {
        return $this->find($this->id);
    }

    public function data($data = null)
    {
        if (! $data) {
            return collect($this->data);
        }

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        $this->data = $data;
        return $this;
    }

    public function has(string $key): bool
    {
        return $this->data()->has($key);
    }

    public function get(string $key, $default = null)
    {
        return $this->data()->get($key, $default);
    }

    public function set(string $key, $value)
    {
        $this->data()->set($key, $value);

        $this->model->update([$key => $value]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data()->toArray();
    }

    public function billingAddress()
    {
        if (!$this->has('billing_address_line1')) {
            return null;
        }

        return new Address(
            $this->get('billing_name'),
            $this->get('billing_address_line1'),
            $this->get('billing_address_line2'),
            $this->get('billing_city'),
            $this->get('billing_country'),
            $this->get('billing_zip_code') ?? $this->get('billing_postal_code'),
            $this->get('billing_region')
        );
    }

    public function shippingAddress()
    {
        if (!$this->has('shipping_address_line1')) {
            return null;
        }

        return new Address(
            $this->get('shipping_name'),
            $this->get('shipping_address_line1'),
            $this->get('shipping_address_line2'),
            $this->get('shipping_city'),
            $this->get('shipping_country'),
            $this->get('shipping_zip_code') ?? $this->get('shipping_postal_code'),
            $this->get('shipping_region')
        );
    }

    public function customer($customer = null)
    {
        if ($customer !== null) {
            $this->set('customer_id', $customer);

            return $this;
        }

        if (! $this->has('customer_id') || $this->get('customer_id') === null) {
            return null;
        }

        return Customer::find($this->get('customer_id'));
    }

    public function coupon($coupon = null)
    {
        if ($coupon !== null) {
            $this->set('coupon_id', $coupon);

            return $this;
        }

        if (! $this->has('coupon_id') || $this->get('coupon_id') === null) {
            return null;
        }

        return Coupon::find($this->get('coupon_id'));
    }

    public function gateway()
    {
        return $this->has('gateway')
            ? collect(SimpleCommerce::gateways())->firstWhere('class', $this->get('gateway'))
            : null;
    }

    // TODO: refactor
    public function redeemCoupon(string $code): bool
    {
        $coupon = Coupon::findByCode($code);

        if ($coupon->isValid($this)) {
            $this->set('coupon_id', $coupon->id());
            event(new CouponRedeemed($coupon));

            return true;
        }

        return false;
    }

    public function markAsPaid(): self
    {
        $this->set('is_paid', true);
        $this->set('paid_at', now());

        event(new OrderPaid($this));

        return $this;
    }

    public function receiptUrl(): string
    {
        return URL::temporarySignedRoute('statamic.simple-commerce.receipt.show', now()->addHour(), [
            'orderId' => $this->id,
        ]);
    }

    public function recalculate(): self
    {
        $calculate = resolve(Calculator::class)->calculate($this);

        $this->data($calculate);

        $this->save();

        return $this;
    }

    public function rules(): array
    {
        // If Runway is being used, grab the validation rules for the blueprint's fields.
        return [];
    }

    public function withoutRecalculating(callable $callback)
    {
        $this->withoutRecalculating = true;

        $return = $callback();

        $this->withoutRecalculating = false;

        return $return;
    }

    public function lineItems(): Collection
    {
        return $this->model->lineItems->map(function ($lineItem) {
            return $lineItem->toArray();
        });
    }

    public function lineItem($lineItemId): array
    {
        return $this->lineItems()->firstWhere('id', $lineItemId)->toArray();
    }

    public function addLineItem(array $lineItemData): array
    {
        $lineItem = $this->model->lineItems()->create($lineItemData);

        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $lineItem->toArray();
    }

    public function updateLineItem($lineItemId, array $lineItemData): array
    {
        $lineItem = $this->lineItems()->firstWhere('id', $lineItemId);

        $lineItem->update($lineItemData);

        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $lineItem->toArray();
    }

    public function removeLineItem($lineItemId): Collection
    {
        $lineItem = $this->lineItems()->firstWhere('id', $lineItemId);

        $lineItem->delete();

        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $this->lineItems();
    }

    public function clearLineItems(): Collection
    {
        $this->lineItems()->delete();

        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $this->lineItems();
    }

    public static function bindings(): array
    {
        return [];
    }

    protected function hydrateFromModel($model): self
    {
        $this->id = $model->id;
        $this->orderNumber = $model->order_number;
        $this->data = Arr::except($model->toArray(), ['id', 'order_number']);

        return $this;
    }

    protected function generateOrderNumber()
    {
        $minimum = config('simple-commerce.minimum_order_number');
        $latestOrderNumber = $this->query()->latest('order_number');

        if ($latestOrderNumber->count() === 0) {
            return $minimum;
        }

        return $latestOrderNumber->first()->order_number + 1;
    }
}
