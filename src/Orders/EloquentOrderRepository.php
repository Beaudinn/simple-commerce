<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Contracts\OrderRepository as RepositoryContract;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;

class EloquentOrderRepository implements RepositoryContract
{
    protected $model;

    public function __construct()
    {
        $this->model = SimpleCommerce::orderDriver()['model'];
    }

    public function all()
    {
        return (new $this->model)->all();
    }

    public function find($id): ?Order
    {
        $model = (new $this->model)->find($id);

        if (! $model) {
            throw new OrderNotFound("Order [{$id}] could not be found.");
        }

        return app(Order::class)
            ->resource($model)
            ->id($model->id)
	        ->shop($model->shop)
            ->orderNumber($model->id)
            ->isPaid($model->is_paid)
            ->isShipped($model->is_shipped)
            ->isRefunded($model->is_refunded)
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
	        ->deliveryAt($model->delivery_at)
	        ->shippingMethod($model->shipping_method)
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
            ]));
    }

    public function make(): Order
    {
        return app(Order::class);
    }

    public function save($order): void
    {
        $model = $order->resource();

        if (! $model) {
            $model = new $this->model();
        }

        $model->shop = $order->shop();
        $model->is_paid = $order->isPaid();
        $model->is_shipped = $order->isShipped();
        $model->is_refunded = $order->isRefunded();
	    $model->items = $order->lineItems()->map->toArray();
	    $model->upsells = $order->upsells()->map->toArray();
        $model->grand_total = $order->grandTotal();
        $model->rush_total = $order->rushTotal();
        $model->items_total = $order->itemsTotal();
        $model->upsell_total = $order->upsellTotal();
        $model->tax_total = $order->taxTotal();
        $model->shipping_total = $order->shippingTotal();
        $model->coupon_total = $order->couponTotal();
        $model->customer_id = optional($order->customer())->id();
        $model->coupon = optional($order->coupon())->id();
        $model->gateway = $order->gateway();
        //$model->deliveries = $order->deliveries();
        $model->delivery_at = $order->deliveryAt();

        $model->shipping_method = $order->shippingMethod();

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

        // We need to do this, otherwise we'll end up duplicating data unnecessarily sometimes.
        $model->data = $order->data()->except([
            'is_paid', 'is_shipped', 'is_refunded', 'items', 'grand_total', 'rush_total', 'items_total', 'upsell_total', 'tax_total',
            'shipping_total', 'coupon_total', 'shipping_company_name', 'shipping_first_name', 'shipping_last_name', 'shipping_phone', 'shipping_postal_code', 'shipping_house_number', 'shipping_addition', 'shipping_street', 'shipping_city', 'shipping_country',
	        'use_shipping_address_for_billing', 'billing_company_name','billing_first_name','billing_last_name','billing_phone','billing_postal_code','billing_house_number','billing_addition','billing_street','billing_city','billing_country',
	        'customer_id', 'coupon', 'gateway', 'shipping_method'
        ]);

        $model->paid_date = $order->get('paid_date');
        $model->save();


        $order->id = $model->id;
        $order->orderNumber = $model->id;
        $order->shop = $model->shop;
        $order->isPaid = $model->is_paid;
        $order->isShipped = $model->is_shipped;
        $order->isRefunded = $model->is_refunded;

        //$order->lineItems = collect($model->items);
        //$order->upsells = collect($model->upsells);

        $order->grandTotal = $model->grand_total;
        $order->rushTotal = $model->rush_total;
        $order->itemsTotal = $model->items_total;
        $order->upsellTotal = $model->upsell_total;
        $order->taxTotal = $model->tax_total;
        $order->shippingTotal = $model->shipping_total;
        $order->couponTotal = $model->coupon_total;
        $order->customer = $model->customer_id ? Customer::find($model->customer_id) : null;
        $order->coupon = $model->coupon;
        $order->delivery_at = $model->delivery_at;
        $order->shipping_method = $model->shipping_method;
        $order->gateway = $model->gateway;

        $order->data = collect($model->data)->merge([
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
        ]);
    }


	/**
	 * Create an order number.
	 */
	protected function createOrderNumber(): string
	{

		$prefix = config('shop.order_number_prefix');
		$number = config('shop.order_number_range');


		if (!empty($number)) {
			do {
				$number++;

				$count =  (new $this->model)->where('order_number', $prefix . $number)->count();

			} while ($count);
		}

		return $prefix . $number;
	}

    public function delete($order): void
    {
        $order->resource()->delete();
    }

    public static function bindings(): array
    {
        return [];
    }
}
