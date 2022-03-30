<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\Eloquent;

use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Statamic\Facades\Entry;

class LineItemModel extends EloquentModel
{
	protected $table = 'line_items';

	protected $fillable = [
		'order_id', 'product', 'variant', 'quantity', 'total', 'metadata',
	];

	protected $casts = [
		'metadata' => 'json',
	];

	//protected $appends = ['product'];

	//protected $with = ['product'];

	public function order(): BelongsTo
	{
		return $this->belongsTo(SimpleCommerce::orderDriver()['model']);
	}

	///**
	// * Determine if the user is an administrator.
	// *
	// * @return \Illuminate\Database\Eloquent\Casts\Attribute
	// */
	//protected function getProductAttribute()
	//{
	//
	//	return Product::find($this->attributes['product'])->toAugmentedArray();
	//}

}
