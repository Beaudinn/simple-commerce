<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Customers\CustomerModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderModel extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'is_paid', 'is_shipped', 'is_refunded', 'items', 'grand_total', 'rush_total', 'items_total', 'tax_total',
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
        'grand_total' => 'integer',
	    'rush_total' => 'integer',
        'items_total' => 'integer',
        'tax_total' => 'integer',
        'shipping_total' => 'integer',
        'coupon_total' => 'integer',
        'use_shipping_address_for_billing' => 'boolean',
        'gateway' => 'json',
        'data' => 'json',
    ];

    protected $appends = [
       // 'order_number',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class);
    }

}
