<?php

namespace DoubleThreeDigital\SimpleCommerce\Events;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class OrderApproved
{
    use Dispatchable;
    use InteractsWithSockets;

    public Order $order;
    public array $values;

    public function __construct(Order $order, $values = [])
    {
        $this->order = $order;
        $this->values = $values;
    }
}
