<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use Composer\Package\Loader\ValidatingArrayLoader;
use Doctrine\DBAL\Schema\Column;
use DoubleThreeDigital\SimpleCommerce\Contracts\Coupon as CouponContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Customer as CustomerContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Contracts\OrderRepository as RepositoryContract;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Support\Facades\Schema;

class EloquentOrderRepository implements RepositoryContract
{
	protected $model;

	protected $knownColumns = [
		'id', 'state', 'is_paid', 'is_shipped', 'reference', 'is_refunded', 'items', 'upsells', 'grand_total', 'items_total', 'tax_total',
		'shipping_total', 'coupon_total', 'shipping_name', 'shipping_address', 'shipping_address_line2',
		'shipping_city', 'shipping_postal_code', 'shipping_region', 'shipping_country', 'billing_name',
		'billing_address', 'billing_address_line2', 'billing_city', 'billing_postal_code', 'billing_region',
		'billing_country', 'use_shipping_address_for_billing', 'customer_id', 'coupon', 'gateway', 'paid_date',
		'data', 'created_at', 'updated_at',
	];

	public function __construct()
	{
		$this->model = SimpleCommerce::orderDriver()['model'];
	}

	public static function bindings(): array
	{
		return [];
	}

	public function all()
	{
		return (new $this->model)->all();
	}

	public function find($id): ?Order
	{
		$model = (new $this->model)->find($id);


		if (!$model) {
			throw new OrderNotFound("Order [{$id}] could not be found.");
		}

		return app(Order::class)
			->resource($model)
			->id($model->id)
			->state($model->state)
			->locale($model->locale)
			->orderNumber($model->order_number)
			->reference($model->reference)
			->isPaid($model->is_paid)
			->postPayment($model->post_payment)
			->lineItems($model->items)
			->upsells($model->upsells)
			->grandTotal($model->grand_total)
			->rushTotal($model->rush_total)
			->itemsTotal($model->items_total)
			->taxTotal($model->tax_total)
			->shippingTotal($model->shipping_total)
			->couponTotal($model->coupon_total)
			->customer($model->customer_id)
			->coupon($model->coupon)
			->gateway($model->gateway)
			->deliveries($model->deliveries)
			->data(collect($model->data)->merge([
				'shipping_company_name' => $model->shipping_company_name,
				'shipping_first_name' => $model->shipping_first_name,
				'shipping_last_name' => $model->shipping_last_name,
				'shipping_phone' => $model->shipping_phone,
				'shipping_postal_code' => $model->shipping_postal_code,
				'shipping_house_number' => $model->shipping_house_number,
				'shipping_addition' => $model->shipping_addition,
				'shipping_street' => $model->shipping_street,
				'shipping_city' => $model->shipping_city,
				'shipping_country' => $model->shipping_country,
				'billing_company_name' => $model->billing_company_name,
				'billing_first_name' => $model->billing_first_name,
				'billing_last_name' => $model->billing_last_name,
				'billing_phone' => $model->billing_phone,
				'billing_postal_code' => $model->billing_postal_code,
				'billing_house_number' => $model->billing_house_number,
				'billing_addition' => $model->billing_addition,
				'billing_street' => $model->billing_street,
				'billing_city' => $model->billing_city,
				'billing_country' => $model->billing_country,
				'use_shipping_address_for_billing' => $model->use_shipping_address_for_billing,
				'paid_date' => $model->paid_date,
			])->merge(
					collect($this->getCustomColumns())
						->mapWithKeys(function ($columnName) use ($model) {
							return [$columnName => $model->{$columnName}];
						})
						->toArray()
				)
			);
	}

	/**
	 * Returns an array of custom columns the developer
	 * has added to the 'orders' table.
	 *
	 * @return array
	 */
	protected function getCustomColumns(): array
	{
		$tableColumns = Schema::getConnection()
			->getDoctrineSchemaManager()
			->listTableColumns((new $this->model)->getTable());

		return collect($tableColumns)
			->reject(function (Column $column) {
				return in_array($column->getName(), $this->knownColumns);
			})
			->map->getName()
			->toArray();
	}

	public function make(): Order
	{
		return app(Order::class);
	}

	public function save($order): void
	{
		$model = $order->resource();

		if (!$model) {
			$model = new $this->model();
		}


		if($order->state() && !$model->state->equals($order->state())){

			$model->state->transitionTo($order->state);
		}


		$model->locale = $order->locale();
		$model->is_paid = $order->isPaid();
		$model->order_number = $order->orderNumber();
		$model->reference = $order->reference();
		$model->post_payment = $order->postPayment();
		$model->items = $order->lineItems()->map->toArray();
		$model->upsells = $order->upsells()->map->toArray();
		$model->grand_total = $order->grandTotal();
		$model->rush_total = $order->rushTotal();
		$model->items_total = $order->itemsTotal();
		$model->upsell_total = $order->upsellTotal();
		$model->tax_total = $order->taxTotal();
		$model->shipping_total = $order->shippingTotal();
		$model->coupon_total = $order->couponTotal();
		$model->customer_id = $order->customer() instanceof CustomerContract ? $order->customer()->id() : $order->customer();
		$model->coupon = $order->coupon() instanceof CouponContract ? $order->coupon()->id() : $order->coupon();
		$model->gateway = $order->gateway();
		//$model->deliveries = $order->deliveries();

		$model->shipping_company_name = $order->get('shipping_company_name');
		$model->shipping_first_name = $order->get('shipping_first_name');
		$model->shipping_last_name = $order->get('shipping_last_name');
		$model->shipping_phone = $order->get('shipping_phone');
		$model->shipping_postal_code = $order->get('shipping_postal_code');
		$model->shipping_house_number = $order->get('shipping_house_number');
		$model->shipping_addition = $order->get('shipping_addition');
		$model->shipping_street = $order->get('shipping_street');
		$model->shipping_city = $order->get('shipping_city');
		$model->shipping_country = $order->get('shipping_country');


		$model->billing_company_name = $order->get('billing_company_name');
		$model->billing_first_name = $order->get('billing_first_name');
		$model->billing_last_name = $order->get('billing_last_name');
		$model->billing_phone = $order->get('billing_phone');
		$model->billing_postal_code = $order->get('billing_postal_code');
		$model->billing_house_number = $order->get('billing_house_number');
		$model->billing_addition = $order->get('billing_addition');
		$model->billing_street = $order->get('billing_street');
		$model->billing_city = $order->get('billing_city');
		$model->billing_country = $order->get('billing_country');
		$model->use_shipping_address_for_billing = $order->get('use_shipping_address_for_billing') ?? false;


		// If anything in the order data has it's own column, save it
		// there, rather than in the data column.
		collect($this->getCustomColumns())
			->filter(function ($columnName) use ($order) {
				return $order->has($columnName);
			})
			->each(function ($columnName) use (&$model, $order) {
				$model->{$columnName} = $order->get($columnName);
			});

		// Set the value of the data column - we take out any 'known' columns,
		// along with any custom columns.
		$model->data = $order->data()
			->except($this->knownColumns)
			->except($this->getCustomColumns());

		$model->paid_date = $order->get('paid_date');

		$model->save();
		//var_dump('--------',$order->upsells()->map->toArray(), $model->upsells);
		//
		//if( $model->upsells){
		//	var_dump('--------',$model->id);
		//	die();
		//}

		$order->id = $model->id;
		$order->state = $model->state;
		$order->orderNumber = $model->order_number;
		$order->reference = $model->reference;
		$order->shop = $model->shop;
		$order->isPaid = $model->is_paid;

		//$order->lineItems = collect($model->items);
		//$order->upsells = collect($model->upsells);

		$order->lineItems($model->items);
		$order->upsells($model->upsells);

		$order->grandTotal = $model->grand_total;
		$order->postPayment = $model->post_payment;
		$order->rushTotal = $model->rush_total;
		$order->itemsTotal = $model->items_total;
		$order->upsellTotal = $model->upsell_total;
		$order->taxTotal = $model->tax_total;
		$order->shippingTotal = $model->shipping_total;
		$order->couponTotal = $model->coupon_total;
		$order->customer = $model->customer_id ? Customer::find($model->customer_id) : NULL;
		$order->coupon = $model->coupon ? Coupon::find($model->coupon) : NULL;
		$order->delivery_at = $model->delivery_at;
		$order->shipping_method = $model->shipping_method;
		$order->gateway = $model->gateway;

		$order->data = collect($model->data)
			->merge([
				'shipping_company_name' => $model->shipping_company_name,
				'shipping_first_name' => $model->shipping_first_name,
				'shipping_last_name' => $model->shipping_last_name,
				'shipping_phone' => $model->shipping_phone,
				'shipping_postal_code' => $model->shipping_postal_code,
				'shipping_house_number' => $model->shipping_house_number,
				'shipping_addition' => $model->shipping_addition,
				'shipping_street' => $model->shipping_street,
				'shipping_city' => $model->shipping_city,
				'shipping_country' => $model->shipping_country,
				'billing_company_name' => $model->billing_company_name,
				'billing_first_name' => $model->billing_first_name,
				'billing_last_name' => $model->billing_last_name,
				'billing_phone' => $model->billing_phone,
				'billing_postal_code' => $model->billing_postal_code,
				'billing_house_number' => $model->billing_house_number,
				'billing_addition' => $model->billing_addition,
				'billing_street' => $model->billing_street,
				'billing_city' => $model->billing_city,
				'billing_country' => $model->billing_country,
				'use_shipping_address_for_billing' => $model->use_shipping_address_for_billing,
				'paid_date' => $model->paid_date,
			])
			->merge(
				collect($this->getCustomColumns())
					->mapWithKeys(function ($columnName) use ($model) {
						return [$columnName => $model->{$columnName}];
					})
					->toArray()
			);

		$order->resource = $model;
	}

	public function delete($order): void
	{
		$order->resource()->delete();
	}

}
