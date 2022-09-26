<?php

namespace DoubleThreeDigital\SimpleCommerce\Actions;

use DoubleThreeDigital\SimpleCommerce\Facades\Gateway;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Statamic\Actions\Action;
use Statamic\Entries\Entry;

class RecalculateCustomAction  extends Action
{
    public static function title()
    {
        return __('Recalculate Custom');
    }

    public function visibleTo($item)
    {
        //if (isset(SimpleCommerce::orderDriver()['collection'])) {
        //    return $item instanceof Entry
        //        && $item->collectionHandle() === SimpleCommerce::orderDriver()['collection']
        //        && $item->get('is_paid') === true
        //        && $item->get('is_refunded') !== true;
        //}

        if (isset(SimpleCommerce::orderDriver()['model'])) {
            $orderModelClass = SimpleCommerce::orderDriver()['model'];


            return $item instanceof $orderModelClass
                && ! $item->invoice;
        }

        return false;
    }

    public function visibleToBulk($items)
    {
        return false;
    }

    public function run($items, $values)
    {
        collect($items)
            ->each(function ($entry) {
                $order = Order::find($entry->id, true);

                return $order->recalculateBase();
            });
    }
}
