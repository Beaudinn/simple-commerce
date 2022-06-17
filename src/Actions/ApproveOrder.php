<?php

namespace DoubleThreeDigital\SimpleCommerce\Actions;

use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Statamic\Actions\Action;
use Statamic\Entries\Entry;

class ApproveOrder extends Action
{
    public static function title()
    {
        return 'Approve';
    }

    public function visibleTo($item)
    {
        if (isset(SimpleCommerce::orderDriver()['collection'])) {
            return $item instanceof Entry
                && $item->collectionHandle() === SimpleCommerce::orderDriver()['collection']
	            && $item->get('is_paid')
                && $item->get('is_approved') !== true;
        }

        if (isset(SimpleCommerce::orderDriver()['model'])) {
            $orderModelClass = SimpleCommerce::orderDriver()['model'];

            //var_dump($item->items); die();
            return $item instanceof $orderModelClass
	            && count($item->items)
	            && $item->delivery_at
	            && $item->customer()
                && ! $item->is_approved;
        }

        return false;
    }

    public function visibleToBulk($items)
    {
        $allowedOnItems = $items->filter(function ($item) {
            return $this->visibleTo($item);
        });

        return $items->count() === $allowedOnItems->count();
    }

    public function run($items, $values)
    {
        collect($items)
            ->each(function ($entry) {
                $order = Order::find($entry->id);

                return $order->markAsApproved();
            });
    }
}
