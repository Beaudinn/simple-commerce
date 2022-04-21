<?php

namespace DoubleThreeDigital\SimpleCommerce\Products;

use DoubleThreeDigital\SimpleCommerce\Contracts\Product;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class ProductProbo
{
    use HasData, FluentlyGetsAndSets;

    protected $key;
    protected $product;
    protected $name;
    protected $price;
    protected $stock;
    protected $data;

    public function key($key = null)
    {
        return $this
            ->fluentlyGetOrSet('key')
            ->args(func_get_args());
    }

    public function product($product = null)
    {
        return $this
            ->fluentlyGetOrSet('product')
            ->setter(function ($product) {
                if (! $product instanceof Product) {
                    return ProductFacade::find($product);
                }

                return $product;
            })
            ->args(func_get_args());
    }

    public function selectedOptions($selected_options = [])
    {
        return $this
            ->fluentlyGetOrSet('selected_options')
	        ->getter(function ($options) {
		        if (empty($options) || !$options) {
			        return collect([]);
		        }

		        return collect($options);
	        })
            ->args(func_get_args());
    }


	public function getSelectedOptionsFromLastResponseWithoutInitial()
	{

		return $this->selectedOptions()->filter(function ($selectedOption) {

			return $selectedOption['type_code'] !== 'cross_sell_pc' &&
				$selectedOption['type_code'] !== 'cross_sell_lm' &&
				$selectedOption['type_code'] !== 'width' &&
				$selectedOption['type_code'] !== 'height' &&
				$selectedOption['type_code'] !== 'length' &&
				$selectedOption['type_code'] !== 'amount';
		});
	}


	public function getSelectedOptionsFromLastResponseWithInitial()
	{
		return $this->selectedOptions()->filter(function ($selectedOption) {

			return $selectedOption['type_code'] === 'width' ||
				$selectedOption['type_code'] === 'height' ||
				$selectedOption['type_code'] === 'length' ||
				$selectedOption['type_code'] === 'amount';
		});

	}

	public static function getName($data)
	{
		if ($data->parent_name) {
			return $data->parent_name;
		}

		return $data->name;
	}

	public static function getValue($data)
	{
		if (!$data->value) {
			return $data->name;
		}

		if($data->unit_code){
			return $data->value . ' ' . $data->unit_code;
		}
		return $data->value;
	}

	public function getInitial()
	{
		$sizes = [];
		//x-text="getInitial().join(' x ') + ' cm';"

		$this->getSelectedOptionsFromLastResponseWithInitial()->each(function ($selectedOption) use (&$sizes) {
			if ($selectedOption['type_code'] === 'width') {
				array_push($sizes, $selectedOption['value']);
			}
			if ($selectedOption['type_code'] === 'height') {
				array_push($sizes, $selectedOption['value']);
			}
			if ($selectedOption['type_code'] === 'length') {
				array_push($sizes, $selectedOption['value']);
			}
		});

		return $sizes;
	}


}
