<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use App\Models\Address;
use App\Models\ShippingMethods;
use Carbon\Carbon;
use DoubleThreeDigital\SimpleCommerce\Contracts\Calculator as CalculatorContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Coupon as CouponContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Customer as CustomerContract;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order as Contract;
use DoubleThreeDigital\SimpleCommerce\Customers\CustomerModel;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Events\CouponRedeemed;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPaid as OrderPaidEvent;
use DoubleThreeDigital\SimpleCommerce\Events\OrderSaved;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use DoubleThreeDigital\SimpleCommerce\Http\Resources\BaseResource;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Quote;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;
use Statamic\Http\Resources\API\EntryResource;

class Order implements Contract
{

	use HasData, HasLineItems, HasUpsellItems;

	public $id;
	public $orderNumber;
	public $state;
	public $reference;
	public $packing_slip;
	public $packing_slip_path;
	public $locale;
	public $isPaid;
	public $postPayment;
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
		$this->isRefunded = false;
		$this->postPayment = false;
		$this->lineItems = collect();
		$this->upsells = collect();

		$this->grandTotal = 0;
		$this->rushTotal = 0;
		$this->itemsTotal = 0;
		$this->upsellTotal = 0;
		$this->taxTotal = 0;
		$this->shippingTotal = 0;
		$this->couponTotal = 0;

		$this->customer_ip = NULL;
		$this->delivery_at = NULL;
		$this->shipping_method = NULL;

		//$this->withoutRecalculating = $this->isQuote();

		$this->data = collect();
	}

	public function site()
	{
		return Site::get($this->locale());
	}

	public function locale($locale = NULL)
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

	public function orderNumber($orderNumber = NULL)
	{
		return $this
			->fluentlyGetOrSet('orderNumber')
			->args(func_get_args());
	}

	public function reference($reference = NULL)
	{
		return $this
			->fluentlyGetOrSet('reference')
			->args(func_get_args());
	}

	public function packingSlip($packing_slip = NULL)
	{
		return $this
			->fluentlyGetOrSet('packing_slip')
			->args(func_get_args());
	}

	public function packingSlipPath($packing_slip_path = NULL)
	{
		return $this
			->fluentlyGetOrSet('packing_slip_path')
			->args(func_get_args());
	}

	public function setPendingState(): self
	{
		$this->state(Pending::class);
		$this->save();

		return $this;
	}

	public function state($state = NULL)
	{
		return $this
			->fluentlyGetOrSet('state')
			->args(func_get_args());
	}

	public function isQuote()
	{
		return $this->state() == Quote::class;
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

	public function beforeSaved()
	{
		//
	}

	public function afterSaved()
	{
		event(new OrderSaved($this));
	}

	public function isApproved($isApproved = NULL)
	{
		return $this
			->fluentlyGetOrSet('isApproved')
			->args(func_get_args());
	}

	public function postPayment($postPayment = NULL)
	{
		return $this
			->fluentlyGetOrSet('postPayment')
			->args(func_get_args());
	}

	public function customer($customer = NULL)
	{

		return $this
			->fluentlyGetOrSet('customer')
			->setter(function ($value) {
				if (!$value) {
					return NULL;
				}

				if ($value instanceof CustomerContract) {
					return $value->id();
				}

				if ($value instanceof Customer) {
					return $value->id;
				}

				if ($value instanceof CustomerModel) {
					return $value->id;
				}

				return Customer::find($value);
			})
			->getter(function ($value) {

				if ($value instanceof \App\Models\Customer) {
					return Customer::fresh($value);
				}

				if (is_string($value) || is_numeric($value)) {
					return Customer::find($value);
				}

				return $value;
			})
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

		return NULL;
	}

	public function gateway($gateway = NULL)
	{
		return $this
			->fluentlyGetOrSet('gateway')
			->args(func_get_args());
	}

	public function rushprices()
	{
		$prices  = $this->lineItems()->map(function ($item) {
			//var_dump($item->rush_prices()->count());
			return array_values($item->rush_prices()->toArray());
		})->flatten(1);



		$rush_prices = $prices->groupBy(function ($rush, $key) {
			return Carbon::parse($rush['delivery_date'])->format('Y-m-d');
		})->sortBy(function ($rush, $key) {
			return $key;
		});


		$rush_prices = $rush_prices->filter(function ($item, $delivery_date){
			//$proboItemCount = $this->lineItems()->filter(function ($lineItem){
			//	return $lineItem->product && $lineItem->product->purchasableType() == ProductType::PROBO();
			//})->count();
			//return count($item) == (count($this->lineItems()) - ($proboItemCount -1));
			//var_dump( count($item).'--'.count($this->lineItems()));
			return count($item) == count($this->lineItems());
		});


		if(count($rush_prices)) {
			$extra = $rush_prices->last()->last();

			//Add three more optional delivery dates
			$nextDate = Carbon::parse($extra['delivery_date'])->addWeekday();
			for ($x = 0; $x <= 1; $x++) {
				$extra['delivery_date'] = $nextDate->format('Y-m-d');
				$rush_prices->put($extra['delivery_date'], collect([$extra]));
				$nextDate->addWeekday();
			}
		}



		$rush_prices = $rush_prices->map(function ($prices) {

			$total = $prices->sum(function ($price) {

				return $price['prices_total']['purchase_rush_surcharge'] ?? 0;
			});

			$deliver_date = Carbon::parse($prices->first()['delivery_date']);
			if ($deliver_date->isTomorrow()) {
				$delivery_date_formatted = ucfirst($deliver_date->translatedFormat('\M\o\r\g\e\n d  F'));;// . ' - ' . Date::now()->hour . ' --- ' . $hours;
			} else {
				$delivery_date_formatted = ucfirst($deliver_date->translatedFormat('l d  F'));// . ' - ' . Date::now()->hour . ' --- ' . $hours;
			}

			$rush_margin = Site::current()->attributes()['rush_margin']; // 10
			if(!$total){
				$rush_price = 0;
			}else{
				$rush_price = (float)  number_format((float)$total + (($total / 100) * $rush_margin), 2, '.', '');
			}

			return (object)[
				'delivery_date_formatted' => $delivery_date_formatted,
				'delivery_date' => Carbon::parse($prices->first()['delivery_date']),
				'shipping_date' => Carbon::parse($prices->first()['shipping_date']),
				'production_hours' => $prices->first()['production_hours'],
				'price' => floatval($rush_price),
				'product_count' => count($prices),
			];
		});

		return $rush_prices;
	}

	public function billingAddress(): ?Address
	{
		return Address::make()->fill([
			'company_name' => $this->get('billing_company_name'),
			'first_name' => $this->get('billing_first_name'),
			'last_name' => $this->get('billing_last_name'),
			'street' => $this->get('billing_street'),
			'house_number' => $this->get('billing_house_number'),
			'addition' => $this->get('billing_addition'),
			'postal_code' => $this->get('billing_postal_code'),
			'city' => $this->get('billing_city'),
			'phone' => $this->get('billing_phone'),
			'email' => $this->get('billing_email'),
			'country' => $this->get('billing_country'),
		]);
	}

	public function shippingAddress(): ?Address
	{
		return Address::make()->fill([
			'company_name' => $this->get('shipping_company_name'),
			'first_name' => $this->get('shipping_first_name'),
			'last_name' => $this->get('shipping_last_name'),
			'street' => $this->get('shipping_street'),
			'house_number' => $this->get('shipping_house_number'),
			'addition' => $this->get('shipping_addition'),
			'postal_code' => $this->get('shipping_postal_code'),
			'city' => $this->get('shipping_city'),
			'phone' => $this->get('shipping_phone'),
			'email' => $this->get('shipping_email'),
			'country' => $this->get('shipping_country'),
		]);
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

	public function coupon($coupon = NULL)
	{
		return $this
			->fluentlyGetOrSet('coupon')
			->setter(function ($value) {
				if (!$value) {
					return NULL;
				}

				if ($value instanceof CouponContract) {
					return $value->id();
				}

				return Coupon::fresh($value);
			})
			->args(func_get_args());
	}

	public function getDeliveryAt($value = NULL)
	{

		if (!$value)
			$value = $this->get('delivery_at');

		if (!$value) {
			return NULL;
		}

		if ($value instanceof Carbon) {
			return $value->format('Y-m-d');
		}

		return Carbon::parse($value)->format('Y-m-d');
	}

	public function getShipping($shippingMethod = NULL)
	{

		if (!$shippingMethod)
			$shippingMethod = $this->get('shipping_method');

		return collect($this->getDeliveries($this->get('delivery_at')))->first(function ($delivery) use ($shippingMethod) {
			return $delivery->shipping_method_api_code == $shippingMethod;
		});
	}

	public function getDeliveries($date)
	{
		$shipping_methods = Cache::rememberForever('swhippcssdindg_methods', function () {
			return ShippingMethods::all()->mapWithKeys(function ($method){
				return [$method->code => $method->overwritableArray()];
			});
		});

		if ($date instanceof Carbon) {
			$date = $date->format('Y-m-d');
		}

		$deliveriesOriginal = collect($this->get('deliveries', []))->groupBy(function($item, $key){
			return $item["delivery_date"]."-".$item["shipping_method_api_code"];
		})->filter(function ($item){
			$proboItemCount = $this->lineItems()->filter(function ($lineItem){
				return $lineItem->product && $lineItem->product->purchasableType() == ProductType::PROBO();
			})->count();

			if($proboItemCount){
				return count($item) == (count($this->lineItems()) - ($proboItemCount -1));
			}else{
				return count($item) == (count($this->lineItems()));
			}

		});

		if(!count($deliveriesOriginal)){

			return  [];
		}



		$deliveriesOriginal = $deliveriesOriginal->flatten(1)->groupBy(function ($rush, $key) {

			return Carbon::parse($rush['delivery_date'])->format('Y-m-d');
		})->sortBy(function ($rush, $key) {
			return $key;
		});

		//Add three more optional delivery dates

		$extraDeliveries = $deliveriesOriginal->filter(function ($date, $key){
			return !Carbon::parse($key)->isWeekend();
		})->last();

		$delivery_date = $extraDeliveries->first()['delivery_date'];
		for ($x = 0; $x <= 1; $x++) {
			$nextDate = Carbon::parse($delivery_date)->addWeekday();
			$delivery_date = $nextDate->format('Y-m-d');
			$deliveriesOriginal->put($delivery_date, $extraDeliveries);
			//var_dump($extra['delivery_date'], $deliveriesOriginal); die();
		}


		if (!isset($deliveriesOriginal[$date])) {

			$this->set('delivery_at', null);
			return [];

			//get custom shipping prijse from probo
			return collect($shipping_methods)->map(function ($method){

				return (object) $method;
			});
		}



		$deliveries = collect();
		foreach ($deliveriesOriginal[$date] as $array) {
			$array['prices'] = (object)$array['prices'];
			$array = (object)$array;


			$overwrite = [];
			$array->prices->sales_price = $array->prices->purchase_price;

			var_dump($array->shipping_method_api_code); die();
			if (isset($shipping_methods[$array->shipping_method_api_code])){
				$overwrite = $shipping_methods[$array->shipping_method_api_code];
				$array->prices->sales_price = (float)$array->prices->purchase_price + (float)$overwrite['margin'];
			}

			$deliveries->push(array_merge((array)$array, (array)$overwrite));
		}



		//Only show delivery options thats is available for all cart items
		//$deliveries = $deliveries->groupBy('shipping_method_api_code')->filter(function ($item){
		//	return count($item) == count($this->lineItems());
		//});

		$deliveries = $deliveries->groupBy('shipping_method_api_code')->map(function ($delivery){
			return (object) $delivery->sortBy('prices.sales_price')->last();
		})->values();
		//echo json_encode($deliveries, true); die();
		//$deliveries = collect($deliveries)->map(function ($delivery){
		//	return (object) $delivery;
		//});

		//echo json_encode($deliveries, true); die();
		return $deliveries;
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

	public function isPaid($isPaid = NULL)
	{
		return $this
			->fluentlyGetOrSet('isPaid')
			->args(func_get_args());
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
		$this->set('delivery_at', $date);

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}
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

	public function deliveries($deliveries = NULL)
	{

		//return $this->get('deliveries');
		return $this
			->fluentlyGetOrSet('deliveries')
			->args(func_get_args());
	}

	public function setShippingMethod(string|null $shipping_method)
	{


		$this->set('shipping_method', $shipping_method);

		if (!$this->withoutRecalculating) {
			$this->recalculate();
		}
	}

	public function collection(): string
	{
		return SimpleCommerce::orderDriver()['collection'];
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

	public function delete(): void
	{
		OrderFacade::delete($this);
	}

	public function fresh(): self
	{
		$freshOrder = OrderFacade::find($this->id(), true);

		$this->id = $freshOrder->id;
		$this->locale = $freshOrder->locale;
		$this->state = $freshOrder->state;
		$this->reference = $freshOrder->reference;
		$this->packing_slip = $freshOrder->packing_slip;
		$this->packing_slip_path = $freshOrder->packing_slip_path;
		$this->postPayment = $freshOrder->postPayment;
		$this->isPaid = $freshOrder->isPaid;
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

	public function id($id = NULL)
	{
		return $this
			->fluentlyGetOrSet('id')
			->args(func_get_args());
	}

	public function toResource()
	{
		if (isset(SimpleCommerce::orderDriver()['collection'])) {
			return new EntryResource($this->resource());
		}

		return new BaseResource($this);
	}

	public function resource($resource = NULL)
	{
		return $this
			->fluentlyGetOrSet('resource')
			->args(func_get_args());
	}

	public function toAugmentedArray($keys = NULL): array
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

			$augmentedData = $resource->augment($this->resource());

			return array_merge(
				$this->toArray(),
				$augmentedData,
			);
		}

		return [];
	}

	public function toArray(): array
	{
		$toArray = $this->data->toArray();

		$toArray['id'] = $this->id();
		$toArray['is_quote'] = $this->isQuote();

		return $toArray;
	}

}

