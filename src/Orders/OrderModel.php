<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use App\Models\Customer;
use App\Models\Orderable;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Draft;
use DoubleThreeDigital\SimpleCommerce\Orders\States\OrderState;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Quote;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;
use Webhoek\P4sSupplier\SupplierOrder\SupplierOrderModel;

class OrderModel extends Model
{
	use HasFactory;
	use HasStates;

	protected $table = 'orders';

	protected $guarded = [];

	protected $casts = [
		'state' => OrderState::class,
		'is_paid' => 'boolean',
		'is_shipped' => 'boolean',
		'is_refunded' => 'boolean',
		'items' => 'json',
		'upsells' => 'json',
		'grand_total' => 'integer',
		'rush_total' => 'integer',
		'items_total' => 'integer',
		'upsell_total' => 'integer',
		'tax_total' => 'integer',
		'shipping_total' => 'integer',
		'coupon_total' => 'integer',
		'use_shipping_address_for_billing' => 'boolean',
		'gateway' => 'json',
		'invoice' => 'json',
		'data' => 'array',
		'paid_date' => 'datetime',
		'ordered_at' => 'datetime',
		'delivery_at' => 'datetime',
		'agent_client' => 'json',
		'agent_os' => 'json',
	];

	protected $appends = [
		'title',
		'order_number',
		'published',
		'profit',
	];

	protected $with = [
		'customer',
	];

	public function getProfitAttribute()
	{
		if ($this->state->equals(Draft::class)) {
			return 0;
		}

		$costs = $this->orders->filter(fn($orderItem) => $orderItem->total_purchase_price)->sum(function ($orderItem) {
			return $orderItem->total_purchase_price;
		}, 0);
		if (!$costs)
			return 0;


		return $this->grand_total - $costs;
	}

	function readOnly()
	{

		if ($this->state->equals(Draft::class) || $this->state->equals(Quote::class) || $this->state->equals(Pending::class) || $this->state->equals(Approved::class)) {
			return false;
		}

		return true;
	}

	public function scopeRunwayListing($query)
	{
		return $query->whereNot('grand_total', 0);
	}

	public function scopeRunwaySearch($query, $searchTerm)
	{
		return $query->where('order_number', 'LIKE', "%{$searchTerm}%")
			->orWhere('reference', 'LIKE', "%{$searchTerm}%")
			->orWhere('reference', 'LIKE', "%{$searchTerm}%")
			->orWhere('shipping_company_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('shipping_first_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('shipping_last_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('billing_company_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('billing_first_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('billing_last_name', 'LIKE', "%{$searchTerm}%")
			->orWhereHas('orders', function ($query) use ($searchTerm) {
				$query->where('order_number', 'LIKE', "%{$searchTerm}%");
			});
	}

	//public function scopeRunwayListing($query)
	//{
	//	return $query->where('is_paid', true);
	//}

	//public function scopeRunway($query)
	//{
	//	$query->where('site', Site::selected()->handle());
	//}

	//protected function items(): Attribute
	//{
	//    return Attribute::make(
	//	    get: fn ($value) => collect(json_decode($value ?? [], true))->filter(function ($item){
	//		    return $item['type'] !== 'UPSELL';
	//	    })->toArray()
	//    );
	//}
	//
	//protected function upsells(): Attribute
	//{
	//    return Attribute::make(
	//	    get: fn ($value,$attributes) => collect(json_decode($attributes['items'] ?? [], true))->filter(function ($item){
	//		    return $item['type'] == 'UPSELL';
	//	    })->toArray()
	//    );
	//}

	public function getTitleAttribute()
	{
		if (array_key_exists('order_number', $this->attributes)) {
			return $this->attributes['order_number'];
		}

		return "#{$this->id}";
	}

	public function getOrderNumberAttribute()
	{
		if (array_key_exists('order_number', $this->attributes) && $this->attributes['order_number']) {
			return $this->attributes['order_number'];
		}

		return "#{$this->id}";
	}


	public function getPublishedAttribute()
	{
		return $this->is_approved;
	}


	public function customer(): BelongsTo
	{
		return $this->belongsTo(Customer::class)->withoutGlobalScopes(['current_site']);
	}



	/**
	 * Get the parent order model.
	 */
	//public function order()
	//{
	//	return $this->morphMany(SupplierOrderModel::class, 'orderable');
	//}
	//
	//public function orderables()
	//{
	//	return $this->morphMany(Orderable::class, 'orderable');
	//}

	public function orders()
	{

		return $this->hasMany(SupplierOrderModel::class, 'order_id');
	}


	//public function orders()
	//{
	//	return $this->morphTo();
	//}

	/**
	 * Get the parent orderable model (Supplier1 or Supplier2).
	 */
	//public function orderables()
	//{
	//	return $this->morphedByMany(Orderable::class, 'orderable');
	//	return $this->morphMany(Orderable::class, 'orderable');
	//
	//}

}
