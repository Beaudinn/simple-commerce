<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Facades\Product as ProductAPI;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use Illuminate\Support\Collection;

trait UpsellItems
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
					if ($item instanceof LineItem) {
						return $item;
					}

					if (! isset($item['id'])) {
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

	public function upsellItem($lineItemId): LineItem
	{
		return $this->upsells()->firstWhere('id', $lineItemId);
	}

	public function addUpsellItem(array $lineItemData): LineItem
	{
		$product = ProductAPI::find($lineItemData['product']);

		if (! isset($lineItemData['id'])) {
			$lineItemData['id'] = mt_rand(1000000000,9999999999);// app('stache')->generateId();
		}

		$lineItem = (new LineItem)
			->id($lineItemData['id'])
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
			$lineItem->options($lineItemData['initial']);
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

		$this->upsells = $this->upsells->push($lineItem);

		$this->save();

		if (! $this->withoutRecalculating) {
			$this->recalculate();
		}

		return $this->upsellItem($lineItem->id());
	}

	public function updateUpsellItem($lineItemId, array $lineItemData): LineItem
	{
		$this->upsells = $this->upsells->map(function ($item) use ($lineItemId, $lineItemData) {
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


	public function removeUpsellItem($lineItemId): Collection
	{
		$this->upsells = $this->upsells->reject(function ($item) use ($lineItemId) {
			return $item->id() === $lineItemId;
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
