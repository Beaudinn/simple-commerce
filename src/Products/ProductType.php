<?php

namespace DoubleThreeDigital\SimpleCommerce\Products;

use Spatie\Enum\Enum;

/**
 * @method static self PRODUCT()
 * @method static self VARIANT()
 * @method static self PROBO()
 * @method static self UPSELL()
 */
class ProductType extends Enum
{

	protected static function values(): array
	{
		return [
			'PRODUCT' => 'simple',
			'VARIANT' => 'variant',
			'PROBO' => 'probo',
			'UPSELL' => 'upsell',
		];
	}
}
