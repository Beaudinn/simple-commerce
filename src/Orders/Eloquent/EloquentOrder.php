<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Eloquent;

use DoubleThreeDigital\Runway\Runway;
use DoubleThreeDigital\SimpleCommerce\Contracts\Calculator;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order as OrderContract;
use DoubleThreeDigital\SimpleCommerce\Events\CouponRedeemed;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPaid;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\Http\Resources\GenericResource;
use DoubleThreeDigital\SimpleCommerce\Orders\Address;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Webhoek\Probo\Api\Resources\Price;
use Webhoek\Probo\Api\Resources\ResourceFactory;

class EloquentOrder implements OrderContract
{
	public $id;
	public $orderNumber;
	public $data;

	/** @var \Illuminate\Database\Eloquent\Model $model */
	protected $model;

	protected $withoutRecalculating = false;

	public static function bindings(): array
	{
		return [];
	}

	// Note: this method will return a Query Builder instance which
	// contains models, rather than instances of this class.

	public function all()
	{
		return SimpleCommerce::orderDriver()['model']::all();
	}

	public function create(array $data = [], string $site = ''): OrderContract
	{
		if (!isset($data['order_number'])) {
			$data['order_number'] = $this->generateOrderNumber();
		}

		$this->model = SimpleCommerce::orderDriver()['model']::create($data);

		return $this->hydrateFromModel($this->model);
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

	public function query()
	{
		return SimpleCommerce::orderDriver()['model']::query();
	}

	protected function hydrateFromModel($model): self
	{
		$this->id = $model->id;
		$this->orderNumber = $model->order_number;
		$this->data = Arr::except($model->toArray(), ['id', 'order_number']);
		$this->data['items'] = $this->lineItems();

		return $this;
	}

	public function lineItems(): Collection
	{
		return $this->model->lineItems->map(function ($lineItem) {
			$data = $lineItem->toArray();
			return $data;
		});
	}

	public function delete()
	{
		$this->model->delete();
	}

	public function toResource()
	{
		return new GenericResource($this);
	}

	public function toAugmentedArray($keys = NULL)
	{
		// TODO: If using Runway, we should get the model's blueprint and return it's augmented data.
		//$this->recalculate();

		$resource = Runway::findResourceByModel($this->model);
		$resource = Runway::findResource('model');



		$data = $resource->augment($this->model)->toAugmentedArray();
		var_dump($data); die();
		$data['items']  = collect($data['items'])->map(function ($item){
			$item['product'] =  Product::find($item['product'])->toAugmentedArray();
			return $item;
		});
		return $data;
	}

	public function toArray(): array
	{
		$data = $this->data()->toArray();
		$data['items'] = $this->lineItems();
		return $data;
	}

	public function data($data = NULL)
	{
		if (!$data) {
			return collect($this->data);
		}

		if ($data instanceof Collection) {
			$data = $data->toArray();
		}

		$this->data = $data;
		return $this;
	}

	public function id()
	{
		return $this->id;
	}

	public function title(?string $title = NULL)
	{
		if (!$title) {
			return $this->title;
		}

		$this->title = $title;
		return $this;
	}

	public function slug(?string $slug = NULL)
	{
		if (!$slug) {
			return $this->slug;
		}

		$this->slug = $slug;
		return $this;
	}

	public function site($site = NULL)
	{
		if (!$site) {
			return '';
		}

		// $this->email = $slug;
		return $this;
	}

	public function fresh(): OrderContract
	{
		return $this->find($this->id);
	}

	public function find($id): OrderContract
	{
		$this->model = SimpleCommerce::orderDriver()['model']::find($id);

		if (!$this->model) {
			throw new \Exception(); // TODO: what message should we be passing in?
		}

		return $this->hydrateFromModel($this->model);
	}

	public function billingAddress()
	{
		if (!$this->has('billing_address_line1')) {
			return NULL;
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

	public function has(string $key): bool
	{
		return $this->data()->has($key);
	}

	public function get(string $key, $default = NULL)
	{
		return $this->data()->get($key, $default);
	}

	public function shippingAddress()
	{
		if (!$this->has('shipping_address_line1')) {
			return NULL;
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

	public function customer($customer = NULL)
	{
		if ($customer !== NULL) {
			$this->set('customer_id', $customer);

			return $this;
		}

		if (!$this->has('customer_id') || $this->get('customer_id') === NULL) {
			return NULL;
		}

		return Customer::find($this->get('customer_id'));
	}

	// TODO: refactor

	public function set(string $key, $value)
	{
		$this->data()->set($key, $value);

		$this->model->update([$key => $value]);

		return $this;
	}

	public function coupon($coupon = NULL)
	{
		if ($coupon !== NULL) {
			$this->set('coupon_id', $coupon);

			return $this;
		}

		if (!$this->has('coupon_id') || $this->get('coupon_id') === NULL) {
			return NULL;
		}

		return Coupon::find($this->get('coupon_id'));
	}

	public function gateway()
	{
		return $this->has('gateway')
			? collect(SimpleCommerce::gateways())->firstWhere('class', $this->get('gateway'))
			: NULL;
	}

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

	public function lineItem($lineItemId): array
	{
		return $this->lineItems()->firstWhere('id', $lineItemId)->toArray();
	}

	public function addLineItem(array $lineItemData): array
	{

		$lineItem = $this->model->lineItems()->create($lineItemData);

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}

		return $lineItem->toArray();
	}

	public function getPrices(){

		return collect($this->model->probo_prices)->mapWithKeys(function ($price){
			return [ $price['delivery_date'] => ResourceFactory::createFromApiResult($price, new Price(app('probo.api.client')))];
		});

	}

	public function recalculate(): self
	{
		$calculate = resolve(Calculator::class)->calculate($this);
		$this->data($calculate);

		$this->save();

		return $this;
	}

	public function save(): OrderContract
	{
		$this->model->update(array_merge($this->data, [
			'orderNumber' => $this->orderNumber,
		]));



		//var_dump($this->data['items']); die();
		foreach ($this->data['items'] as $item) {
			$this->model->lineItems()->where('id', $item['id'])->update(['total' => $item['total']]);
		}

		$this->model = $this->model->fresh();

		return $this->hydrateFromModel($this->model);
	}

	public function updateLineItem($lineItemId, array $lineItemData): array
	{
		$lineItem = $this->model->lineItems->find($lineItemId);

		$lineItem->update($lineItemData);

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}

		return $lineItem->toArray();
	}

	public function removeLineItem($lineItemId): Collection
	{
		$lineItem = $this->model->lineItems->find($lineItemId);

		$lineItem->delete();

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItems();
	}

	public function clearLineItems(): Collection
	{
		$this->lineItems()->delete();

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItems();
	}
}
