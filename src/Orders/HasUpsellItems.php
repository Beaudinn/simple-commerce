<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use Illuminate\Support\Collection;

trait HasUpsellItems
{

	public function upsells($lineItems = null)
	{
		return $this
			->fluentlyGetOrSet('upsells')
			->setter(function ($value) {
				if ($value === null) {
					$value = collect();
				}

				if (is_array($value)) {
					$value = collect($value);
				}

				return $value->map(function ($item) {
					if ($item instanceof UpsellItem) {
						return $item;
					}

					if (! isset($item['id'])) {
						$item['id'] = mt_rand(1000000000,9999999999);// app('stache')->generateId();
					}

					if (! isset($item['total'])) {
						$item['total'] = 0;
					}

					$lineItem = (new UpsellItem($item))
						->id($item['id'])
						->item(isset($item['item']) ? $item['item'] : null)
						->product($item['product'])
						->price($item['price'])
						->quantity($item['quantity'])
						->total($item['total']);

					if (isset($item['variant'])) {
						$lineItem->variant($item['variant']);
					}

					if (isset($item['tax'])) {
						$lineItem->tax($item['tax']);
					}


					if (isset($item['metadata'])) {
						$lineItem->metadata($item['metadata']);
					}

					return $lineItem;
				});
			})
			->args(func_get_args());
	}

	public function upsellItem($lineItemId): UpsellItem
	{
		return $this->upsells()->firstWhere('id', $lineItemId);
	}

	public function addUpsellItem(array $lineItemData): UpsellItem
	{
		$product = ProductAPI::find($lineItemData['product']);

		if (! isset($lineItemData['id'])) {
			$lineItemData['id'] = mt_rand(1000000000,9999999999);// app('stache')->generateId();
		}

		$lineItem = (new UpsellItem)
			->id($lineItemData['id'])
			->product($lineItemData['product'])
			->item($lineItemData['item'])
			->price($lineItemData['price'])
			->quantity($lineItemData['quantity'])
			->total($lineItemData['total']);

		if (isset($lineItemData['variant'])) {
			$lineItem->variant($lineItemData['variant']);
		}

		if (isset($lineItemData['tax'])) {
			$lineItem->tax($lineItemData['tax']);
		}

		if (isset($lineItemData['metadata'])) {
			$lineItem->metadata($lineItemData['metadata']);
		}

		$this->upsells = $this->upsells->push($lineItem);

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->upsellItem($lineItem->id());
	}

	public function updateUpsellItem($lineItemId, array $lineItemData): UpsellItem
	{
		$this->upsells = $this->upsells->map(function ($item) use ($lineItemId, $lineItemData) {
			if ($item->id() !== $lineItemId) {
				return $item;
			}

			$lineItem = $item;

			if (isset($lineItemData['product'])) {
				$lineItem->product($lineItemData['product']);
			}

			if (isset($lineItemData['item'])) {
				$lineItem->item($lineItemData['item']);
			}

			if (isset($lineItemData['price'])) {
				$lineItem->price($lineItemData['price']);
			}


			if (isset($lineItemData['quantity'])) {
				$lineItem->quantity($lineItemData['quantity']);
			}

			if (isset($lineItemData['total'])) {
				$lineItem->total($lineItemData['total']);
			}


			if (isset($lineItemData['tax'])) {
				$lineItem->tax($lineItemData['tax']);
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

		return $this->upsellItem($lineItemId);
	}


	public function removeUpsellItem($uppsellItemId, $lineItemId): Collection
	{
		$this->upsells = $this->upsells->reject(function ($item) use ($uppsellItemId, $lineItemId) {

			return $item->id() === $lineItemId && ($uppsellItemId ? $uppsellItemId == $item->item()->id() : true);
		});

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->upsells();
	}

	public function clearUpsellItems(): Collection
	{
		$this->upsells = collect();

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->upsells();
	}


}
