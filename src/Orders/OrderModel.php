<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use App\Models\Customer;
use App\Models\Orderable;
use DoubleThreeDigital\SimpleCommerce\Customers\CustomerModel;
use DoubleThreeDigital\SimpleCommerce\Orders\States\OrderState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\ModelStates\HasStates;
use Statamic\Facades\Site;
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
        'data' => 'json',
        'paid_date' => 'datetime',
	    'delivery_at' => 'datetime',
    ];

    protected $appends = [
    	'title',
        'order_number',
	    'published'
    ];

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
        return $this->belongsTo(Customer::class);
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

	public function orders(){

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
