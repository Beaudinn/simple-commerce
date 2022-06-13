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

    protected $guarded = [];


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
