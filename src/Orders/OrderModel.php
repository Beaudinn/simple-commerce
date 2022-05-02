<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Customers\CustomerModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderModel extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'is_paid', 'is_shipped', 'is_refunded', 'items', 'upsells', 'grand_total', 'rush_total', 'items_total', 'upsell_total', 'tax_total',
        'shipping_total', 'coupon_total',
	    'shipping_company_name', 'shipping_first_name', 'shipping_last_name', 'shipping_phone', 'shipping_postal_code', 'shipping_house_number', 'shipping_addition', 'shipping_street', 'shipping_city', 'shipping_country',
	    'billing_company_name','billing_first_name','billing_last_name','billing_phone','billing_postal_code','billing_house_number','billing_addition','billing_street','billing_city','billing_country',
	    'use_shipping_address_for_billing', 'customer_id', 'coupon', 'gateway', 'data', 'delivery_at', 'shipping_method'
    ];

    protected $casts = [
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
        'data' => 'json',
        'paid_date' => 'datetime',
    ];

    protected $appends = [
       // 'order_number',
    ];



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



    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class);
    }

}
