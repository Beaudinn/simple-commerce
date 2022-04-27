<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use Illuminate\Support\Collection;

trait Upsells
{
    public function upsells($upsells = null)
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

                return $value;
            })
            ->args(func_get_args());
    }
    public function upsellItem($lineItemId): array
    {
        return $this->upsells()->firstWhere('id', $lineItemId);
    }

    public function addUpsellItem(array $lineItemData): array
    {
        $lineItemData['id'] = mt_rand(1000000000,9999999999); //app('stache')->generateId();

        $this->upsells = $this->upsells->push($lineItemData);

        $this->save();



        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $this->upsellItem($lineItemData['id']);
    }

    public function updateUpsellItem($lineItemId, array $lineItemData): array
    {
        $this->lineItems = $this->lineItems->map(function ($item) use ($lineItemId, $lineItemData) {
            if ($item['id'] !== $lineItemId) {
                return $item;
            }

            return array_merge($item, $lineItemData);
        });

        $this->save();

        if (! $this->withoutRecalculating) {
            $this->recalculate();
        }

        return $this->upsellItem($lineItemId);
    }

    public function removeUpsellItem($lineItemId): Collection
    {

        $this->lineItems = $this->lineItems->reject(function ($item) use ($lineItemId) {
            return $item['id'] === (int) $lineItemId;
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
