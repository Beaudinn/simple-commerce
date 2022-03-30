<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Eloquent;

use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Model extends EloquentModel
{
	protected $table = 'orders';

	protected $fillable = [
		'order_number', 'is_paid', 'customer_id', 'coupon_id', 'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 'shipping_city',
		'shipping_region', 'shipping_postal_code', 'shipping_country', 'billing_name', 'billing_address_line1', 'billing_address_line2', 'billing_city',
		'billing_region', 'billing_postal_code', 'billing_country', 'gateway', 'gateway_data', 'items_total', 'coupon_total', 'tax_total', 'shipping_total',
		'grand_total', 'paid_at',
	];

	protected $casts = [
		'is_paid' => 'boolean',
		'gateway_data' => 'json',
		'paid_at' => 'datetime',
	];

	protected $with = ['lineItems'];

	public function customer(): BelongsTo
	{
		return $this->belongsTo(SimpleCommerce::customerDriver()['model'], 'customer_id');
	}

	public function coupon(): BelongsTo
	{
		return $this->belongsTo(SimpleCommerce::couponDriver()['model'], 'coupon_id');
	}

	public function lineItems(): HasMany
	{
		return $this->hasMany(LineItemModel::class, 'order_id');
	}
}
