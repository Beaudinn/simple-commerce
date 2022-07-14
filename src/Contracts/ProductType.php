<?php

namespace DoubleThreeDigital\SimpleCommerce\Contracts;

use DoubleThreeDigital\SimpleCommerce\Gateways\Prepare;
use DoubleThreeDigital\SimpleCommerce\Gateways\Purchase;
use DoubleThreeDigital\SimpleCommerce\Gateways\Response;
use Illuminate\Http\Request;

interface ProductType
{
    //public function name(): string;

	static public function calculateLineItem(array $data, array $lineItem): array;

	static public function calculateRushpriceItem($order,  array $data, array $lineItem, $priceResource): array;
}
