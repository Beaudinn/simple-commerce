<?php

namespace DoubleThreeDigital\SimpleCommerce\Products;

use DoubleThreeDigital\SimpleCommerce\Contracts\Product as Contract;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductFacade;
use DoubleThreeDigital\SimpleCommerce\Facades\TaxCategory as TaxCategoryFacade;
use DoubleThreeDigital\SimpleCommerce\Tax\Standard\TaxCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Http\Resources\API\EntryResource;
use Webhoek\Probo\Probo;

class Product implements Contract
{
	use HasData;

	public $id;
	public $price;
	public $productVariants;
	public $stock;
	public $taxCategory;
	public $data;
	public $resource;

	public function __construct()
	{
		$this->data = collect();
	}

	public function id($id = NULL)
	{
		return $this
			->fluentlyGetOrSet('id')
			->args(func_get_args());
	}

	public function price($price = NULL)
	{
		return $this
			->fluentlyGetOrSet('price')
			->args(func_get_args());
	}

	public function productVariants($productVariants = NULL)
	{
		return $this
			->fluentlyGetOrSet('productVariants')
			->args(func_get_args());
	}

	public function stock($stock = NULL)
	{
		return $this
			->fluentlyGetOrSet('stock')
			->getter(function ($value) {
				if ($this->purchasableType() === ProductType::VARIANT()) {
					return NULL;
				}

				return $value;
			})
			->setter(function ($value) {
				if ($value === NULL) {
					return NULL;
				}

				return (int)$value;
			})
			->args(func_get_args());
	}


	public function taxCategory($taxCategory = NULL)
	{
		return $this
			->fluentlyGetOrSet('taxCategory')
			->getter(function ($value) {
				if (!$value) {
					return TaxCategoryFacade::find('default');
				}

				return $value;
			})
			->setter(function ($taxCategory) {
				if ($taxCategory instanceof TaxCategory) {
					return $taxCategory;
				}

				return TaxCategoryFacade::find($taxCategory);
			})
			->args(func_get_args());
	}

	public function resource($resource = NULL)
	{
		return $this
			->fluentlyGetOrSet('resource')
			->args(func_get_args());
	}

	public function marginType($margin_type = 'global')
	{
		return $this
			->fluentlyGetOrSet('margin_type')
			->args(func_get_args());
	}

	public function margin($margin = 30)
	{
		return $this
			->fluentlyGetOrSet('margin')
			->args(func_get_args());
	}

	public function purchasableType(): ProductType
	{


		if ($this->data()['blueprint'] == 'product_probo') {
			return ProductType::PROBO();
		}

		if ($this->productVariants) {
			return ProductType::VARIANT();
		}

		return ProductType::PRODUCT();
	}



	public function variantOptions(): Collection
	{
		if (!$this->productVariants) {
			return collect();
		}

		return collect($this->productVariants()['options'])
			->map(function ($variantOption) {
				$productVariant = (new ProductVariant)
					->key($variantOption['key'])
					->product($this)
					->name($variantOption['variant'])
					->price($variantOption['price'])
					->data(Arr::except($variantOption, ['key', 'variant', 'price', 'stock']));

				if (isset($variantOption['stock'])) {
					$productVariant->stock($variantOption['stock']);
				}

				return $productVariant;
			});
	}

	public function variant(string $optionKey): ?ProductVariant
	{
		return $this->variantOptions()->filter(function ($variant) use ($optionKey) {
			return $variant->key() === $optionKey;
		})->first();
	}


	public function probo($options): ?ProductProbo
	{
		$productProbo = (new ProductProbo)
			->key($options['product_id'])
			->product($this)
			->selectedOptions($options['selected_options'])
			->data(Arr::except($options, ['key', 'variant', 'product_id', 'selected_options']));

		return $productProbo;
	}


	public function beforeSaved()
	{
		return NULL;
	}

	public function afterSaved()
	{
		return NULL;
	}

	public function save(): self
	{
		if (method_exists($this, 'beforeSaved')) {
			$this->beforeSaved();
		}

		ProductFacade::save($this);

		if (method_exists($this, 'afterSaved')) {
			$this->afterSaved();
		}

		return $this;
	}

	public function delete(): void
	{
		ProductFacade::delete($this);
	}

	public function fresh(): self
	{
		$freshProduct = ProductFacade::find($this->id());

		$this->id = $freshProduct->id;
		$this->price = $freshProduct->price;
		$this->productVariants = $freshProduct->productVariants;
		$this->stock = $freshProduct->stock;
		$this->taxCategory = $freshProduct->taxCategory;
		$this->data = $freshProduct->data;
		$this->resource = $freshProduct->resource;

		return $this;
	}

	public function toResource()
	{
		return new EntryResource($this->resource());
	}

	public function toAugmentedArray(): array
	{
		$blueprintFields = $this->resource()->blueprint()->fields()->items()->reject(function ($field) {
			return $field['handle'] === 'value';
		})->pluck('handle')->toArray();

		$augmentedData = $this->resource()->toAugmentedArray($blueprintFields);

		return array_merge(
			$this->toArray(),
			$augmentedData,
		);
	}
}
