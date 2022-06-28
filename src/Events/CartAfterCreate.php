<?php

namespace DoubleThreeDigital\SimpleCommerce\Events;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class CartAfterCreate
{
    use Dispatchable;
    use InteractsWithSockets;

    public OrderModel $order;

    public function __construct(OrderModel $order)
    {
        $this->order = $order;
    }
}
