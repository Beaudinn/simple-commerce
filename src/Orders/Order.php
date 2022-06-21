<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use App\Models\Address;
use App\Models\ShippingMethods;
use Carbon\Carbon;
use DoubleThreeDigital\SimpleCommerce\Contracts\Calculator as CalculatorContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Coupon as CouponContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Customer as CustomerContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order as Contract;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Events\CouponRedeemed;
use DoubleThreeDigital\SimpleCommerce\Events\OrderApproved as OrderApprovedEvent;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPaid as OrderPaidEvent;
use DoubleThreeDigital\SimpleCommerce\Events\OrderSaved;
use DoubleThreeDigital\SimpleCommerce\Events\OrderShipped as OrderShippedEvent;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use DoubleThreeDigital\SimpleCommerce\Http\Resources\BaseResource;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;
use Statamic\Http\Resources\API\EntryResource;

class Order implements Contract
{

	use HasData, HasLineItems, UpsellItems;

	public $id;
	public $orderNumber;
	public $locale;
	public $isPaid;
	public $isShipped;
	public $isRefunded;
	public $lineItems;
	public $upsells;
	public $grandTotal;
	public $rushTotal;
	public $itemsTotal;
	public $upsellTotal;
	public $taxTotal;
	public $shippingTotal;
	public $couponTotal;
	public $customer;
	public $coupon;
	public $gateway;
	public $data;
	public $deliveries;
	public $delivery_at;
	public $shipping_method;
	public $resource;

	protected $withoutRecalculating = false;

	public function __construct()
	{
		$this->isPaid = false;
		$this->locale = Site::current()->handle();
		$this->isShipped = false;
		$this->isRefunded = false;
		$this->lineItems = collect();
		$this->upsells = collect();

		$this->grandTotal = 0;
		$this->rushTotal = 0;
		$this->itemsTotal = 0;
		$this->upsellTotal = 0;
		$this->taxTotal = 0;
		$this->shippingTotal = 0;
		$this->couponTotal = 0;

		$this->delivery_at = NULL;
		$this->shipping_method = NULL;

		$this->data = collect();
	}

	public function id($id = NULL)
	{
		return $this
			->fluentlyGetOrSet('id')
			->args(func_get_args());
	}


	public function locale($locale = null)
	{
		return $this
			->fluentlyGetOrSet('locale')
			->setter(function ($locale) {
				return $locale instanceof \Statamic\Sites\Site ? $locale->handle() : $locale;
			})
			->getter(function ($locale) {

				return $locale ?? Site::current()->handle();
			})
			->args(func_get_args());
	}

	public function site()
	{
		return Site::get($this->locale());
	}
	

	public function orderNumber($orderNumber = NULL)
	{
		return $this
			->fluentlyGetOrSet('orderNumber')
			->args(func_get_args());
	}

	public function isApproved($isApproved = NULL)
	{
		return $this
			->fluentlyGetOrSet('isApproved')
			->args(func_get_args());
	}

	public function isPaid($isPaid = NULL)
	{
		return $this
			->fluentlyGetOrSet('isPaid')
			->args(func_get_args());
	}

	public function isShipped($isShipped = NULL)
	{
		return $this
			->fluentlyGetOrSet('isShipped')
			->args(func_get_args());
	}

	public function isRefunded($isRefunded = NULL)
	{
		return $this
			->fluentlyGetOrSet('isRefunded')
			->args(func_get_args());
	}

	public function grandTotal($grandTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('grandTotal')
			->args(func_get_args());
	}

	public function rushTotal($grandTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('rushTotal')
			->args(func_get_args());
	}

	public function itemsTotal($itemsTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('itemsTotal')
			->args(func_get_args());
	}

	public function upsellTotal($upsellTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('upsellTotal')
			->args(func_get_args());
	}

	public function taxTotal($taxTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('taxTotal')
			->args(func_get_args());
	}

	public function shippingTotal($shippingTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('shippingTotal')
			->args(func_get_args());
	}

	public function couponTotal($couponTotal = NULL)
	{
		return $this
			->fluentlyGetOrSet('couponTotal')
			->args(func_get_args());
	}

	public function customer($customer = null)
	{
		return $this
			->fluentlyGetOrSet('customer')
			->setter(function ($value) {
				if (! $value) {
					return null;
				}

				if ($value instanceof CustomerContract) {
					return $value->id();
				}

				return Customer::find($value);
			})
			->args(func_get_args());
	}

	public function coupon($coupon = null)
	{
		return $this
			->fluentlyGetOrSet('coupon')
			->setter(function ($value) {
				if (! $value) {
					return null;
				}

				if ($value instanceof CouponContract) {
					return $value->id();
				}

				return Coupon::find($value);
			})
			->args(func_get_args());
	}

	//public function deliveryAt($delivery_at = NULL)
	//{
	//	return $this
	//		->fluentlyGetOrSet('delivery_at')
	//		->getter(function ($value) {
	//
	//			if ($value instanceof Carbon) {
	//				return $value->format('Y-m-d');
	//			}
	//
	//			return Carbon::parse($value)->format('Y-m-d');
	//		})
	//		->args(func_get_args());
	//}


	public function gateway($gateway = NULL)
	{
		return $this
			->fluentlyGetOrSet('gateway')
			->args(func_get_args());
	}

	public function currentGateway(): ?array
	{
		if (is_string($this->gateway())) {
			return collect(SimpleCommerce::gateways())->firstWhere('class', $this->gateway());
		}

		if (is_array($this->gateway())) {
			return collect(SimpleCommerce::gateways())->firstWhere('class', $this->gateway()['use']);
		}

		return null;
	}


	public function rushprices()
	{
		$rush_prices = $this->lineItems()->map(function ($item) {

			return $item->rush_prices ?? [];
		})->flatten(1)->groupBy(function ($rush) {
			return Carbon::parse($rush['delivery_date'])->format('Y-m-d');
		})->sortBy(function ($rush, $key) {
			return $key;
		});

		$rush_prices = $rush_prices->map(function ($prices) {

			$total = $prices->sum(function ($price) {

				return $price['prices_per_product']['purchase_rush_surcharge'] ?? 0;
			});
			$deliver_date = Carbon::parse($prices->first()['delivery_date']);
			if ($deliver_date->isTomorrow()) {
				$delivery_date_formatted = ucfirst($deliver_date->translatedFormat('\M\o\r\g\e\n d  F'));;// . ' - ' . Date::now()->hour . ' --- ' . $hours;
			} else {
				$delivery_date_formatted = ucfirst($deliver_date->translatedFormat('l d  F'));// . ' - ' . Date::now()->hour . ' --- ' . $hours;
			}

			return (object)[
				'delivery_date_formatted' => $delivery_date_formatted,
				'delivery_date' => Carbon::parse($prices->first()['delivery_date']),
				'shipping_date' => Carbon::parse($prices->first()['shipping_date']),
				'production_hours' => $prices->first()['production_hours'],
				'price' => floatval($total),
				'product_count' => count($prices),
			];
		});

		return $rush_prices;
	}


    public function resource($resource = null)
    {
        return $this
            ->fluentlyGetOrSet('resource')
            ->args(func_get_args());
    }

    public function billingAddress(): ?Address
    {
	    if (! $this->has('billing_address_id')) {
		    return null;
	    }

	    return  Address::find($this->get('billing_address_id'));
    }

    public function shippingAddress(): ?Address
    {
        if (! $this->has('shipping_address_id')) {
            return null;
        }

	    return  Address::find($this->get('shipping_address_id'));
    }

	public function redeemCoupon(string $code): bool
	{
		$coupon = Coupon::findByCode($code);

		if ($coupon->isValid($this)) {
			$this->coupon($coupon);
			$this->save();

			event(new CouponRedeemed($coupon));

			return true;
		}

		return false;
	}


	public function deliveries($deliveries = NULL)
	{

		//return $this->get('deliveries');
		return $this
			->fluentlyGetOrSet('deliveries')
			->args(func_get_args());
	}


	public function getDeliveries($date)
	{

		if($date instanceof Carbon){
			$date = $date->format('Y-m-d');
		}

		if (!isset($this->get('deliveries', [])[$date])) {

			//get custom shipping prijse from probo
			return [];
		}

		$deliveries = [];
		foreach ($this->get('deliveries', [])[$date] as $array) {
			$array['prices'] = (object)$array['prices'];
			$array = (object)$array;

			$overwrite = [];
			$array->prices->sales_price = $array->prices->purchase_price;

			if ($method = ShippingMethods::where('code', $array->shipping_method_api_code)->first()) {
				$overwrite = $method->overwritableArray();
				$array->prices->sales_price = (float)$array->prices->purchase_price + (float)$overwrite['margin'];
			}


			$deliveries[] = (object)array_merge((array)$array, (array)$overwrite);


		}
		return $deliveries;
	}

	public function getShipping($shippingMethod = NULL)
	{

		if (!$shippingMethod)
			$shippingMethod = $this->get('shipping_method');

		return collect($this->getDeliveries($this->get('delivery_at')))->first(function ($delivery) use ($shippingMethod) {
			return $delivery->shipping_method_api_code == $shippingMethod;
		});
	}



	public function markAsPaid(): self
	{
		$this->isPaid(true);

		$this->merge([
			'paid_date' => now()->format('Y-m-d H:i'),
		]);

		$this->save();

		event(new OrderPaidEvent($this));

		return $this;
	}


	public function refund($refundData): self
	{
		$this->isRefunded(true);

		if (is_string($this->gateway())) {
			$data = [
				'use' => $this->gateway(),
				'refund' => $refundData,
			];
		} elseif (is_array($this->gateway())) {
			$data = array_merge($this->gateway(), [
				'refund' => $refundData,
			]);
		}

		$this->gateway($data);

		return $this;
	}

	public function setDeliveryAt(string $date)
	{
		$this->set('delivery_at',$date);

		$this->save();

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}
	}

	public function setShippingMethod(string|null $shipping_method)
	{


		$this->set('shipping_method', $shipping_method);

		$this->save();

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}
	}


	public function collection(): string
	{
		return SimpleCommerce::orderDriver()['collection'];
	}

	public function recalculate(): self
	{
		$calculate = resolve(CalculatorContract::class)->calculate($this);


		$this->lineItems($calculate['items']);
		$this->upsells($calculate['upsells']);

		$this->grandTotal($calculate['grand_total']);
		$this->rushTotal($calculate['rush_total']);
		$this->itemsTotal($calculate['items_total']);
		$this->upsellTotal($calculate['upsell_total']);
		$this->taxTotal($calculate['tax_total']);
		$this->shippingTotal($calculate['shipping_total']);
		$this->couponTotal($calculate['coupon_total']);
		$this->deliveries($calculate['deliveries']);


		$this->merge(Arr::except($calculate, 'items'));

		$this->save();

		return $this;
	}

	public function recalculateBase(): self
	{
		$calculate = resolve(\DoubleThreeDigital\SimpleCommerce\Orders\OrderCalculator::class)->calculate($this);


		$this->lineItems($calculate['items']);
		$this->upsells($calculate['upsells']);

		$this->grandTotal($calculate['grand_total']);
		$this->rushTotal($calculate['rush_total']);
		$this->itemsTotal($calculate['items_total']);
		$this->upsellTotal($calculate['upsell_total']);
		$this->taxTotal($calculate['tax_total']);
		$this->shippingTotal($calculate['shipping_total']);
		$this->couponTotal($calculate['coupon_total']);


		$this->merge(Arr::except($calculate, 'items'));

		$this->save();

		return $this;
	}



	public function withoutRecalculating(callable $callback)
	{
		$this->withoutRecalculating = true;

		$return = $callback();

		$this->withoutRecalculating = false;

		return $return;
	}

	public function beforeSaved()
	{
		//
	}

	public function afterSaved()
	{
		event(new OrderSaved($this));
	}

	public function save(): self
	{
		if (method_exists($this, 'beforeSaved')) {
			$this->beforeSaved();
		}

		OrderFacade::save($this);

		if (method_exists($this, 'afterSaved')) {
			$this->afterSaved();
		}

		return $this;
	}

	public function delete(): void
	{
		OrderFacade::delete($this);
	}

	public function fresh(): self
	{
		$freshOrder = OrderFacade::find($this->id());

		$this->id = $freshOrder->id;
		$this->locale = $freshOrder->locale;
		$this->isPaid = $freshOrder->isPaid;
		$this->isShipped = $freshOrder->isShipped;
		$this->lineItems = $freshOrder->lineItems;
		$this->upsells = $freshOrder->upsells;
		$this->rushTotal = $freshOrder->rushTotal;
		$this->grandTotal = $freshOrder->grandTotal;
		$this->itemsTotal = $freshOrder->itemsTotal;
		$this->upsellTotal = $freshOrder->upsellTotal;
		$this->taxTotal = $freshOrder->taxTotal;
		$this->shippingTotal = $freshOrder->shippingTotal;
		$this->couponTotal = $freshOrder->couponTotal;
		$this->customer = $freshOrder->customer;
		$this->coupon = $freshOrder->coupon;
		$this->gateway = $freshOrder->gateway;
		$this->data = $freshOrder->data;
		$this->deliveries = $freshOrder->deliveries;
		$this->delivery_at = $freshOrder->delivery_at;
		$this->shipping_method = $freshOrder->shipping_method;
		$this->resource = $freshOrder->resource;

		return $this;
	}

	public function toArray(): array
	{
		$toArray = $this->data->toArray();

		$toArray['id'] = $this->id();

		return $toArray;
	}



	public function toResource()
	{
		if (isset(SimpleCommerce::orderDriver()['collection'])) {
			return new EntryResource($this->resource());
		}

		return new BaseResource($this);
	}

	public function toAugmentedArray($keys = null): array
	{
		if ($this->resource() instanceof Entry) {
			$blueprintFields = $this->resource()->blueprint()->fields()->items()->reject(function ($field) {
				return isset($field['import']) || $field['handle'] === 'value';
			})->pluck('handle')->toArray();

			$augmentedData = $this->resource()->toAugmentedArray($blueprintFields);

			return array_merge(
				$this->toArray(),
				$augmentedData,
			);
		}

		if ($this->resource() instanceof Model) {
			$resource = \DoubleThreeDigital\Runway\Runway::findResourceByModel($this->resource());

			return $resource->augment($this->resource());
		}

		return [];
	}

}

