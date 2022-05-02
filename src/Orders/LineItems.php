<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use Illuminate\Support\Collection;

trait LineItems
{

	public function proboItems(){
		return $this->lineItems()
			->filter(function ($lineItem) {
				return $lineItem->product->purchasableType() === ProductType::PROBO();
			});
	}

	public function productItems(){

		return $this->lineItems()->filter(function ($lineItem){
			return $lineItem->product->purchasableType() === ProductType::UPSELL();
		});
	}

	public function lineItems($lineItems = null)
	{
		return $this
			->fluentlyGetOrSet('lineItems')
			->setter(function ($value) {
				if ($value === null) {
					$value = collect();
				}

				if (is_array($value)) {
					$value = collect($value);
				}

				return $value->map(function ($item) {
					if ($item instanceof LineItem) {
						return $item;
					}

					if (! isset($item['id'])) {
						var_dump($item); die();
						$item['id'] = mt_rand(1000000000,9999999999);// app('stache')->generateId();
					}

					if (! isset($item['total'])) {
						$item['total'] = 0;
					}

					$lineItem = (new LineItem($item))
						->id($item['id'])
						->product($item['product'])
						->quantity($item['quantity'])
						->total($item['total']);

					if (isset($item['variant'])) {
						$lineItem->variant($item['variant']);
					}

					if (isset($item['tax'])) {
						$lineItem->tax($item['tax']);
					}

					if (isset($item['initial'])) {
						$lineItem->initial($item['initial']);
					}

					if (isset($item['options'])) {
						$lineItem->options($item['options']);
					}

					if (isset($item['uploader'])) {
						$lineItem->uploader($item['uploader']);
					}

					if (isset($item['rush_prices'])) {
						$lineItem->rush_prices($item['rush_prices']);
					}

					if (isset($item['metadata'])) {
						$lineItem->metadata($item['metadata']);
					}

					return $lineItem;
				});
			})
			->args(func_get_args());
	}

	public function lineItem($lineItemId): LineItem
	{
		return $this->lineItems()->firstWhere('id', $lineItemId);
	}

	public function addLineItem(array $lineItemData): LineItem
	{
		$product = ProductAPI::find($lineItemData['product']);

		$lineItem = (new LineItem)
			->id(mt_rand(1000000000,9999999999))
			->product($lineItemData['product'])
			->quantity($lineItemData['quantity'])
			->total($lineItemData['total']);

		if (isset($lineItemData['variant'])) {
			$lineItem->variant($lineItemData['variant']);
		}

		if (isset($lineItemData['tax'])) {
			$lineItem->tax($lineItemData['tax']);
		}

		if (isset($lineItemData['initial'])) {
			$lineItem->initial($lineItemData['initial']);
		}

		if (isset($lineItemData['options'])) {
			$lineItem->options($lineItemData['options']);
		}

		if (isset($lineItemData['uploader'])) {
			$lineItem->uploader($lineItemData['uploader']);
		}

		if (isset($lineItemData['metadata'])) {
			$lineItem->metadata($lineItemData['metadata']);
		}

		$this->lineItems = $this->lineItems->push($lineItem);

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItem($lineItem->id());
	}

	public function updateLineItem($lineItemId, array $lineItemData): LineItem
	{
		$this->lineItems = $this->lineItems->map(function ($item) use ($lineItemId, $lineItemData) {
			if ($item->id() !== $lineItemId) {
				return $item;
			}

			$lineItem = $item;

			if (isset($lineItemData['product'])) {
				$lineItem->product($lineItemData['product']);
			}

			if (isset($lineItemData['quantity'])) {
				$lineItem->quantity($lineItemData['quantity']);
			}

			if (isset($lineItemData['total'])) {
				$lineItem->total($lineItemData['total']);
			}

			if (isset($lineItemData['variant'])) {
				$lineItem->variant($lineItemData['variant']);
			}


			if (isset($lineItemData['tax'])) {
				$lineItem->tax($lineItemData['tax']);
			}

			if (isset($lineItemData['initial'])) {
				$lineItem->initial($lineItemData['initial']);
			}

			if (isset($lineItemData['options'])) {
				$lineItem->options($lineItemData['options']);
			}

			if (isset($lineItemData['uploader'])) {
				$lineItem->uploader($lineItemData['uploader']);
			}

			if (isset($lineItemData['rush_prices'])) {
				$lineItem->rush_prices($lineItemData['rush_prices']);
			}

			if (isset($lineItemData['metadata'])) {
				$lineItem->metadata($lineItemData['metadata']);
			}

			return $lineItem;
		});

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItem($lineItemId);
	}

	/**
	 * Increments the quantity of a cart item.
	 *
	 * @param int Id of the cart item
	 * @param int quantity to be increased
	 * @return array
	 */
	public function incrementQuantityAt($lineItemId, $quantity = 1){
		$this->lineItems = $this->lineItems->map(function ($item) use ($lineItemId, $quantity) {
			if ($item->id() !== (int) $lineItemId) {
				return $item;
			}

			$item->quantity($item->quantity() + $quantity);
			return $item;
		});

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItem($lineItemId);
	}


	/**
	 * Decrements the quantity of a cart item.
	 *
	 * @param int Index of the cart item
	 * @param int quantity to be decreased
	 * @return array
	 */
	public function decrementQuantityAt($lineItemId, $quantity = 1)
	{
		$this->lineItems = $this->lineItems->map(function ($item) use ($lineItemId, $quantity) {
			if ($item->id() !== (int) $lineItemId) {
				return $item;
			}

			$item->quantity($item->quantity() - $quantity);
			return $item;
		});

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItem($lineItemId);
	}

	public function removeLineItem($lineItemId): Collection
	{
		$this->lineItems = $this->lineItems->reject(function ($item) use ($lineItemId) {
			return $item->id() === $lineItemId;
		});

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItems();
	}

	public function clearLineItems(): Collection
	{
		$this->lineItems = collect();

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->lineItems();
	}


}
